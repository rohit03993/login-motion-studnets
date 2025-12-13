<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function edit()
    {
        $data = [
            'aisensy_url' => Setting::get('aisensy_url', config('services.aisensy.url')),
            'aisensy_template_in' => Setting::get('aisensy_template_in', config('services.aisensy.template_in')),
            'aisensy_template_out' => Setting::get('aisensy_template_out', config('services.aisensy.template_out')),
        ];
        return view('settings.edit', $data);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'aisensy_url' => 'required|string',
            'aisensy_template_in' => 'required|string',
            'aisensy_template_out' => 'required|string',
        ]);

        Setting::set('aisensy_url', $data['aisensy_url']);
        Setting::set('aisensy_template_in', $data['aisensy_template_in']);
        Setting::set('aisensy_template_out', $data['aisensy_template_out']);

        return redirect()->back()->with('status', 'Settings updated');
    }
}

