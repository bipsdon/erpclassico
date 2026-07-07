<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    // ──────────────────────────────────────────────
    // Step 1 — Show "Forgot Password" form
    // ──────────────────────────────────────────────

    public function showForgotForm(): View
    {
        return view('auth.forgot-password');
    }

    // ──────────────────────────────────────────────
    // Step 2 — Generate token and display it (no email)
    //
    // This app is an internal tool without email configured.
    // Instead of emailing a link, we show the reset token directly
    // so an admin can pass it to the user via WhatsApp / phone.
    // ──────────────────────────────────────────────

    public function sendResetToken(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ], [
            'email.exists' => 'No account found with that email address.',
        ]);

        $token = Str::random(64);

        DB::table('password_reset_tokens')->upsert(
            [
                'email'      => $request->email,
                'token'      => Hash::make($token),
                'created_at' => now(),
            ],
            ['email'],
            ['token', 'created_at'],
        );

        // Surface the plain token to the admin so they can relay it manually.
        // In a real deployment with email configured, swap this for Mail::send().
        return back()->with([
            'reset_token' => $token,
            'reset_email' => $request->email,
        ]);
    }

    // ──────────────────────────────────────────────
    // Step 3 — Show "Reset Password" form
    // ──────────────────────────────────────────────

    public function showResetForm(Request $request): View
    {
        return view('auth.reset-password', [
            'token' => $request->query('token', ''),
            'email' => $request->query('email', ''),
        ]);
    }

    // ──────────────────────────────────────────────
    // Step 4 — Apply new password
    // ──────────────────────────────────────────────

    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'email'                 => ['required', 'email', 'exists:users,email'],
            'token'                 => ['required', 'string'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required'],
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (! $record) {
            return back()->withErrors(['token' => 'Invalid or expired reset token.']);
        }

        // Tokens expire after 60 minutes
        if (Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return back()->withErrors(['token' => 'This reset token has expired. Please request a new one.']);
        }

        if (! Hash::check($request->token, $record->token)) {
            return back()->withErrors(['token' => 'Invalid or expired reset token.']);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        $user->update(['password' => Hash::make($request->password)]);

        // Consume the token
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return redirect()->route('login')
            ->with('success', 'Password reset successfully. You can now sign in.');
    }
}
