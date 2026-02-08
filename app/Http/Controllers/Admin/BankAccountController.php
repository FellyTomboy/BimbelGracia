<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BankAccountController extends Controller
{
    public function index(): View
    {
        $accounts = BankAccount::query()
            ->latest()
            ->get();

        return view('admin.bank-accounts.index', compact('accounts'));
    }

    public function inactive(): View
    {
        $accounts = BankAccount::onlyTrashed()
            ->latest('deleted_at')
            ->get();

        return view('admin.bank-accounts.inactive', compact('accounts'));
    }

    public function create(): View
    {
        return view('admin.bank-accounts.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'bank_name' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:50'],
            'account_holder' => ['required', 'string', 'max:120'],
            'status' => ['required', 'in:active,hibernasi'],
        ]);

        BankAccount::create($validated);

        return redirect()
            ->route('admin.bank-accounts.index')
            ->with('status', 'Rekening bimbel berhasil dibuat.');
    }

    public function edit(BankAccount $bankAccount): View
    {
        return view('admin.bank-accounts.edit', compact('bankAccount'));
    }

    public function update(Request $request, BankAccount $bankAccount): RedirectResponse
    {
        $validated = $request->validate([
            'bank_name' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:50'],
            'account_holder' => ['required', 'string', 'max:120'],
            'status' => ['required', 'in:active,hibernasi'],
        ]);

        $bankAccount->update($validated);

        return redirect()
            ->route('admin.bank-accounts.index')
            ->with('status', 'Rekening bimbel berhasil diperbarui.');
    }

    public function destroy(BankAccount $bankAccount): RedirectResponse
    {
        $bankAccount->delete();

        return redirect()
            ->route('admin.bank-accounts.index')
            ->with('status', 'Rekening bimbel dihibernasi.');
    }

    public function restore(int $bankAccountId): RedirectResponse
    {
        $bankAccount = BankAccount::withTrashed()->findOrFail($bankAccountId);
        $bankAccount->restore();

        return redirect()
            ->route('admin.bank-accounts.index')
            ->with('status', 'Rekening bimbel berhasil dipulihkan.');
    }
}
