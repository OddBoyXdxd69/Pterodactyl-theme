<?php

namespace Pterodactyl\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Pterodactyl\Models\User;
use Illuminate\Support\Str;
use Illuminate\Auth\AuthManager;
use Illuminate\Support\Facades\Event;
use Pterodactyl\Events\Auth\DirectLogin;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Users\UserCreationService;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class DiscordController extends Controller
{
    /**
     * DiscordController constructor.
     */
    public function __construct(
        private AuthManager $auth,
        private UserCreationService $creator,
    ) {
    }

    /**
     * Redirect the user to the Discord OAuth2 authorization page.
     */
    public function redirect(Request $request)
    {
        if (!config('pterodactyl.discord.enabled', false)) {
            return redirect('/auth/login?error=discord_disabled');
        }

        $clientId = config('pterodactyl.discord.client_id');
        if (empty($clientId)) {
            return redirect('/auth/login?error=discord_not_configured');
        }

        $state = Str::random(40);
        $request->session()->put('discord_oauth_state', $state);

        $query = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => url('/auth/discord/callback'),
            'response_type' => 'code',
            'scope' => 'identify email',
            'state' => $state,
        ]);

        return redirect('https://discord.com/api/oauth2/authorize?' . $query);
    }

    /**
     * Handle the callback from Discord OAuth2.
     */
    public function callback(Request $request)
    {
        if (!config('pterodactyl.discord.enabled', false)) {
            return redirect('/auth/login?error=discord_disabled');
        }

        $clientId = config('pterodactyl.discord.client_id');
        $clientSecret = config('pterodactyl.discord.client_secret');
        if (empty($clientId) || empty($clientSecret)) {
            return redirect('/auth/login?error=discord_not_configured');
        }

        $code = $request->input('code');
        if (empty($code)) {
            return redirect('/auth/login?error=discord_code_missing');
        }

        $sessionState = $request->session()->pull('discord_oauth_state');
        if (empty($request->input('state')) || $request->input('state') !== $sessionState) {
            return redirect('/auth/login?error=discord_invalid_state');
        }

        try {
            $client = new Client([
                'timeout' => 5.0,
                'connect_timeout' => 3.0,
                'curl' => [
                    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                ]
            ]);

            $response = $client->post('https://discord.com/api/oauth2/token', [
                'form_params' => [
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'redirect_uri' => url('/auth/discord/callback'),
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $accessToken = $data['access_token'] ?? null;
            if (empty($accessToken)) {
                return redirect('/auth/login?error=discord_token_failed');
            }
        } catch (\Exception $e) {
            Log::error('Discord OAuth exchange failed: ' . $e->getMessage());
            return redirect('/auth/login?error=discord_exchange_error');
        }

        try {
            $response = $client->get('https://discord.com/api/users/@me', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
            ]);

            $discordUser = json_decode($response->getBody()->getContents(), true);
            $email = $discordUser['email'] ?? null;
            if (empty($email)) {
                return redirect('/auth/login?error=discord_email_missing');
            }
            if (empty($discordUser['verified']) || !$discordUser['verified']) {
                return redirect('/auth/login?error=discord_email_unverified');
            }
        } catch (\Exception $e) {
            Log::error('Discord OAuth profile fetch failed: ' . $e->getMessage());
            return redirect('/auth/login?error=discord_profile_error');
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            if (!config('pterodactyl.registration.enabled', false)) {
                return redirect('/auth/login?error=discord_registration_disabled');
            }

            // Generate username conforming to username rule constraints
            $username = preg_replace('/[^a-zA-Z0-9._-]/', '', $discordUser['username']);
            if (strlen($username) < 3) {
                $username = 'discord_' . Str::random(6);
            }
            $originalUsername = $username;
            while (User::where('username', $username)->exists()) {
                $username = substr($originalUsername, 0, 24) . Str::random(4);
            }

            // Generate random password
            $password = Str::random(16);
            
            // Set up names
            $firstName = $discordUser['global_name'] ?? $discordUser['username'] ?? 'Discord';
            $lastName = 'User';

            try {
                $user = $this->creator->handle([
                    'email' => $email,
                    'username' => $username,
                    'name_first' => $firstName,
                    'name_last' => $lastName,
                    'password' => $password,
                    'root_admin' => false,
                ]);
            } catch (\Exception $e) {
                Log::error('Discord OAuth user creation failed: ' . $e->getMessage());
                return redirect('/auth/login?error=discord_creation_error');
            }
        }

        // Complete the authentication session login
        $this->auth->guard()->login($user, true);
        Event::dispatch(new DirectLogin($user, true));

        return redirect('/');
    }
}
