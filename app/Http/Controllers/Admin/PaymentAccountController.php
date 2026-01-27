<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PaymentAccountStoreRequest;
use App\Http\Requests\Admin\PaymentAccountUpdateRequest;
use App\Models\PaymentAccount;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PaymentAccountController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', PaymentAccount::class);

        return Inertia::render('admin/payment-accounts/Index', [
            'items' => PaymentAccount::query()->orderBy('name')->get(),
        ]);
    }

    public function store(PaymentAccountStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', PaymentAccount::class);

        PaymentAccount::create($request->validated());

        return back();
    }

    public function update(PaymentAccountUpdateRequest $request, PaymentAccount $paymentAccount): RedirectResponse
    {
        $this->authorize('update', $paymentAccount);

        $paymentAccount->update($request->validated());

        return back();
    }

    public function destroy(PaymentAccount $paymentAccount): RedirectResponse
    {
        $this->authorize('delete', $paymentAccount);

        $paymentAccount->delete();

        return back();
    }
}
