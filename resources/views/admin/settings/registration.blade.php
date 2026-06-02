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
    <div class="row">
        <div class="col-xs-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Self-Service User Registration Configuration</h3>
                </div>
                <form action="{{ route('admin.registration') }}" method="POST">
                    <div class="box-body">
                        <div class="row">
                            <div class="form-group col-md-12">
                                <label class="control-label">Enable Self-Service Registration</label>
                                <div>
                                    <select class="form-control" name="pterodactyl:registration:enabled">
                                        <option value="1" {{ old('pterodactyl:registration:enabled', config('pterodactyl.registration.enabled', false)) ? 'selected' : '' }}>Enabled</option>
                                        <option value="0" {{ !old('pterodactyl:registration:enabled', config('pterodactyl.registration.enabled', false)) ? 'selected' : '' }}>Disabled</option>
                                    </select>
                                    <p class="text-muted"><small>If enabled, a "Register" option will be visible on the login screen to allow new users to sign up for accounts themselves.</small></p>
                                </div>
                            </div>
                            <div class="form-group col-md-12">
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
                    <div class="box-footer">
                        {!! csrf_field() !!}
                        <button type="submit" name="_method" value="PATCH" class="btn btn-sm btn-primary pull-right">Save Registration Config</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
