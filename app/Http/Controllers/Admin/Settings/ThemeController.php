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
            'pterodactyl:theme:support_url' => 'nullable|url',
        ]);

        foreach ($request->only(['pterodactyl:theme:favicon', 'pterodactyl:theme:login_logo', 'pterodactyl:theme:login_footer', 'pterodactyl:theme:discord_url', 'pterodactyl:theme:support_url']) as $key => $value) {
            $this->settings->set('settings::' . $key, $value ?? '');
        }

        $this->kernel->call('queue:restart');
        $this->alert->success('Theme settings have been updated successfully and the queue worker was restarted.')->flash();

        return redirect()->route('admin.theme');
    }
}
