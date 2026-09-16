<?php
// app/Http/Controllers/SmtpSettingController.php

namespace App\Http\Controllers;

use App\Models\SmtpSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SmtpSettingController extends Controller
{
    public function list()
    {
        $smtpSettings = SmtpSetting::whereNull('deleted_at')
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
        return view('admin.smtp.list', compact('smtpSettings'));
    }

    public function create()
    {
        return view('admin.smtp.form');
    }

    public function store(Request $request)
    {
        $request->validate([
            'host' => 'required|string',
            'port' => 'required|integer|min:1|max:65535',
            'encryption' => 'nullable|in:tls,ssl',
            'username' => 'required|email',
            'password' => 'required|string',
            'from_address' => 'required|email',
            'from_name' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
            'is_default' => 'boolean'
        ]);

        $data = $request->only([
            'host', 'port', 'encryption', 'username', 
            'password', 'from_address', 'from_name', 'status'
        ]);
        
        $data['is_default'] = $request->has('is_default') ? true : false;

        // If setting as default, ensure only one default exists
        if ($data['is_default'] && $data['status'] === 'active') {
            SmtpSetting::where('is_default', true)->update(['is_default' => false]);
        }

        SmtpSetting::create($data);

        return redirect()->route('smtp.index')
            ->with('success', 'SMTP setting added successfully!');
    }

    public function edit($id)
    {
        $smtpSetting = SmtpSetting::findOrFail($id);
        return view('admin.smtp.form', compact('smtpSetting'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'host' => 'required|string',
            'port' => 'required|integer|min:1|max:65535',
            'encryption' => 'nullable|in:tls,ssl',
            'username' => 'required|email',
            'password' => 'nullable|string',
            'from_address' => 'required|email',
            'from_name' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
            'is_default' => 'boolean'
        ]);

        $smtpSetting = SmtpSetting::findOrFail($id);
        $data = $request->only([
            'host', 'port', 'encryption', 'username', 
            'from_address', 'from_name', 'status'
        ]);

        // Only update password if provided
        if ($request->filled('password')) {
            $data['password'] = $request->password;
        }

        $data['is_default'] = $request->has('is_default') ? true : false;

        // If setting as default, ensure only one default exists
        if ($data['is_default'] && $data['status'] === 'active') {
            SmtpSetting::where('id', '!=', $id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        $smtpSetting->update($data);

        return redirect()->route('smtp.index')
            ->with('success', 'SMTP setting updated successfully!');
    }

    public function destroy($id)
    {
        $smtpSetting = SmtpSetting::findOrFail($id);
        
        // Prevent deletion of default active setting
        if ($smtpSetting->is_default && $smtpSetting->status === 'active') {
            return redirect()->route('smtp.index')
                ->with('error', 'Cannot delete the default active SMTP setting!');
        }
        
        $smtpSetting->delete();
        
        return redirect()->route('smtp.index')
            ->with('success', 'SMTP setting deleted successfully!');
    }

    // Test SMTP Configuration
    public function test(Request $request, $id)
    {
        try {
            $smtpSetting = SmtpSetting::findOrFail($id);
            
            // Apply the configuration temporarily
            $smtpSetting->applyConfig();
            
            // Send test email
            Mail::raw('This is a test email from your Laravel application.', function ($message) use ($smtpSetting) {
                $message->to($smtpSetting->username)
                        ->subject('Test SMTP Configuration');
            });
            
            return redirect()->route('smtp.index')
                ->with('success', 'Test email sent successfully to ' . $smtpSetting->username);
                
        } catch (\Exception $e) {
            Log::error('SMTP Test Failed: ' . $e->getMessage());
            return redirect()->route('smtp.index')
                ->with('error', 'Failed to send test email: ' . $e->getMessage());
        }
    }
}