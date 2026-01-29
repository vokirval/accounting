<?php

namespace App\Console\Commands;

use App\Models\PaymentRequest;
use App\Models\PaymentRequestHistory;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PruneRequisitesFiles extends Command
{
    protected $signature = 'payment-requests:prune-requisites';

    protected $description = 'Remove requisites files older than configured retention days.';

    public function handle(): int
    {
        $days = (int) config('payment_requests.requisites_file_retention_days', 7);
        if ($days <= 0) {
            $this->info('Requisites file retention is disabled.');
            return Command::SUCCESS;
        }

        $cutoff = now()->subDays($days);
        $disk = (string) config('payment_requests.files_disk', config('filesystems.payment_disk', 'public'));
        $baseUrl = rtrim((string) Storage::disk($disk)->url(''), '/');

        $historyUser = User::query()
            ->where('role', 'admin')
            ->whereNull('blocked_at')
            ->orderBy('id')
            ->first();

        $deleted = 0;
        $skipped = 0;

        PaymentRequest::query()
            ->whereNotNull('requisites_file_url')
            ->whereNotNull('requisites_file_uploaded_at')
            ->where('requisites_file_uploaded_at', '<=', $cutoff)
            ->orderBy('id')
            ->chunkById(200, function ($requests) use ($disk, $baseUrl, $historyUser, &$deleted, &$skipped) {
                foreach ($requests as $request) {
                    $url = (string) $request->requisites_file_url;
                    $path = $this->resolvePath($url, $baseUrl);

                    if ($path === null) {
                        $skipped++;
                        continue;
                    }

                    if (Storage::disk($disk)->exists($path)) {
                        Storage::disk($disk)->delete($path);
                    }

                    $request->forceFill([
                        'requisites_file_url' => null,
                        'requisites_file_uploaded_at' => null,
                    ])->save();

                    $historyUserId = $historyUser?->id ?? $request->user_id;
                    $request->participants()->syncWithoutDetaching([$historyUserId]);

                    PaymentRequestHistory::create([
                        'payment_request_id' => $request->id,
                        'user_id' => $historyUserId,
                        'action' => 'requisites_file_pruned',
                        'changed_fields' => [
                            'requisites_file_url' => [
                                'old' => $url,
                                'new' => null,
                            ],
                        ],
                    ]);

                    $deleted++;
                }
            });

        $this->info(sprintf('Requisites files removed: %d. Skipped: %d.', $deleted, $skipped));

        return Command::SUCCESS;
    }

    private function resolvePath(string $url, string $baseUrl): ?string
    {
        $cleanUrl = strtok($url, '?') ?: $url;

        if ($baseUrl === '' || ! Str::startsWith($cleanUrl, $baseUrl)) {
            return null;
        }

        $path = ltrim(Str::after($cleanUrl, $baseUrl), '/');

        return $path !== '' ? $path : null;
    }
}
