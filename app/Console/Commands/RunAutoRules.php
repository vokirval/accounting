<?php

namespace App\Console\Commands;

use App\Models\AutoRule;
use App\Models\AutoRuleLog;
use App\Models\PaymentRequest;
use App\Models\PaymentRequestHistory;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RunAutoRules extends Command
{
    protected $signature = 'auto-rules:run';

    protected $description = 'Create payment requests based on auto rules.';

    public function handle(): int
    {
        $now = now('UTC');
        $disk = (string) config('payment_requests.files_disk', config('filesystems.payment_disk', 'public'));
        $debug = (bool) config('payment_requests.auto_rules_debug', false);

        if ($debug) {
            $activeRules = AutoRule::query()->where('is_active', true)->get();
            $totalActive = $activeRules->count();
            $dueCount = AutoRule::query()
                ->where('is_active', true)
                ->whereNotNull('next_run_at')
                ->where('next_run_at', '<=', $now)
                ->count();

            Log::info('Auto rules run started.', [
                'now_utc' => $now->toDateTimeString(),
                'now_kyiv' => $now->copy()->setTimezone('Europe/Kyiv')->toDateTimeString(),
                'active_rules' => $totalActive,
                'due_rules' => $dueCount,
            ]);

            foreach ($activeRules as $rule) {
                $tz = $rule->timezone ?: 'Europe/Kyiv';
                Log::info('Auto rule snapshot.', [
                    'rule_id' => $rule->id,
                    'frequency' => $rule->frequency,
                    'start_date' => (string) $rule->start_date,
                    'run_at' => $rule->run_at,
                    'timezone' => $tz,
                    'next_run_at_utc' => optional($rule->next_run_at)->toDateTimeString(),
                    'next_run_at_local' => optional($rule->next_run_at)->copy()->setTimezone($tz)->toDateTimeString(),
                    'last_run_at_utc' => optional($rule->last_run_at)->toDateTimeString(),
                ]);
            }
        }

        AutoRule::query()
            ->where('is_active', true)
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', $now)
            ->orderBy('next_run_at')
            ->chunkById(100, function ($rules) use ($now, $disk) {
                foreach ($rules as $rule) {
                    try {
                        $debug = (bool) config('payment_requests.auto_rules_debug', false);
                        if ($debug) {
                            $nextRunLocal = $rule->next_run_at
                                ? $rule->next_run_at->copy()->setTimezone($rule->timezone ?: 'Europe/Kyiv')->toDateTimeString()
                                : null;
                            Log::info('Auto rule due.', [
                                'rule_id' => $rule->id,
                                'frequency' => $rule->frequency,
                                'start_date' => (string) $rule->start_date,
                                'run_at' => $rule->run_at,
                                'timezone' => $rule->timezone ?: 'Europe/Kyiv',
                                'next_run_at_utc' => optional($rule->next_run_at)->toDateTimeString(),
                                'next_run_at_local' => $nextRunLocal,
                                'now_utc' => $now->toDateTimeString(),
                                'now_local' => $now->copy()->setTimezone($rule->timezone ?: 'Europe/Kyiv')->toDateTimeString(),
                            ]);
                        }

                        $requisitesFileUrl = null;
                        $requisitesUploadedAt = null;

                        if ($rule->requisites_file_url) {
                            $sourcePath = $this->pathFromUrl($rule->requisites_file_url, $disk);
                            if ($sourcePath && Storage::disk($disk)->exists($sourcePath)) {
                                $targetPath = 'requisites/'.$this->makeStoredFilenameFromPath($sourcePath);
                                Storage::disk($disk)->copy($sourcePath, $targetPath);
                                $requisitesFileUrl = Storage::disk($disk)->url($targetPath);
                                $requisitesUploadedAt = now();
                            } else {
                                AutoRuleLog::create([
                                    'auto_rule_id' => $rule->id,
                                    'level' => 'error',
                                    'message' => 'Не вдалося знайти файл реквізитів для копіювання.',
                                    'context' => [
                                        'requisites_file_url' => $rule->requisites_file_url,
                                    ],
                                ]);
                            }
                        }

                        $paymentRequest = PaymentRequest::create([
                            'user_id' => $rule->user_id,
                            'expense_type_id' => $rule->expense_type_id,
                            'expense_category_id' => $rule->expense_category_id,
                            'requisites' => $rule->requisites,
                            'requisites_file_url' => $requisitesFileUrl,
                            'requisites_file_uploaded_at' => $requisitesUploadedAt,
                            'amount' => $rule->amount,
                            'commission' => null,
                            'purchase_reference' => null,
                            'ready_for_payment' => (bool) $rule->ready_for_payment,
                            'paid' => false,
                            'paid_account_id' => null,
                            'receipt_url' => null,
                        ]);

                        $paymentRequest->participants()->syncWithoutDetaching([$rule->user_id]);

                        PaymentRequestHistory::create([
                            'payment_request_id' => $paymentRequest->id,
                            'user_id' => $rule->user_id,
                            'action' => 'created',
                            'changed_fields' => Arr::only($paymentRequest->toArray(), [
                                'user_id',
                                'expense_type_id',
                                'expense_category_id',
                                'requisites',
                                'requisites_file_url',
                                'amount',
                                'commission',
                                'purchase_reference',
                                'ready_for_payment',
                                'paid',
                                'paid_account_id',
                                'receipt_url',
                            ]),
                        ]);

                        $rule->last_run_at = $now;
                        if ($rule->frequency === 'once') {
                            $rule->is_active = false;
                            $rule->next_run_at = null;
                        } else {
                            $rule->next_run_at = $rule->computeNextRunAt($now);
                        }
                        $rule->save();

                        AutoRuleLog::create([
                            'auto_rule_id' => $rule->id,
                            'level' => 'info',
                            'message' => 'Заявку створено автоматично.',
                            'context' => [
                                'payment_request_id' => $paymentRequest->id,
                            ],
                        ]);

                        if ($debug) {
                            Log::info('Auto rule completed.', [
                                'rule_id' => $rule->id,
                                'payment_request_id' => $paymentRequest->id,
                                'next_run_at_utc' => optional($rule->next_run_at)->toDateTimeString(),
                            ]);
                        }
                    } catch (\Throwable $e) {
                        AutoRuleLog::create([
                            'auto_rule_id' => $rule->id,
                            'level' => 'error',
                            'message' => $e->getMessage(),
                            'context' => [
                                'trace' => collect($e->getTrace())->take(5)->all(),
                            ],
                        ]);

                        if ($debug) {
                            Log::error('Auto rule failed.', [
                                'rule_id' => $rule->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }
            });

        if ($debug) {
            Log::info('Auto rules run finished.', [
                'now_utc' => $now->toDateTimeString(),
            ]);
        }

        return Command::SUCCESS;
    }

    private function pathFromUrl(string $url, string $disk): ?string
    {
        $baseUrl = rtrim((string) Storage::disk($disk)->url(''), '/');
        $cleanUrl = strtok($url, '?') ?: $url;

        if ($baseUrl === '' || ! Str::startsWith($cleanUrl, $baseUrl)) {
            return null;
        }

        $path = ltrim(Str::after($cleanUrl, $baseUrl), '/');

        return $path !== '' ? $path : null;
    }

    private function makeStoredFilenameFromPath(string $path): string
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        if ($extension === '') {
            $extension = 'bin';
        }

        return sprintf('%s.%s', (string) Str::uuid(), $extension);
    }
}

