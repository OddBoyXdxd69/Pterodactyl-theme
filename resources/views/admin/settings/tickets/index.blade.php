@extends('layouts.admin')

@section('title')
    Support Tickets
@endsection

@section('content-header')
    <h1>Support Tickets<small>View and manage all customer support tickets.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Tickets</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <div class="col-xs-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Support Tickets List</h3>
                </div>
                <div class="box-body table-responsive no-padding">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Title</th>
                                <th>Status</th>
                                <th>Opened At</th>
                                <th>Last Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tickets as $ticket)
                                <tr>
                                    <td><code>#{{ $ticket->id }}</code></td>
                                    <td><a href="{{ route('admin.users.view', $ticket->user->id) }}">{{ $ticket->user->username }}</a></td>
                                    <td><a href="{{ route('admin.tickets.view', $ticket->id) }}"><strong>{{ $ticket->title }}</strong></a></td>
                                    <td>
                                        @if ($ticket->status === 'open')
                                            <span class="label label-success">Open</span>
                                        @else
                                            <span class="label label-default">Closed</span>
                                        @endif
                                    </td>
                                    <td>{{ $ticket->created_at->format('Y-m-d H:i') }}</td>
                                    <td>{{ $ticket->updated_at->diffForHumans() }}</td>
                                    <td>
                                        <a href="{{ route('admin.tickets.view', $ticket->id) }}" class="btn btn-xs btn-primary"><i class="fa fa-eye"></i> View</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($tickets->hasPages())
                    <div class="box-footer with-border">
                        <div class="pull-right">
                            {!! $tickets->render() !!}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
