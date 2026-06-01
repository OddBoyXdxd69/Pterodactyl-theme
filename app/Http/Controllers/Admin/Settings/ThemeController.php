<?php

namespace Pterodactyl\Http\Controllers\Admin\Settings;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use Illuminate\Contracts\Console\Kernel;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

class ThemeController extends Controller
{
    /**
     * ThemeController constructor.
     */
    public function __construct(
        private AlertsMessageBag $alert,
        private Kernel $kernel,
        private SettingsRepositoryInterface $settings,
    ) {
    }

    /**
     * Render the UI for Theme and Configuration settings.
     */
    public function index(): View
    {
        return view('admin.settings.theme');
    }

    /**
     * Handle theme settings update.
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'pterodactyl:theme:favicon' => 'nullable|string',
            'pterodactyl:theme:login_logo' => 'nullable|string',
            'pterodactyl:theme:login_footer' => 'nullable|string',
            'pterodactyl:theme:discord_url' => 'nullable|url',
            'pterodactyl:registration:enabled' => 'required|in:0,1',
            'favicon_file' => 'nullable|image|mimes:png,jpg,jpeg,ico,gif,svg|max:2048',
            'login_logo_file' => 'nullable|image|mimes:png,jpg,jpeg,gif,svg|max:2048',
        ]);

        if ($request->hasFile('favicon_file')) {
            $file = $request->file('favicon_file');
            $filename = 'favicon_' . time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('assets/branding'), $filename);
            $faviconUrl = '/assets/branding/' . $filename;
            $this->settings->set('settings::pterodactyl:theme:favicon', $faviconUrl);
        }

        if ($request->hasFile('login_logo_file')) {
            $file = $request->file('login_logo_file');
            $filename = 'login_logo_' . time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('assets/branding'), $filename);
            $logoUrl = '/assets/branding/' . $filename;
            $this->settings->set('settings::pterodactyl:theme:login_logo', $logoUrl);
        }

        $keys = ['pterodactyl:theme:login_footer', 'pterodactyl:theme:discord_url', 'pterodactyl:registration:enabled'];
        if (!$request->hasFile('favicon_file')) {
            $keys[] = 'pterodactyl:theme:favicon';
        }
        if (!$request->hasFile('login_logo_file')) {
            $keys[] = 'pterodactyl:theme:login_logo';
        }

        foreach ($request->only($keys) as $key => $value) {
            $this->settings->set('settings::' . $key, $value ?? '');
        }

        $this->kernel->call('queue:restart');
        $this->alert->success('Theme settings have been updated successfully and the queue worker was restarted.')->flash();

        return redirect()->route('admin.theme');
    }
}
