@extends('layouts.admin')

@section('title')
    Subdomain Settings
@endsection

@section('content-header')
    <h1>Subdomain Settings<small>Configure root domains for client subdomain setup.</small></h1>
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
                                    <textarea class="form-control" name="pterodactyl:subdomains:domains" rows="8" placeholder="play-mc.net&#10;mc-join.com&#10;yourdomain.com">{{ old('pterodactyl:subdomains:domains', config('pterodactyl.subdomains.domains')) }}</textarea>
                                    <p class="text-muted"><small>Enter the root domains that clients are allowed to choose from when creating subdomains. Enter <strong>one domain per line</strong>.</small></p>
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
