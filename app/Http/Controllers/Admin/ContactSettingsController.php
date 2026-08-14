<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContactSettingsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/ContactSettings', [
            'contact_link' => Setting::get('contact.link', 'mailto:support@zavelyx.com'),
        ]);
    }

    public function save(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'contact_link' => [
                'required',
                'string',
                'max:500',
                function ($attribute, $value, $fail) {
                    $allowed = ['mailto:', 'https://', 'http://'];
                    $valid = false;
                    foreach ($allowed as $prefix) {
                        if (str_starts_with($value, $prefix)) {
                            $valid = true;
                            break;
                        }
                    }
                    if (!$valid) {
                        $fail('The contact link must start with mailto:, https://, or http://.');
                    }
                },
            ],
        ]);

        Setting::set('contact.link', $validated['contact_link']);

        return back()->with('success', 'Contact settings saved successfully.');
    }
}
