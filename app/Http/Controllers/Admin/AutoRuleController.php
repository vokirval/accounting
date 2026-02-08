<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AutoRuleStoreRequest;
use App\Http\Requests\Admin\AutoRuleUpdateRequest;
use App\Models\AutoRule;
use App\Models\AutoRuleLog;
use App\Models\ExpenseCategory;
use App\Models\ExpenseType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AutoRuleController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', AutoRule::class);

        return Inertia::render('admin/auto-rules/Index', [
            'items' => AutoRule::query()
                ->with(['expenseType:id,name', 'expenseCategory:id,name'])
                ->orderByDesc('created_at')
                ->get(),
            'logs' => AutoRuleLog::query()
                ->with('rule:id,name')
                ->orderByDesc('created_at')
                ->limit(200)
                ->get(),
            'expenseTypes' => ExpenseType::query()->orderBy('name')->get(['id', 'name']),
            'expenseCategories' => ExpenseCategory::query()->orderBy('name')->get(['id', 'name', 'expense_type_id']),
            'timezone' => 'Europe/Kyiv',
        ]);
    }

    public function store(AutoRuleStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', AutoRule::class);

        $data = $request->validated();

        if ($request->hasFile('requisites_file')) {
            $data['requisites_file_url'] = $this->storeRuleFile($request->file('requisites_file'));
        }

        unset($data['requisites_file']);

        $rule = AutoRule::create([
            ...$data,
            'user_id' => $request->user()->id,
            'timezone' => $data['timezone'] ?? 'Europe/Kyiv',
        ]);

        $rule->next_run_at = $rule->computeNextRunAt();
        if ($rule->frequency === 'once' && $rule->next_run_at === null) {
            $rule->is_active = false;
        }
        $rule->save();

        return back();
    }

    public function update(AutoRuleUpdateRequest $request, AutoRule $autoRule): RedirectResponse
    {
        $this->authorize('update', $autoRule);

        $data = $request->validated();

        if ($request->hasFile('requisites_file')) {
            $this->deleteFileByUrl($autoRule->requisites_file_url);
            $data['requisites_file_url'] = $this->storeRuleFile($request->file('requisites_file'));
        }

        unset($data['requisites_file']);

        $autoRule->fill($data);
        $autoRule->next_run_at = $autoRule->computeNextRunAt();
        if ($autoRule->frequency === 'once' && $autoRule->next_run_at === null) {
            $autoRule->is_active = false;
        }
        $autoRule->save();

        return back();
    }

    public function destroy(AutoRule $autoRule): RedirectResponse
    {
        $this->authorize('delete', $autoRule);

        $this->deleteFileByUrl($autoRule->requisites_file_url);
        $autoRule->delete();

        return back();
    }

    private function storeRuleFile(\Illuminate\Http\UploadedFile $file): string
    {
        $disk = (string) config('payment_requests.files_disk', config('filesystems.payment_disk', 'public'));
        $path = $file->storeAs('auto-rule-requisites', $this->makeStoredFilename($file), $disk);

        return Storage::disk($disk)->url($path);
    }

    private function deleteFileByUrl(?string $url): void
    {
        if (! $url) {
            return;
        }

        $disk = (string) config('payment_requests.files_disk', config('filesystems.payment_disk', 'public'));
        $baseUrl = rtrim((string) Storage::disk($disk)->url(''), '/');
        $cleanUrl = strtok($url, '?') ?: $url;

        if ($baseUrl === '' || ! Str::startsWith($cleanUrl, $baseUrl)) {
            return;
        }

        $path = ltrim(Str::after($cleanUrl, $baseUrl), '/');
        if ($path === '') {
            return;
        }

        Storage::disk($disk)->delete($path);
    }

    private function makeStoredFilename(\Illuminate\Http\UploadedFile $file): string
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());
        if ($extension === '') {
            $extension = strtolower((string) ($file->guessExtension() ?: 'bin'));
        }

        return sprintf('%s.%s', (string) Str::uuid(), $extension);
    }
}
