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
