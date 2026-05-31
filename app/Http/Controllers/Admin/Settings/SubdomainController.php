<?php

namespace Pterodactyl\Http\Controllers\Admin\Settings;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use Illuminate\Contracts\Console\Kernel;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

class SubdomainController extends Controller
{
    /**
     * SubdomainController constructor.
     */
    public function __construct(
        private AlertsMessageBag $alert,
        private Kernel $kernel,
        private SettingsRepositoryInterface $settings,
    ) {
    }

    /**
     * Render the subdomain config page.
     */
    public function index(): View
    {
        return view('admin.settings.subdomains');
    }

    /**
     * Handle subdomain settings update.
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'pterodactyl:subdomains:enabled' => 'required|in:0,1',
            'pterodactyl:subdomains:domains' => 'nullable|string',
            'pterodactyl:subdomains:limit' => 'required|integer|min:0',
            'pterodactyl:subdomains:cf_email' => 'nullable|email',
            'pterodactyl:subdomains:cf_key' => 'nullable|string',
            'pterodactyl:subdomains:cf_zone_id' => 'nullable|string',
        ]);

        $this->settings->set('settings::pterodactyl:subdomains:enabled', $request->input('pterodactyl:subdomains:enabled'));
        $this->settings->set('settings::pterodactyl:subdomains:domains', $request->input('pterodactyl:subdomains:domains') ?? '');
        $this->settings->set('settings::pterodactyl:subdomains:limit', $request->input('pterodactyl:subdomains:limit'));
        $this->settings->set('settings::pterodactyl:subdomains:cf_email', $request->input('pterodactyl:subdomains:cf_email') ?? '');
        $this->settings->set('settings::pterodactyl:subdomains:cf_key', $request->input('pterodactyl:subdomains:cf_key') ?? '');
        $this->settings->set('settings::pterodactyl:subdomains:cf_zone_id', $request->input('pterodactyl:subdomains:cf_zone_id') ?? '');

        $this->kernel->call('queue:restart');
        $this->alert->success('Subdomains settings have been updated successfully and the queue worker was restarted.')->flash();

        return redirect()->route('admin.subdomains');
    }
}
