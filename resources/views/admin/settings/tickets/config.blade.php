@extends('layouts.admin')

@section('title')
    Tickets Config Settings
@endsection

@section('content-header')
    <h1>Tickets Settings<small>Configure support ticket limitations and toggles.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.tickets') }}">Tickets</a></li>
        <li class="active">Config</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <div class="col-xs-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Support Tickets Configuration</h3>
                </div>
                <form action="{{ route('admin.tickets.config') }}" method="POST">
                    <div class="box-body">
                        <div class="row">
                            <div class="form-group col-md-12">
                                <label class="control-label">Enable Support Tickets System</label>
                                <div>
                                    <select class="form-control" name="pterodactyl:tickets:enabled">
                                        <option value="1" {{ old('pterodactyl:tickets:enabled', config('pterodactyl.tickets.enabled', true)) ? 'selected' : '' }}>Enabled</option>
                                        <option value="0" {{ !old('pterodactyl:tickets:enabled', config('pterodactyl.tickets.enabled', true)) ? 'selected' : '' }}>Disabled</option>
                                    </select>
                                    <p class="text-muted"><small>Allows client users to view, open, and converse inside direct support tickets from the client panel.</small></p>
                                </div>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="control-label">Maximum Open Tickets Per User</label>
                                <div>
                                    <input type="number" class="form-control" name="pterodactyl:tickets:limit" value="{{ old('pterodactyl:tickets:limit', config('pterodactyl.tickets.limit', 3)) }}" min="1" max="100" required />
                                    <p class="text-muted"><small>The maximum number of active (Open/Under Review) support tickets a single client user can have open simultaneously.</small></p>
                                </div>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="control-label">Maximum User Messages Per Open Ticket</label>
                                <div>
                                    <input type="number" class="form-control" name="pterodactyl:tickets:message_limit" value="{{ old('pterodactyl:tickets:message_limit', config('pterodactyl.tickets.message_limit', 10)) }}" min="1" max="1000" required />
                                    <p class="text-muted"><small>The message cap a user can send inside a ticket while it remains under "Open" status. (Once staff puts the ticket "Under Review", users have infinite chat access).</small></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="box-footer">
                        {!! csrf_field() !!}
                        <button type="submit" name="_method" value="PATCH" class="btn btn-sm btn-primary pull-right">Save Config Settings</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
