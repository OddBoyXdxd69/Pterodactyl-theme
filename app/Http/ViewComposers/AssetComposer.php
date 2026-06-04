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
                'provider' => config('recaptcha.provider', 'recaptcha'),
                'siteKey' => config('recaptcha.website_key') ?? '',
            ],
            'theme' => [
                'logo' => trim(config('pterodactyl.theme.login_logo', '')) ?: '/assets/svgs/pterodactyl.svg',
                'footer' => config('pterodactyl.theme.login_footer') ?: '',
                'discord_url' => trim(config('pterodactyl.theme.discord_url', '')) ?: 'https://discord.gg',
                'announcement_enabled' => (bool) config('pterodactyl.theme.announcement_enabled', false),
                'announcement_text' => config('pterodactyl.theme.announcement_text') ?: '',
                'announcement_type' => config('pterodactyl.theme.announcement_type') ?: 'info',
                'announcement_dismissible' => (bool) config('pterodactyl.theme.announcement_dismissible', true),
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
            'registration' => [
                'enabled' => (bool) config('pterodactyl.registration.enabled', false),
            ],
            'discord' => [
                'enabled' => (bool) config('pterodactyl.discord.enabled', false),
            ],
        ]);
    }
}
