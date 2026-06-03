@extends('layouts.admin')

@section('title')
    Registration Config Settings
@endsection

@section('content-header')
    <h1>Registration Portal Settings<small>Configure settings and toggles for user registration.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Registration Config</li>
    </ol>
@endsection

@section('content')
    <form action="{{ route('admin.registration') }}" method="POST">
        <div class="row">
            <div class="col-md-6">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">Self-Service User Registration Configuration</h3>
                    </div>
                    <div class="box-body">
                        <div class="form-group">
                            <label class="control-label">Enable Self-Service Registration</label>
                            <div>
                                <select class="form-control" name="pterodactyl:registration:enabled">
                                    <option value="1" {{ old('pterodactyl:registration:enabled', config('pterodactyl.registration.enabled', false)) ? 'selected' : '' }}>Enabled</option>
                                    <option value="0" {{ !old('pterodactyl:registration:enabled', config('pterodactyl.registration.enabled', false)) ? 'selected' : '' }}>Disabled</option>
                                </select>
                                <p class="text-muted"><small>If enabled, a "Register" option will be visible on the login screen to allow new users to sign up for accounts themselves.</small></p>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label">Require Email OTP Verification</label>
                            <div>
                                @php
                                    $mailHost = config('mail.mailers.smtp.host');
                                    $mailSetup = !empty($mailHost) && $mailHost !== 'smtp.example.com' && !empty(config('mail.mailers.smtp.username'));
                                    $otpEnabled = config('pterodactyl.registration.otp', false);
                                @endphp
                                <select class="form-control" name="pterodactyl:registration:otp" @if(!$mailSetup) disabled @endif>
                                    <option value="1" {{ old('pterodactyl:registration:otp', $otpEnabled) && $mailSetup ? 'selected' : '' }}>Enabled (Requires users to verify their email via OTP)</option>
                                    <option value="0" {{ !old('pterodactyl:registration:otp', $otpEnabled) || !$mailSetup ? 'selected' : '' }}>Disabled</option>
                                </select>
                                @if(!$mailSetup)
                                    <p class="text-danger" style="margin-top: 5px;"><small><strong>Note:</strong> Mail settings are not fully configured yet. Please configure your <a href="{{ route('admin.settings.mail') }}">Mail Settings</a> first before enabling OTP verification.</small></p>
                                @else
                                    <p class="text-muted"><small>If enabled, users will receive a professional 6-digit OTP code to verify their email address before their account is created.</small></p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="box box-success">
                    <div class="box-header with-border">
                        <h3 class="box-title">Discord Social Authentication</h3>
                    </div>
                    <div class="box-body">
                        <div class="form-group">
                            <label class="control-label">Enable Discord Login/Registration</label>
                            <div>
                                <select class="form-control" name="pterodactyl:discord:enabled">
                                    <option value="1" {{ old('pterodactyl:discord:enabled', config('pterodactyl.discord.enabled', false)) ? 'selected' : '' }}>Enabled</option>
                                    <option value="0" {{ !old('pterodactyl:discord:enabled', config('pterodactyl.discord.enabled', false)) ? 'selected' : '' }}>Disabled</option>
                                </select>
                                <p class="text-muted"><small>Enable or disable login and registration via Discord OAuth2.</small></p>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label">Discord Client ID</label>
                            <div>
                                <input type="text" class="form-control" name="pterodactyl:discord:client_id" value="{{ old('pterodactyl:discord:client_id', config('pterodactyl.discord.client_id')) }}" placeholder="e.g. 104829381048392810">
                                <p class="text-muted"><small>The Client ID from your Discord Developer Portal application.</small></p>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label">Discord Client Secret</label>
                            <div>
                                <input type="password" class="form-control" name="pterodactyl:discord:client_secret" value="{{ old('pterodactyl:discord:client_secret', config('pterodactyl.discord.client_secret')) }}" placeholder="••••••••••••••••••••••••">
                                <p class="text-muted"><small>The Client Secret from your Discord Developer Portal application.</small></p>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label">Discord Redirect URI</label>
                            <div>
                                <input type="text" class="form-control" readonly value="{{ url('/auth/discord/callback') }}">
                                <p class="text-muted"><small>Copy this URL and paste it into the "Redirects" section under OAuth2 in your Discord Developer Application.</small></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-xs-12">
                <div class="box">
                    <div class="box-footer">
                        {!! csrf_field() !!}
                        <button type="submit" name="_method" value="PATCH" class="btn btn-sm btn-primary pull-right">Save Registration Config</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
