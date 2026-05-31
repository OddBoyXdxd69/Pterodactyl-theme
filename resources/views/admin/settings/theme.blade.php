@extends('layouts.admin')

@section('title')
    Theme Settings
@endsection

@section('content-header')
    <h1>Theme & Configuration<small>Customize the look and branding of your panel.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Theme Settings</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <div class="col-xs-12">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Theme Settings</h3>
                </div>
                <form action="{{ route('admin.theme') }}" method="POST">
                    <div class="box-body">
                        <div class="row">
                            <div class="form-group col-md-4">
                                <label class="control-label">Favicon URL</label>
                                <div>
                                    <input type="text" class="form-control" name="pterodactyl:theme:favicon" value="{{ old('pterodactyl:theme:favicon', config('pterodactyl.theme.favicon')) }}" placeholder="/favicons/favicon.ico" />
                                    <p class="text-muted"><small>The URL of your custom favicon icon file.</small></p>
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="control-label">Login Page Logo URL</label>
                                <div>
                                    <input type="text" class="form-control" name="pterodactyl:theme:login_logo" value="{{ old('pterodactyl:theme:login_logo', config('pterodactyl.theme.login_logo')) }}" placeholder="/assets/svgs/pterodactyl.svg" />
                                    <p class="text-muted"><small>The URL of the logo displayed on the login and auth pages.</small></p>
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="control-label">Login Page Footer Text</label>
                                <div>
                                    <input type="text" class="form-control" name="pterodactyl:theme:login_footer" value="{{ old('pterodactyl:theme:login_footer', config('pterodactyl.theme.login_footer')) }}" placeholder="Pterodactyl Software" />
                                    <p class="text-muted"><small>Branding and copyright footer text displayed at the bottom of the login pages.</small></p>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-12">
                                <hr style="border-color: #444;" />
                            </div>
                            <div class="form-group col-md-6">
                                <label class="control-label">Discord Server Invite URL</label>
                                <div>
                                    <input type="text" class="form-control" name="pterodactyl:theme:discord_url" value="{{ old('pterodactyl:theme:discord_url', config('pterodactyl.theme.discord_url')) }}" placeholder="https://discord.gg/yourinvite" />
                                    <p class="text-muted"><small>The Discord link displayed alongside your panel name in the client sidebar.</small></p>
                                </div>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="control-label">Support Server / Ticket URL</label>
                                <div>
                                    <input type="text" class="form-control" name="pterodactyl:theme:support_url" value="{{ old('pterodactyl:theme:support_url', config('pterodactyl.theme.support_url')) }}" placeholder="https://hostmc.in/support" />
                                    <p class="text-muted"><small>The Support link (represented by a headset icon) displayed in the client header.</small></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="box-footer">
                        {!! csrf_field() !!}
                        <button type="submit" name="_method" value="PATCH" class="btn btn-sm btn-primary pull-right">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
