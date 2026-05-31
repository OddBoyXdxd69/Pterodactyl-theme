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

class VersionsController extends Controller
{
    /**
     * VersionsController constructor.
     */
    public function __construct(
        private AlertsMessageBag $alert,
        private Kernel $kernel,
        private SettingsRepositoryInterface $settings,
    ) {
    }

    /**
     * Render the versions downloader config page.
     */
    public function index(): View
    {
        $nests = Nest::all();
        $allowedNests = array_filter(array_map('intval', explode(',', $this->settings->get('settings::pterodactyl:versions:nests', ''))));

        return view('admin.settings.versions', [
            'nests' => $nests,
            'allowedNests' => $allowedNests,
        ]);
    }

    /**
     * Handle versions settings update.
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'pterodactyl:versions:enabled' => 'required|in:0,1',
            'nests' => 'nullable|array',
            'nests.*' => 'integer|exists:nests,id',
        ]);

        $nestsString = implode(',', $request->input('nests', []));

        $this->settings->set('settings::pterodactyl:versions:enabled', $request->input('pterodactyl:versions:enabled'));
        $this->settings->set('settings::pterodactyl:versions:nests', $nestsString);

        $this->kernel->call('queue:restart');
        $this->alert->success('Versions downloader settings have been updated successfully and the queue worker was restarted.')->flash();

        return redirect()->route('admin.versions');
    }
}
