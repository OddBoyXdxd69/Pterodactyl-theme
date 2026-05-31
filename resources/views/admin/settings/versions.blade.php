@extends('layouts.admin')

@section('title')
    Versions Downloader Settings
@endsection

@section('content-header')
    <h1>Versions Settings<small>Configure Minecraft server jar versions downloader settings and allowed nests.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Versions</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <div class="col-xs-12">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Versions Downloader Configuration</h3>
                </div>
                <form action="{{ route('admin.versions') }}" method="POST">
                    <div class="box-body">
                        <div class="row">
                            <div class="form-group col-md-12">
                                <label class="control-label">Enable Minecraft Versions Downloader</label>
                                <div>
                                    <select class="form-control" name="pterodactyl:versions:enabled">
                                        <option value="1" {{ old('pterodactyl:versions:enabled', config('pterodactyl.versions.enabled')) ? 'selected' : '' }}>Enabled</option>
                                        <option value="0" {{ !old('pterodactyl:versions:enabled', config('pterodactyl.versions.enabled')) ? 'selected' : '' }}>Disabled</option>
                                    </select>
                                    <p class="text-muted"><small>Allows client servers belonging to allowed nests to search, choose, and install server jar versions directly from Paper, Folia, Purpur, Vanilla, Fabric, Velocity, and BungeeCord.</small></p>
                                </div>
                            </div>
                            <div class="form-group col-md-12">
                                <label class="control-label">Allowed Nests</label>
                                <p class="text-muted"><small>Select which Nests can use the versions downloader. (Typically you want to select Minecraft Java or proxy nests here).</small></p>
                                <div class="row">
                                    @foreach ($nests as $nest)
                                        <div class="col-md-4 col-sm-6">
                                            <div class="checkbox checkbox-primary">
                                                <input id="nest_{{ $nest->id }}" name="nests[]" value="{{ $nest->id }}" type="checkbox" {{ in_array($nest->id, $allowedNests) ? 'checked' : '' }}>
                                                <label for="nest_{{ $nest->id }}">
                                                    <strong>{{ $nest->name }}</strong> <span class="text-muted">({{ $nest->author }})</span>
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
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
