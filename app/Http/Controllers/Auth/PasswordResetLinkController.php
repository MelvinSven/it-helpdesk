<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        // Self-service reset is restricted to admin accounts. The email must
        // belong to an active admin; otherwise we report it back so the user
        // knows the address wasn't recognised.
        // NOTE: this intentionally reveals whether an email is a valid admin
        // account (account enumeration). Acceptable for this internal, admin-only
        // tool; revert to a neutral response if the login is ever exposed publicly.
        $user = User::where('email', $request->string('email')->toString())->first();

        if (! $user || ! $user->isAdmin() || ! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => [__('Tidak ada akun admin aktif dengan alamat email tersebut.')],
            ]);
        }

        Password::sendResetLink($request->only('email'));

        return back()->with('status', __('Tautan atur ulang kata sandi telah dikirim ke email Anda.'));
    }
}
