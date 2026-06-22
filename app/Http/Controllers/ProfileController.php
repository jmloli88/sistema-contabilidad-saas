<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\TelegramUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Link a Telegram account to the current user.
     */
    public function linkTelegram(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'telegram_auth_token' => 'required|string',
        ]);

        $telegramUser = TelegramUser::where('auth_token', $validated['telegram_auth_token'])->first();

        if (!$telegramUser) {
            return back()->withErrors(['telegram_auth_token' => 'Código inválido.']);
        }

        $telegramUser->update([
            'user_id' => $request->user()->id,
            'is_authenticated' => true,
            'auth_token' => null,
        ]);

        return back()->with('status', 'telegram-linked');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
