<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetLinkController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/ForgotPassword', [
            'meta' => ['title' => 'Lupa Kata Sandi'],
        ]);
    }

    /**
     * Always responds with the same message regardless of whether the email
     * exists, to avoid leaking which addresses are registered.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        Password::sendResetLink($validated);

        return back()->with('success', 'Jika email tersebut terdaftar, tautan untuk mengatur ulang kata sandi telah dikirim.');
    }
}
