<?php

namespace Pterodactyl\Models;

/**
 * Pterodactyl\Models\Subdomain.
 *
 * @property int $id
 * @property int $server_id
 * @property string $subdomain
 * @property string $domain
 * @property string $record_type
 * @property string $ip
 * @property int $port
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \Pterodactyl\Models\Server $server
 */
class Subdomain extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'subdomains';

    protected $fillable = [
        'server_id',
        'subdomain',
        'domain',
        'record_type',
        'ip',
        'port',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function (Subdomain $subdomain) {
            $settings = app(\Pterodactyl\Contracts\Repository\SettingsRepositoryInterface::class);
            $cfEmail = $settings->get('settings::pterodactyl:subdomains:cf_email', '');
            $cfKey = $settings->get('settings::pterodactyl:subdomains:cf_key', '');
            $cfZone = $settings->get('settings::pterodactyl:subdomains:cf_zone_id', '');

            if (!empty($cfEmail) && !empty($cfKey) && !empty($cfZone)) {
                $names = [];
                $fullSubdomain = "{$subdomain->subdomain}.{$subdomain->domain}";

                if ($subdomain->record_type === 'SRV') {
                    $names[] = "_minecraft._tcp.{$fullSubdomain}";
                    $names[] = "target-{$subdomain->subdomain}.{$subdomain->domain}";
                } else {
                    $names[] = $fullSubdomain;
                }

                foreach ($names as $name) {
                    try {
                        $response = \Illuminate\Support\Facades\Http::withHeaders([
                            'X-Auth-Email' => $cfEmail,
                            'Authorization' => "Bearer {$cfKey}",
                            'Content-Type' => 'application/json',
                        ])->get("https://api.cloudflare.com/client/v4/zones/{$cfZone}/dns_records", [
                            'name' => $name,
                        ]);

                        if ($response->successful()) {
                            $records = $response->json('result') ?? [];
                            foreach ($records as $record) {
                                $recordId = $record['id'] ?? null;
                                if ($recordId) {
                                    \Illuminate\Support\Facades\Http::withHeaders([
                                        'X-Auth-Email' => $cfEmail,
                                        'Authorization' => "Bearer {$cfKey}",
                                        'Content-Type' => 'application/json',
                                    ])->delete("https://api.cloudflare.com/client/v4/zones/{$cfZone}/dns_records/{$recordId}");
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error("Failed to delete Cloudflare DNS record for {$name}: " . $e->getMessage());
                    }
                }
            }
        });
    }

    public static array $validationRules = [
        'server_id' => 'required|numeric|exists:servers,id',
        'subdomain' => 'required|string|regex:/^[a-zA-Z0-9-]+$/|max:64',
        'domain' => 'required|string|max:191',
        'record_type' => 'required|string|in:A,CNAME,SRV',
        'ip' => 'required|string',
        'port' => 'required|numeric|between:1,65535',
    ];

    /**
     * Get the server associated with this subdomain.
     */
    public function server()
    {
        return $this->belongsTo(Server::class);
    }
}
