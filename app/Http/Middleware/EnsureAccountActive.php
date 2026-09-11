<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            return $next($request);
        }

        // Only check for non-admin roles that can be soft-deleted
        $role = $user->role?->value;

        if (in_array($role, ['guru', 'parent'])) {
            // User model no longer has SoftDeletes trait — skip trashed check
            if (! in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive(\App\Models\User::class), true)) {
                return $next($request);
            }

            // Query fresh from DB — Auth::user() may be cached and not reflect trashed status
            $isTrashed = \App\Models\User::withoutGlobalScopes()
                ->where('id', $user->id)
                ->onlyTrashed()
                ->exists();

            if ($isTrashed) {
                $adminPhone = $this->getAdminPhone();
                $message = $this->buildWhatsAppMessage($user, $adminPhone);

                return response()->view('errors.account-deactivated', [
                    'adminPhone' => $adminPhone,
                    'message' => $message,
                    'userName' => $this->getUserDisplayName($user),
                ], 403);
            }
        }

        return $next($request);
    }

    private function getAdminPhone(): string
    {
        // Get the first admin user's phone number
        $adminPhone = DB::table('users')
            ->where('role', 'admin')
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->orderBy('id')
            ->value('phone');

        return $adminPhone ?? '';
    }

    private function getUserDisplayName($user): string
    {
        if ($user->role?->value === 'guru') {
            return $user->teacher?->full_name ?? $user->name ?? 'Guru';
        }

        if ($user->role?->value === 'parent') {
            return $user->name ?? 'Orang Tua';
        }

        return $user->name ?? '-';
    }

    private function buildWhatsAppMessage($user, string $adminPhone): string
    {
        $userName = $this->getUserDisplayName($user);
        $userPhone = $user->phone ?? '-';

        return "Halo kak, saya ingin minta tolong aktifkan akun kembali atas nama {$userName} dengan nomor telepon {$userPhone}. Terima kasih.";
    }
}
