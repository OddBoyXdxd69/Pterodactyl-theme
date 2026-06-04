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
                <form action="{{ route('admin.theme') }}" method="POST" enctype="multipart/form-data">
                    <div class="box-body">
                        <div class="row">
                            <div class="form-group col-md-4">
                                <label class="control-label">Favicon URL</label>
                                <div>
                                    <input type="text" class="form-control" name="pterodactyl:theme:favicon" value="{{ old('pterodactyl:theme:favicon', config('pterodactyl.theme.favicon')) }}" placeholder="/favicons/favicon.ico" />
                                    <p class="text-muted"><small>The URL of your custom favicon icon file.</small></p>
                                </div>
                                <div style="margin-top: 10px;">
                                    <label class="control-label">Or Upload Favicon</label>
                                    <input type="file" class="form-control" name="favicon_file" accept="image/*" />
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="control-label">Login Page Logo URL</label>
                                <div>
                                    <input type="text" class="form-control" name="pterodactyl:theme:login_logo" value="{{ old('pterodactyl:theme:login_logo', config('pterodactyl.theme.login_logo')) }}" placeholder="/assets/svgs/pterodactyl.svg" />
                                    <p class="text-muted"><small>The URL of the logo displayed on the login and auth pages.</small></p>
                                </div>
                                <div style="margin-top: 10px;">
                                    <label class="control-label">Or Upload Logo</label>
                                    <input type="file" class="form-control" name="login_logo_file" accept="image/*" />
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
                            <div class="form-group col-md-12">
                                <label class="control-label">Discord Server Invite URL</label>
                                <div>
                                    <input type="text" class="form-control" name="pterodactyl:theme:discord_url" value="{{ old('pterodactyl:theme:discord_url', config('pterodactyl.theme.discord_url')) }}" placeholder="https://discord.gg/yourinvite" />
                                    <p class="text-muted"><small>The Discord link displayed alongside your panel name in the client sidebar.</small></p>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-12">
                                <hr style="border-color: #444;" />
                                <h3 class="box-title" style="margin-bottom: 15px; color: #fff;">Global Announcement Banner</h3>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="control-label">Enable Announcement</label>
                                <div>
                                    <select class="form-control" name="pterodactyl:theme:announcement_enabled">
                                        <option value="true" {{ old('pterodactyl:theme:announcement_enabled', config('pterodactyl.theme.announcement_enabled')) === 'true' || config('pterodactyl.theme.announcement_enabled') === true ? 'selected' : '' }}>Enabled</option>
                                        <option value="false" {{ old('pterodactyl:theme:announcement_enabled', config('pterodactyl.theme.announcement_enabled')) === 'false' || config('pterodactyl.theme.announcement_enabled') === false ? 'selected' : '' }}>Disabled</option>
                                    </select>
                                    <p class="text-muted"><small>Show or hide the global announcement banner for all users.</small></p>
                                </div>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="control-label">Dismissible (Can Close)</label>
                                <div>
                                    <select class="form-control" name="pterodactyl:theme:announcement_dismissible">
                                        <option value="true" {{ old('pterodactyl:theme:announcement_dismissible', config('pterodactyl.theme.announcement_dismissible')) === 'true' || config('pterodactyl.theme.announcement_dismissible') === true ? 'selected' : '' }}>Yes (Show × close button)</option>
                                        <option value="false" {{ old('pterodactyl:theme:announcement_dismissible', config('pterodactyl.theme.announcement_dismissible')) === 'false' || config('pterodactyl.theme.announcement_dismissible') === false ? 'selected' : '' }}>No (Show always, cannot close)</option>
                                    </select>
                                    <p class="text-muted"><small>Allows users to hide the announcement. If disabled, it stays visible constantly.</small></p>
                                </div>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="control-label">Banner Type / Color</label>
                                <div>
                                    <select class="form-control" name="pterodactyl:theme:announcement_type">
                                        <option value="info" {{ old('pterodactyl:theme:announcement_type', config('pterodactyl.theme.announcement_type')) === 'info' ? 'selected' : '' }}>Info (Purple / Blue)</option>
                                        <option value="warning" {{ old('pterodactyl:theme:announcement_type', config('pterodactyl.theme.announcement_type')) === 'warning' ? 'selected' : '' }}>Warning (Yellow / Orange)</option>
                                        <option value="critical" {{ old('pterodactyl:theme:announcement_type', config('pterodactyl.theme.announcement_type')) === 'critical' ? 'selected' : '' }}>Critical (Red)</option>
                                    </select>
                                    <p class="text-muted"><small>The styling type and alert color theme of the banner.</small></p>
                                </div>
                            </div>
                            <div class="form-group col-md-12">
                                <label class="control-label">Announcement Content</label>
                                <div>
                                    <textarea class="form-control" name="pterodactyl:theme:announcement_text" rows="3" placeholder="Enter announcement text here. HTML tags are supported.">{{ old('pterodactyl:theme:announcement_text', config('pterodactyl.theme.announcement_text')) }}</textarea>
                                    <p class="text-muted"><small>The announcement text/HTML content to display at the top of the user dashboard.</small></p>
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
