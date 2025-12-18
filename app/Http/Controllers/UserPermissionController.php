<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserClassPermission;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserPermissionController extends Controller
{
    public function edit(): View
    {
        // Include all users (no is_super_admin column in current schema)
        $users = User::orderBy('name')->get();
        $courses = Course::orderBy('name')->get();
        $permissionList = User::with('classPermissions')->orderBy('name')->get();
        return view('users.permissions', compact('users', 'courses', 'permissionList'));
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'classes' => 'array',
            'classes.*' => 'string',
            'can_view_employees' => 'nullable|boolean',
        ]);

        $user = User::findOrFail($request->input('user_id'));

        // Update employee permission
        $user->can_view_employees = $request->boolean('can_view_employees');
        $user->save();

        // Sync class permissions
        $classes = $request->input('classes', []);
        UserClassPermission::where('user_id', $user->id)->delete();
        foreach ($classes as $className) {
            UserClassPermission::create([
                'user_id' => $user->id,
                'class_name' => $className,
                'can_mark' => true, // view implies mark in/out
            ]);
        }

        return back()->with('success', 'Permissions updated.');
    }
}
