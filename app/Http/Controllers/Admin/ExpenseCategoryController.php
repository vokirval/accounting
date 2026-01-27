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

        $expenseCategory->delete();

        return back();
    }
}
