<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    //

    /**
     * Show password change form
     */
    public function showChangePassword()
    {
        return view('profile.change-password');
    }

    /**
     * Update password
     */
    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = auth()->user();

        // Verify current password
        if (!Hash::check($validated['current_password'], $user->password)) {
            return back()
                ->withErrors(['current_password' => 'Current password is incorrect.'])
                ->withInput();
        }

        // Update password and track change
        $user->password = Hash::make($validated['password']);
        $user->password_changed_at = now();
        $user->save();

        return redirect()->route('profile.change-password')
            ->with('success', 'Password changed successfully.');
    }
}

