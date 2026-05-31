@extends('layouts.admin')

@section('title')
    Subdomain Settings
@endsection

@section('content-header')
    <h1>Subdomain Settings<small>Configure root domains and DNS API for client subdomain setup.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Subdomains</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <div class="col-xs-12">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Subdomain Configuration</h3>
                </div>
                <form action="{{ route('admin.subdomains') }}" method="POST">
                    <div class="box-body">
                        <div class="row">
                            <div class="form-group col-md-12">
                                <label class="control-label">Enable Client Subdomain Creation</label>
                                <div>
                                    <select class="form-control" name="pterodactyl:subdomains:enabled">
                                        <option value="1" {{ old('pterodactyl:subdomains:enabled', config('pterodactyl.subdomains.enabled')) ? 'selected' : '' }}>Enabled</option>
                                        <option value="0" {{ !old('pterodactyl:subdomains:enabled', config('pterodactyl.subdomains.enabled')) ? 'selected' : '' }}>Disabled</option>
                                    </select>
                                    <p class="text-muted"><small>Allows clients to dynamically set up subdomains for their allocations from their dashboard.</small></p>
                                </div>
                            </div>
                            <div class="form-group col-md-12">
                                <label class="control-label">Allowed Root Domains</label>
                                <div>
                                    <textarea class="form-control" name="pterodactyl:subdomains:domains" rows="4" placeholder="play-mc.net&#10;mc-join.com&#10;yourdomain.com:zone_id_here">{{ old('pterodactyl:subdomains:domains', config('pterodactyl.subdomains.domains')) }}</textarea>
                                    <p class="text-muted"><small>Enter the root domains allowed for subdomains (<strong>one per line</strong>). You can optionally assign specific Zone IDs by appending it with a colon (e.g. <code>domain.com:zone_id</code>).</small></p>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-12">
                                <hr style="border-color: #444;" />
                                <h4>Cloudflare DNS Settings</h4>
                                <p class="text-muted">Enter your Cloudflare API credentials below to enable automatic DNS record creation when clients set up subdomains.</p>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="control-label">Cloudflare Account Email</label>
                                <div>
                                    <input type="email" class="form-control" name="pterodactyl:subdomains:cf_email" value="{{ old('pterodactyl:subdomains:cf_email', config('pterodactyl.subdomains.cf_email')) }}" placeholder="admin@domain.com" />
                                    <p class="text-muted"><small>The email address associated with your Cloudflare account.</small></p>
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="control-label">Cloudflare Global API Key / Token</label>
                                <div>
                                    <input type="password" class="form-control" name="pterodactyl:subdomains:cf_key" value="{{ old('pterodactyl:subdomains:cf_key', config('pterodactyl.subdomains.cf_key')) }}" placeholder="Cloudflare API Key or Token" />
                                    <p class="text-muted"><small>Your Cloudflare API Key or custom Token with DNS write permissions.</small></p>
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="control-label">Default Cloudflare Zone ID</label>
                                <div>
                                    <input type="text" class="form-control" name="pterodactyl:subdomains:cf_zone_id" value="{{ old('pterodactyl:subdomains:cf_zone_id', config('pterodactyl.subdomains.cf_zone_id')) }}" placeholder="Zone ID (32 characters)" />
                                    <p class="text-muted"><small>The default Zone ID where DNS records will be added if no specific Zone ID is set in the domains list.</small></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="box-footer">
                        {!! csrf_field() !!}
                        <button type="submit" name="_method" value="PATCH" class="btn btn-sm btn-primary pull-right">Save Settings</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
