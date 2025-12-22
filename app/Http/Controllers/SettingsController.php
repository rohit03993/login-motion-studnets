<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function edit()
    {
        // Ensure only Super Admin can access settings
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Access denied. Only Super Administrators can access settings.');
        }
        
        $data = [
            'aisensy_url' => Setting::get('aisensy_url', config('services.aisensy.url')),
            'aisensy_template_in' => Setting::get('aisensy_template_in', config('services.aisensy.template_in')),
            'aisensy_template_out' => Setting::get('aisensy_template_out', config('services.aisensy.template_out')),
            'aisensy_template_manual_in' => Setting::get('aisensy_template_manual_in', config('services.aisensy.template_manual_in')),
            'aisensy_template_manual_out' => Setting::get('aisensy_template_manual_out', config('services.aisensy.template_manual_out')),
            'auto_out_enabled' => Setting::get('auto_out_enabled', '1'), // Default enabled
            'auto_out_time' => Setting::get('auto_out_time', '19:00'), // Default 7 PM
            'company_logo' => Setting::get('company_logo'),
        ];
        return view('settings.edit', $data);
    }

    public function update(Request $request)
    {
        // Ensure only Super Admin can update settings
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Access denied. Only Super Administrators can update settings.');
        }
        
        $data = $request->validate([
            'aisensy_url' => 'required|string',
            'aisensy_template_in' => 'required|string',
            'aisensy_template_out' => 'required|string',
            'aisensy_template_manual_in' => 'required|string',
            'aisensy_template_manual_out' => 'required|string',
            'auto_out_time' => 'nullable|date_format:H:i',
            'company_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        Setting::set('aisensy_url', $data['aisensy_url']);
        Setting::set('aisensy_template_in', $data['aisensy_template_in']);
        Setting::set('aisensy_template_out', $data['aisensy_template_out']);
        Setting::set('aisensy_template_manual_in', $data['aisensy_template_manual_in']);
        Setting::set('aisensy_template_manual_out', $data['aisensy_template_manual_out']);
        Setting::set('auto_out_enabled', $request->has('auto_out_enabled') && $request->input('auto_out_enabled') === '1' ? '1' : '0');
        Setting::set('auto_out_time', $data['auto_out_time'] ?? '19:00');

        // Handle logo upload (admin only)
        if ($request->hasFile('company_logo') && auth()->user()->isSuperAdmin()) {
            $oldLogo = Setting::get('company_logo');
            if ($oldLogo && file_exists(storage_path('app/public/' . $oldLogo))) {
                unlink(storage_path('app/public/' . $oldLogo));
            }
            
            $logoPath = $request->file('company_logo')->store('logos', 'public');
            Setting::set('company_logo', $logoPath);
        }

        // Handle logo removal
        if ($request->has('remove_logo') && auth()->user()->isSuperAdmin()) {
            $oldLogo = Setting::get('company_logo');
            if ($oldLogo && file_exists(storage_path('app/public/' . $oldLogo))) {
                unlink(storage_path('app/public/' . $oldLogo));
            }
            Setting::set('company_logo', null);
        }

        return redirect()->back()->with('status', 'Settings updated');
    }
}

