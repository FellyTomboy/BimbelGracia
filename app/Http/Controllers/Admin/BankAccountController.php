<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Traits\SearchAndSort;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BankAccountController extends Controller
{
    use SearchAndSort;

    public function index(Request $request): View
    {
        $params = $this->getSearchSortParams($request);

        $accounts = BankAccount::query();

        $accounts = $this->applySearch($accounts, $params['search'], [
            'bank_name', 'account_number', 'account_holder', 'status',
        ]);

        $accounts = $this->applySort($accounts, $params['sort'], $params['direction'], [
            'bank_name', 'status', 'created_at',
        ]);

        $accounts = $accounts->paginate(20)->withQueryString();

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

    public function restore(Request $request): JsonResponse|RedirectResponse
    {
        $bankAccountId = $request->route('bankAccount');
        $bankAccount = BankAccount::withTrashed()->findOrFail($bankAccountId);
        $bankAccount->restore();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Rekening berhasil dipulihkan.']);
        }

        return redirect()
            ->route('admin.bank-accounts.index')
            ->with('status', 'Rekening bimbel berhasil dipulihkan.');
    }

    public function bulkDestroy(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $count = BankAccount::whereIn('id', $validated['ids'])->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => "{$count} rekening bimbel dihibernasi."]);
        }

        return redirect()
            ->route('admin.bank-accounts.index')
            ->with('status', "{$count} rekening bimbel dihibernasi.");
    }

    public function forceDestroy(Request $request, int $bankAccountId): JsonResponse|RedirectResponse
    {
        $bankAccount = BankAccount::onlyTrashed()->findOrFail($bankAccountId);

        DB::transaction(fn () => $bankAccount->forceDelete());

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Rekening bimbel dihapus permanen.']);
        }

        return redirect()
            ->route('admin.bank-accounts.inactive')
            ->with('status', 'Rekening bimbel dihapus permanen.');
    }

    public function bulkForceDestroy(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $count = DB::transaction(fn () =>
            BankAccount::onlyTrashed()->whereIn('id', $validated['ids'])->forceDelete()
        );

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => "{$count} rekening bimbel dihapus permanen."]);
        }

        return redirect()
            ->route('admin.bank-accounts.inactive')
            ->with('status', "{$count} rekening bimbel dihapus permanen.");
    }
}
