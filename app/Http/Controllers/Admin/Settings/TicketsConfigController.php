<?php

namespace Pterodactyl\Http\Controllers\Admin\Settings;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use Illuminate\Contracts\Console\Kernel;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

class TicketsConfigController extends Controller
{
    /**
     * TicketsConfigController constructor.
     */
    public function __construct(
        private AlertsMessageBag $alert,
        private Kernel $kernel,
        private SettingsRepositoryInterface $settings,
    ) {
    }

    /**
     * Render the tickets config page.
     */
    public function index(): View
    {
        return view('admin.settings.tickets.config');
    }

    /**
     * Handle tickets config update.
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'pterodactyl:tickets:enabled' => 'required|in:0,1',
            'pterodactyl:tickets:limit' => 'required|integer|min:1|max:100',
            'pterodactyl:tickets:message_limit' => 'required|integer|min:1|max:1000',
            'pterodactyl:tickets:clear_inactive_days' => 'required|integer|min:1|max:365',
            'pterodactyl:tickets:clear_closed_days' => 'required|integer|min:1|max:365',
        ]);

        $this->settings->set('settings::pterodactyl:tickets:enabled', $request->input('pterodactyl:tickets:enabled'));
        $this->settings->set('settings::pterodactyl:tickets:limit', $request->input('pterodactyl:tickets:limit'));
        $this->settings->set('settings::pterodactyl:tickets:message_limit', $request->input('pterodactyl:tickets:message_limit'));
        $this->settings->set('settings::pterodactyl:tickets:clear_inactive_days', $request->input('pterodactyl:tickets:clear_inactive_days'));
        $this->settings->set('settings::pterodactyl:tickets:clear_closed_days', $request->input('pterodactyl:tickets:clear_closed_days'));

        $this->kernel->call('queue:restart');
        $this->alert->success('Support ticket system configurations have been updated successfully and the queue worker was restarted.')->flash();

        return redirect()->route('admin.tickets.config');
    }
}
