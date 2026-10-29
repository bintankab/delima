<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\UserModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VerifyEmailController extends Controller
{
    /**
     * The link can't rely on an authenticated session — the user isn't allowed
     * to log in until an admin approves the account, which only happens after
     * this step. The signed URL (validated by the `signed` middleware) is what
     * makes it safe to trust the `id`/`hash` route parameters directly.
     */
    public function __invoke(Request $request, int $id, string $hash): RedirectResponse
    {
        $user = UserModel::findOrFail($id);

        abort_unless(hash_equals(sha1($user->getEmailForVerification()), $hash), 403);

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return redirect()
            ->route('login')
            ->with('success', 'Email berhasil diverifikasi. Akun Anda kini menunggu persetujuan admin sebelum dapat masuk.');
    }
}
