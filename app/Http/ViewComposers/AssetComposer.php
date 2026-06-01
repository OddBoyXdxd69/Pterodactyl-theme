<?php

namespace Pterodactyl\Http\ViewComposers;

use Illuminate\View\View;
use Pterodactyl\Services\Helpers\AssetHashService;

class AssetComposer
{
    /**
     * AssetComposer constructor.
     */
    public function __construct(private AssetHashService $assetHashService)
    {
    }

    /**
     * Provide access to the asset service in the views.
     */
    public function compose(View $view): void
    {
        $view->with('asset', $this->assetHashService);
        $view->with('siteConfiguration', [
            'name' => config('app.name') ?? 'Pterodactyl',
            'locale' => config('app.locale') ?? 'en',
            'recaptcha' => [
                'enabled' => config('recaptcha.enabled', false),
                'siteKey' => config('recaptcha.website_key') ?? '',
            ],
            'theme' => [
                'logo' => config('pterodactyl.theme.login_logo') ?: '/assets/svgs/pterodactyl.svg',
                'footer' => config('pterodactyl.theme.login_footer') ?: '',
                'discord_url' => config('pterodactyl.theme.discord_url') ?: '',
            ],
            'plugins' => [
                'enabled' => (bool) config('pterodactyl.plugins.enabled', false),
                'nests' => array_filter(array_map('intval', explode(',', config('pterodactyl.plugins.nests', '')))),
            ],
            'versions' => [
                'enabled' => (bool) config('pterodactyl.versions.enabled', false),
                'nests' => array_filter(array_map('intval', explode(',', config('pterodactyl.versions.nests', '')))),
            ],
            'tickets' => [
                'enabled' => (bool) config('pterodactyl.tickets.enabled', true),
                'limit' => (int) config('pterodactyl.tickets.limit', 3),
                'message_limit' => (int) config('pterodactyl.tickets.message_limit', 10),
            ],
        ]);
    }
}
