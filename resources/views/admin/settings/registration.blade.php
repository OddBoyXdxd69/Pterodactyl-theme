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
