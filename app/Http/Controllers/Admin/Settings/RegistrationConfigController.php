<?php

namespace Pterodactyl\Http\Controllers\Admin\Settings;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use Illuminate\Contracts\Console\Kernel;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

class RegistrationConfigController extends Controller
{
    /**
     * RegistrationConfigController constructor.
     */
    public function __construct(
        private AlertsMessageBag $alert,
        private Kernel $kernel,
        private SettingsRepositoryInterface $settings,
    ) {
    }

    /**
     * Render the Registration Config settings page.
     */
    public function index(): View
    {
        return view('admin.settings.registration');
    }

    /**
     * Update the registration settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'pterodactyl:registration:enabled' => 'required|in:0,1',
            'pterodactyl:registration:otp' => 'nullable|in:0,1',
        ]);

        $this->settings->set('settings::pterodactyl:registration:enabled', $request->input('pterodactyl:registration:enabled'));
        $this->settings->set('settings::pterodactyl:registration:otp', $request->input('pterodactyl:registration:otp', '0'));

        $this->kernel->call('queue:restart');
        $this->alert->success('User registration configurations have been updated successfully and the queue worker was restarted.')->flash();

        return redirect()->route('admin.registration');
    }
}
