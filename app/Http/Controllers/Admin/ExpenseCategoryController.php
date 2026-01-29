<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ExpenseCategoryStoreRequest;
use App\Http\Requests\Admin\ExpenseCategoryUpdateRequest;
use App\Models\ExpenseCategory;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseCategoryController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', ExpenseCategory::class);

        return Inertia::render('admin/expense-categories/Index', [
            'items' => ExpenseCategory::query()->orderBy('name')->get(),
        ]);
    }

    public function store(ExpenseCategoryStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', ExpenseCategory::class);

        ExpenseCategory::create($request->validated());

        return back();
    }

    public function update(ExpenseCategoryUpdateRequest $request, ExpenseCategory $expenseCategory): RedirectResponse
    {
        $this->authorize('update', $expenseCategory);

        $expenseCategory->update($request->validated());

        return back();
    }

    public function destroy(ExpenseCategory $expenseCategory): RedirectResponse
    {
        $this->authorize('delete', $expenseCategory);

        $requestCount = $expenseCategory->paymentRequests()->count();
        if ($requestCount > 0) {
            return back()->withErrors([
                'delete' => sprintf(
                    'РќРµРјРѕР¶Р»РёРІРѕ РІРёРґР°Р»РёС‚Рё РєР°С‚РµРіРѕСЂС–СЋ РІРёС‚СЂР°С‚. РџРѕРІвЂ™СЏР·Р°РЅС– Р·Р°СЏРІРєРё: %d. РЎРїРѕС‡Р°С‚РєСѓ РІРёРґР°Р»С–С‚СЊ Р°Р±Рѕ Р·РјС–РЅС–С‚СЊ С†С– Р·Р°СЏРІРєРё.',
                    $requestCount,
                ),
            ]);
        }

        $expenseCategory->delete();

        return back();
    }
}
