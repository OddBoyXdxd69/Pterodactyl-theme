<?php

namespace Pterodactyl\Http\Controllers\Admin\Settings;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Pterodactyl\Models\Nest;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use Illuminate\Contracts\Console\Kernel;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

class PluginsController extends Controller
{
    /**
     * PluginsController constructor.
     */
    public function __construct(
        private AlertsMessageBag $alert,
        private Kernel $kernel,
        private SettingsRepositoryInterface $settings,
    ) {
    }

    /**
     * Render the plugins config page.
     */
    public function index(): View
    {
        $nests = Nest::all();
        $allowedNests = array_filter(array_map('intval', explode(',', $this->settings->get('settings::pterodactyl:plugins:nests', ''))));

        return view('admin.settings.plugins', [
            'nests' => $nests,
            'allowedNests' => $allowedNests,
        ]);
    }

    /**
     * Handle plugins settings update.
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'pterodactyl:plugins:enabled' => 'required|in:0,1',
            'nests' => 'nullable|array',
            'nests.*' => 'integer|exists:nests,id',
        ]);

        $nestsString = implode(',', $request->input('nests', []));

        $this->settings->set('settings::pterodactyl:plugins:enabled', $request->input('pterodactyl:plugins:enabled'));
        $this->settings->set('settings::pterodactyl:plugins:nests', $nestsString);

        $this->kernel->call('queue:restart');
        $this->alert->success('Plugins downloader settings have been updated successfully and the queue worker was restarted.')->flash();

        return redirect()->route('admin.plugins');
    }
}
