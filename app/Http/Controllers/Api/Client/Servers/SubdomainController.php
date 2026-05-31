<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Subdomain;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Illuminate\Support\Facades\Http;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

class SubdomainController extends ClientApiController
{
    /**
     * SubdomainController constructor.
     */
    public function __construct(private SettingsRepositoryInterface $settings)
    {
        parent::__construct();
    }

    /**
     * Get existing subdomains and configuration options.
     */
    public function index(Request $request, Server $server): JsonResponse
    {
        $subdomains = Subdomain::where('server_id', $server->id)->get();

        $domainsRaw = $this->settings->get('settings::pterodactyl:subdomains:domains', '');
        $allowedDomains = array_filter(array_map('trim', preg_split('/[\n,]+/', $domainsRaw)));

        // Get default allocation IP and port
        $allocation = $server->allocation;
        $ip = $allocation ? $allocation->ip : '127.0.0.1';
        $port = $allocation ? $allocation->port : 25565;

        $globalLimit = (int) $this->settings->get('settings::pterodactyl:subdomains:limit', 0);
        $serverLimit = is_null($server->subdomain_limit) ? $globalLimit : (int) $server->subdomain_limit;

        return new JsonResponse([
            'subdomains' => $subdomains,
            'allowed_domains' => array_values($allowedDomains),
            'default_ip' => $ip,
            'default_port' => $port,
            'subdomain_limit' => $serverLimit,
        ]);
    }

    /**
     * Create a new subdomain for the server.
     */
    public function store(Request $request, Server $server): JsonResponse
    {
        $domainsRaw = $this->settings->get('settings::pterodactyl:subdomains:domains', '');
        $allowedDomains = array_filter(array_map('trim', preg_split('/[\n,]+/', $domainsRaw)));
        $allowedDomainsStr = implode(',', $allowedDomains);

        $request->validate([
            'subdomain' => 'required|string|regex:/^[a-zA-Z0-9-]+$/|max:63',
            'domain' => 'required|string|in:' . $allowedDomainsStr,
            'record_type' => 'required|string|in:A,CNAME,SRV',
        ]);

        $globalLimit = (int) $this->settings->get('settings::pterodactyl:subdomains:limit', 0);
        $serverLimit = is_null($server->subdomain_limit) ? $globalLimit : (int) $server->subdomain_limit;

        if ($serverLimit > 0) {
            $currentCount = Subdomain::where('server_id', $server->id)->count();
            if ($currentCount >= $serverLimit) {
                return new JsonResponse([
                    'errors' => [
                        'subdomain' => ["This server has reached its limit of {$serverLimit} subdomains."]
                    ]
                ], 422);
            }
        }

        $subdomainPrefix = strtolower($request->input('subdomain'));
        $rootDomain = $request->input('domain');
        $recordType = $request->input('record_type');

        // Check if prefix+domain already exists in DB
        $exists = Subdomain::where('subdomain', $subdomainPrefix)
            ->where('domain', $rootDomain)
            ->exists();

        if ($exists) {
            return new JsonResponse([
                'errors' => [
                    'subdomain' => ['This subdomain is already taken.']
                ]
            ], 422);
        }

        $allocation = $server->allocation;
        $ip = $allocation ? $allocation->ip : '127.0.0.1';
        $port = $allocation ? $allocation->port : 25565;

        // Try cloudflare api if configured
        $cfEmail = $this->settings->get('settings::pterodactyl:subdomains:cf_email', '');
        $cfKey = $this->settings->get('settings::pterodactyl:subdomains:cf_key', '');
        $cfZone = $this->settings->get('settings::pterodactyl:subdomains:cf_zone_id', '');

        $dnsCreated = false;
        $dnsError = null;

        if (!empty($cfEmail) && !empty($cfKey) && !empty($cfZone)) {
            try {
                $fullSubdomain = "{$subdomainPrefix}.{$rootDomain}";
                
                if ($recordType === 'SRV') {
                    // Minecraft SRV record
                    $body = [
                        'type' => 'SRV',
                        'name' => "_minecraft._tcp.{$fullSubdomain}",
                        'data' => [
                            'service' => '_minecraft',
                            'proto' => '_tcp',
                            'name' => $subdomainPrefix,
                            'priority' => 0,
                            'weight' => 5,
                            'port' => (int)$port,
                            'target' => $rootDomain,
                        ],
                        'ttl' => 120,
                    ];
                    
                    // 1. Create target A record
                    $aTarget = "target-{$subdomainPrefix}.{$rootDomain}";
                    Http::withHeaders([
                        'X-Auth-Email' => $cfEmail,
                        'Authorization' => "Bearer {$cfKey}",
                        'Content-Type' => 'application/json',
                    ])->post("https://api.cloudflare.com/client/v4/zones/{$cfZone}/dns_records", [
                        'type' => 'A',
                        'name' => $aTarget,
                        'content' => $ip,
                        'ttl' => 120,
                        'proxied' => false,
                    ]);
                    
                    // 2. Point SRV to target A record
                    $body['data']['target'] = $aTarget;
                } else {
                    // A or CNAME record
                    $body = [
                        'type' => $recordType,
                        'name' => $fullSubdomain,
                        'content' => $ip,
                        'ttl' => 120,
                        'proxied' => false,
                    ];
                }

                $response = Http::withHeaders([
                    'X-Auth-Email' => $cfEmail,
                    'Authorization' => "Bearer {$cfKey}",
                    'Content-Type' => 'application/json',
                ])->post("https://api.cloudflare.com/client/v4/zones/{$cfZone}/dns_records", $body);

                if ($response->successful()) {
                    $dnsCreated = true;
                } else {
                    $dnsError = $response->json('errors.0.message') ?? 'Cloudflare API call failed.';
                }
            } catch (\Exception $e) {
                $dnsError = $e->getMessage();
            }
        }

        // Save locally in DB
        $subdomain = Subdomain::create([
            'server_id' => $server->id,
            'subdomain' => $subdomainPrefix,
            'domain' => $rootDomain,
            'record_type' => $recordType,
            'ip' => $ip,
            'port' => $port,
        ]);

        return new JsonResponse([
            'subdomain' => $subdomain,
            'dns_synced' => $dnsCreated,
            'dns_error' => $dnsError,
        ]);
    }

    /**
     * Delete a subdomain.
     */
    public function delete(Request $request, Server $server, $subdomainId): JsonResponse
    {
        $subdomain = Subdomain::where('server_id', $server->id)
            ->where('id', $subdomainId)
            ->firstOrFail();

        $subdomain->delete();

        return new JsonResponse([], 204);
    }
}
