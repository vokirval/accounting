<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ExpenseTypeStoreRequest;
use App\Http\Requests\Admin\ExpenseTypeUpdateRequest;
use App\Models\ExpenseType;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseTypeController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', ExpenseType::class);

        return Inertia::render('admin/expense-types/Index', [
            'items' => ExpenseType::query()->orderBy('name')->get(),
        ]);
    }

    public function store(ExpenseTypeStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', ExpenseType::class);

        ExpenseType::create($request->validated());

        return back();
    }

    public function update(ExpenseTypeUpdateRequest $request, ExpenseType $expenseType): RedirectResponse
    {
        $this->authorize('update', $expenseType);

        $expenseType->update($request->validated());

        return back();
    }

    public function destroy(ExpenseType $expenseType): RedirectResponse
    {
        $this->authorize('delete', $expenseType);

        $expenseType->delete();

        return back();
    }
}
