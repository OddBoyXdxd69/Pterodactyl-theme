@extends('layouts.admin')

@section('title')
    Ticket #{{ $ticket->id }}: {{ $ticket->title }}
@endsection

@section('content-header')
    <h1>Support Ticket #{{ $ticket->id }}<small>{{ $ticket->title }}</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.tickets') }}">Tickets</a></li>
        <li class="active">#{{ $ticket->id }}</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-4">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Ticket Information</h3>
                </div>
                <div class="box-body">
                    <div class="form-group">
                        <label class="control-label">Title</label>
                        <p class="form-control-static">{{ $ticket->title }}</p>
                    </div>
                    <div class="form-group">
                        <label class="control-label">User</label>
                        <p class="form-control-static">
                            <a href="{{ route('admin.users.view', $ticket->user->id) }}">{{ $ticket->user->username }}</a> ({{ $ticket->user->email }})
                        </p>
                    </div>
                    <div class="form-group">
                        <label class="control-label">Status</label>
                        <div>
                            @if ($ticket->status === 'open')
                                <span class="label label-success">Open</span>
                            @else
                                <span class="label label-default">Closed</span>
                            @endif
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label">Opened At</label>
                        <p class="form-control-static">{{ $ticket->created_at->format('Y-m-d H:i:s') }}</p>
                    </div>
                </div>
                <div class="box-footer">
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        @if ($ticket->status === 'open')
                            <form action="{{ route('admin.tickets.close', $ticket->id) }}" method="POST" style="width:100%; margin-bottom:5px;">
                                {!! csrf_field() !!}
                                <button type="submit" class="btn btn-warning btn-block"><i class="fa fa-lock"></i> Close / End Ticket</button>
                            </form>
                        @endif
                        <form action="{{ route('admin.tickets.delete', $ticket->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this ticket? This cannot be undone.')" style="width:100%;">
                            {!! csrf_field() !!}
                            {!! method_field('DELETE') !!}
                            <button type="submit" class="btn btn-danger btn-block"><i class="fa fa-trash"></i> Delete Ticket</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Conversation Log</h3>
                </div>
                <div class="box-body chat" id="chat-box" style="max-height: 400px; overflow-y: auto; padding: 10px;">
                    @foreach ($ticket->messages as $msg)
                        <div class="item" style="margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #f4f4f4;">
                            <p class="message" style="margin-left: 0;">
                                <span class="name">
                                    <small class="text-muted pull-right"><i class="fa fa-clock-o"></i> {{ $msg->created_at->diffForHumans() }}</small>
                                    <strong>
                                        @if ($msg->is_admin)
                                            <span class="label label-danger">Admin Reply</span> {{ $msg->user->username }}
                                        @else
                                            <span class="label label-primary">User</span> {{ $msg->user->username }}
                                        @endif
                                    </strong>
                                </span>
                                <div style="margin-top: 5px; white-space: pre-wrap;">{{ $msg->message }}</div>
                            </p>
                        </div>
                    @endforeach
                </div>
                @if ($ticket->status === 'open')
                    <form action="{{ route('admin.tickets.reply', $ticket->id) }}" method="POST">
                        {!! csrf_field() !!}
                        <div class="box-footer">
                            <div class="input-group" style="width: 100%">
                                <textarea name="message" class="form-control" placeholder="Type message..." rows="3" required style="resize: vertical; margin-bottom: 10px;"></textarea>
                                <button type="submit" class="btn btn-primary pull-right"><i class="fa fa-paper-plane"></i> Send Reply</button>
                            </div>
                        </div>
                    </form>
                @else
                    <div class="box-footer text-center text-muted">
                        This ticket is closed. Re-opening is not supported from this screen.
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
