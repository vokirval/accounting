<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentRequestStoreRequest;
use App\Http\Requests\PaymentRequestUpdateRequest;
use App\Models\ExpenseCategory;
use App\Models\ExpenseType;
use App\Models\PaymentAccount;
use App\Models\PaymentRequest;
use App\Models\PaymentRequestHistory;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PaymentRequestController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', PaymentRequest::class);

        $user = $request->user();
        $query = PaymentRequest::query()
            ->with([
                'author:id,name',
                'expenseType:id,name',
                'expenseCategory:id,name',
                'paidAccount:id,name',
                'participants:id,name',
                'history.user:id,name',
            ])
            ->orderByDesc('created_at');

        if ($user->isUser()) {
            $query->whereHas('author', function ($participants) use ($user) {
                $participants->where('users.id', $user->id);
            });
        }

        $authorId = $request->input('author_id');
        if ($authorId !== null && $authorId !== '') {
            $query->where('user_id', (int) $authorId);
        }

        $participantId = $request->input('participant_id');
        if ($participantId !== null && $participantId !== '') {
            $query->whereHas('participants', function ($participants) use ($participantId) {
                $participants->where('users.id', (int) $participantId);
            });
        }

        $expenseTypeId = $request->input('expense_type_id');
        if ($expenseTypeId !== null && $expenseTypeId !== '') {
            $query->where('expense_type_id', (int) $expenseTypeId);
        }

        $expenseCategoryId = $request->input('expense_category_id');
        if ($expenseCategoryId !== null && $expenseCategoryId !== '') {
            $query->where('expense_category_id', (int) $expenseCategoryId);
        }

        $paidAccountId = $request->input('paid_account_id');
        if ($paidAccountId !== null && $paidAccountId !== '') {
            $query->where('paid_account_id', (int) $paidAccountId);
        }

        if ($request->filled('purchase_reference')) {
            $query->where('purchase_reference', 'like', '%'.$request->string('purchase_reference').'%');
        }

        $readyForPayment = $request->input('ready_for_payment');
        if ($readyForPayment !== null && $readyForPayment !== '') {
            $query->where('ready_for_payment', (string) $readyForPayment === '1');
        }

        $paid = $request->input('paid');
        if ($paid !== null && $paid !== '') {
            $query->where('paid', (string) $paid === '1');
        }

        if ($request->filled('created_from')) {
            $query->whereDate('created_at', '>=', $request->date('created_from'));
        }

        if ($request->filled('created_to')) {
            $query->whereDate('created_at', '<=', $request->date('created_to'));
        }

        $usersQuery = User::query()->orderBy('name')->select(['id', 'name', 'email', 'role']);

        if ($user->isUser()) {
            $usersQuery->whereKey($user->id);
        }

        $expenseTypes = ExpenseType::query()->orderBy('name')->get(['id', 'name']);
        $expenseCategories = ExpenseCategory::query()->orderBy('name')->get(['id', 'name', 'expense_type_id']);
        $paymentAccounts = PaymentAccount::query()->orderBy('name')->get(['id', 'name']);

        $expenseTypeNames = $expenseTypes->pluck('name', 'id')->all();
        $expenseCategoryNames = $expenseCategories->pluck('name', 'id')->all();
        $paymentAccountNames = $paymentAccounts->pluck('name', 'id')->all();

        $mapHistoryValue = function (string $field, mixed $value) use (
            $expenseTypeNames,
            $expenseCategoryNames,
            $paymentAccountNames,
        ): mixed {
            if ($value === null || $value === '') {
                return $value;
            }

            $id = (int) $value;

            return match ($field) {
                'expense_type_id' => $expenseTypeNames[$id] ?? $value,
                'expense_category_id' => $expenseCategoryNames[$id] ?? $value,
                'paid_account_id' => $paymentAccountNames[$id] ?? $value,
                default => $value,
            };
        };

        $showTotals = $request->filled('created_from');
        $totals = null;

        if ($showTotals) {
            $totalsQuery = clone $query;
            $totals = [
                'amount' => (float) $totalsQuery->sum('amount'),
                'commission' => (float) $totalsQuery->sum('commission'),
            ];
        }

        $perPage = (int) $request->input('per_page', 30);
        $allowedPerPage = [10, 20, 30, 50, 100];
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 30;
        }

        $paymentRequests = $query
            ->paginate($perPage)
            ->withQueryString()
            ->through(function (PaymentRequest $paymentRequest) use (
                $user,
                $mapHistoryValue,
            ) {
                return [
                    'id' => $paymentRequest->id,
                    'author' => $paymentRequest->author,
                    'expense_type' => $paymentRequest->expenseType,
                    'expense_category' => $paymentRequest->expenseCategory,
                    'paid_account' => $paymentRequest->paidAccount,
                    'participants' => $paymentRequest->participants,
                    'requisites' => $paymentRequest->requisites,
                    'requisites_file_url' => $paymentRequest->requisites_file_url,
                    'amount' => $paymentRequest->amount,
                    'commission' => $paymentRequest->commission,
                    'purchase_reference' => $paymentRequest->purchase_reference,
                    'ready_for_payment' => $paymentRequest->ready_for_payment,
                    'paid' => $paymentRequest->paid,
                    'paid_account_id' => $paymentRequest->paid_account_id,
                    'receipt_url' => $paymentRequest->receipt_url,
                    'created_at' => $paymentRequest->created_at,
                    'updated_at' => $paymentRequest->updated_at,
                    'history' => $paymentRequest->history
                        ->sortByDesc('created_at')
                        ->values()
                        ->map(function ($history) use ($mapHistoryValue) {
                            $changed = $history->changed_fields ?? [];

                            foreach ($changed as $field => $value) {
                                if (is_array($value) && array_key_exists('old', $value) && array_key_exists('new', $value)) {
                                    $value['old'] = $mapHistoryValue($field, $value['old']);
                                    $value['new'] = $mapHistoryValue($field, $value['new']);
                                    $changed[$field] = $value;
                                    continue;
                                }

                                $changed[$field] = $mapHistoryValue($field, $value);
                            }

                            return [
                                'id' => $history->id,
                                'user' => $history->user,
                                'action' => $history->action,
                                'changed_fields' => $changed,
                                'created_at' => $history->created_at,
                            ];
                        }),
                    'can' => [
                        'update' => Gate::forUser($user)->allows('update', $paymentRequest),
                        'view' => Gate::forUser($user)->allows('view', $paymentRequest),
                    ],
                ];
            });

        return Inertia::render('payment-requests/Index', [
            'paymentRequests' => $paymentRequests,
            'filters' => [
                'author_id' => $request->input('author_id'),
                'participant_id' => $request->input('participant_id'),
                'expense_type_id' => $request->input('expense_type_id'),
                'expense_category_id' => $request->input('expense_category_id'),
                'paid_account_id' => $request->input('paid_account_id'),
                'purchase_reference' => $request->input('purchase_reference'),
                'ready_for_payment' => $request->input('ready_for_payment'),
                'paid' => $request->input('paid'),
                'created_from' => $request->input('created_from'),
                'created_to' => $request->input('created_to'),
                'per_page' => $perPage,
            ],
            'expenseTypes' => $expenseTypes,
            'expenseCategories' => $expenseCategories,
            'paymentAccounts' => $paymentAccounts,
            'users' => $usersQuery->get(),
            'totals' => $totals,
            'permissions' => [
                'create' => Gate::forUser($user)->allows('create', PaymentRequest::class),
            ],
        ]);
    }

    public function store(PaymentRequestStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', PaymentRequest::class);

        $user = $request->user();
        $data = $request->validated();

        [$ready, $paid] = $this->normalizeStatus(
            (bool) ($data['ready_for_payment'] ?? false),
            (bool) ($data['paid'] ?? false),
        );

        $data['ready_for_payment'] = $ready;
        $data['paid'] = $paid;

        if (array_key_exists('receipt_url', $data) && $data['receipt_url'] === '') {
            $data['receipt_url'] = null;
        }

        if (array_key_exists('commission', $data) && $data['commission'] === '') {
            $data['commission'] = null;
        }

        if ($request->hasFile('receipt_file')) {
            $data['receipt_url'] = $this->storeReceiptFile($request->file('receipt_file'));
        }

        if ($request->hasFile('requisites_file')) {
            $data['requisites_file_url'] = $this->storeRequisitesFile($request->file('requisites_file'));
            $data['requisites_file_uploaded_at'] = now();
        }

        unset($data['receipt_file']);
        unset($data['requisites_file']);

        if ($user->isUser()) {
            $data['commission'] = null;
        }

        $paymentRequest = PaymentRequest::create([
            ...$data,
            'user_id' => $user->id,
        ]);

        $paymentRequest->participants()->syncWithoutDetaching([$user->id]);

        PaymentRequestHistory::create([
            'payment_request_id' => $paymentRequest->id,
            'user_id' => $user->id,
            'action' => 'created',
            'changed_fields' => Arr::only($paymentRequest->fresh()->toArray(), [
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

        return back();
    }

    public function update(PaymentRequestUpdateRequest $request, PaymentRequest $paymentRequest): RedirectResponse
    {
        $this->authorize('update', $paymentRequest);

        $user = $request->user();
        $original = $paymentRequest->getOriginal();

        $data = $request->validated();

        [$ready, $paid] = $this->normalizeStatus(
            (bool) ($data['ready_for_payment'] ?? $paymentRequest->ready_for_payment),
            (bool) ($data['paid'] ?? $paymentRequest->paid),
        );

        $data['ready_for_payment'] = $ready;
        $data['paid'] = $paid;

        $statusWillChange = ($ready !== $paymentRequest->ready_for_payment) || ($paid !== $paymentRequest->paid);
        if ($statusWillChange && ! Gate::forUser($user)->allows('changeStatus', $paymentRequest)) {
            abort(403);
        }

        if (array_key_exists('receipt_url', $data) && $data['receipt_url'] === '' && ! $request->hasFile('receipt_file')) {
            unset($data['receipt_url']);
        }

        if (array_key_exists('commission', $data) && $data['commission'] === '') {
            $data['commission'] = null;
        }

        if ($user->isUser()) {
            $data['commission'] = $paymentRequest->commission;
        }

        if ($request->hasFile('receipt_file')) {
            $data['receipt_url'] = $this->storeReceiptFile($request->file('receipt_file'));
        }

        if ($request->hasFile('requisites_file')) {
            $this->deleteFileByUrl($paymentRequest->requisites_file_url);
            $data['requisites_file_url'] = $this->storeRequisitesFile($request->file('requisites_file'));
            $data['requisites_file_uploaded_at'] = now();
        }

        unset($data['receipt_file']);
        unset($data['requisites_file']);

        $paymentRequest->fill($data);
        $paymentRequest->save();

        $changes = $this->buildChangeSet($original, $paymentRequest->getChanges());

        if ($changes !== []) {
            $paymentRequest->participants()->syncWithoutDetaching([$user->id]);

            $statusChanged = array_key_exists('ready_for_payment', $changes) || array_key_exists('paid', $changes);

            PaymentRequestHistory::create([
                'payment_request_id' => $paymentRequest->id,
                'user_id' => $user->id,
                'action' => $statusChanged ? 'status_changed' : 'updated',
                'changed_fields' => $changes,
            ]);
        }

        return back();
    }

    private function buildChangeSet(array $original, array $changes): array
    {
        $filtered = Arr::except($changes, ['updated_at', 'requisites_file_uploaded_at']);

        $result = [];
        foreach ($filtered as $field => $value) {
            $result[$field] = [
                'old' => $original[$field] ?? null,
                'new' => $value,
            ];
        }

        return $result;
    }

    private function normalizeStatus(bool $ready, bool $paid): array
    {
        if (! $ready) {
            $paid = false;
        }

        if ($paid) {
            $ready = true;
        }

        return [$ready, $paid];
    }

    private function storeReceiptFile(\Illuminate\Http\UploadedFile $file): string
    {
        $disk = $this->paymentFilesDisk();
        $path = $file->store('receipts', $disk);

        return Storage::disk($disk)->url($path);
    }

    private function storeRequisitesFile(\Illuminate\Http\UploadedFile $file): string
    {
        $disk = $this->paymentFilesDisk();
        $path = $file->store('requisites', $disk);

        return Storage::disk($disk)->url($path);
    }

    private function paymentFilesDisk(): string
    {
        return (string) config('payment_requests.files_disk', config('filesystems.payment_disk', 'public'));
    }

    private function deleteFileByUrl(?string $url): void
    {
        if (! $url) {
            return;
        }

        $disk = $this->paymentFilesDisk();
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
}
