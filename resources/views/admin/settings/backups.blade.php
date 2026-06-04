@extends('layouts.admin')
@include('partials/admin.settings.nav', ['activeTab' => 'backups'])

@section('title')
    Universal Backups
@endsection

@section('content-header')
    <h1>Universal Backups<small>Configure and manage premium Google Drive backups and disaster recovery.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Settings</li>
    </ol>
@endsection

@section('content')
    @yield('settings::nav')
    <div class="row">
        <div class="col-xs-12 col-md-6">
            <form action="{{ route('admin.settings.backups') }}" method="POST">
                @method('PATCH')
                @csrf
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-google"></i> Google Drive Credentials</h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="form-group col-xs-12">
                                <label class="control-label">Enable Backups</label>
                                <div>
                                    <select class="form-control" name="pterodactyl:backups:enabled">
                                        <option value="1" @if(config('pterodactyl.backups.enabled') == '1') selected @endif>Enabled</option>
                                        <option value="0" @if(config('pterodactyl.backups.enabled') != '1') selected @endif>Disabled</option>
                                    </select>
                                    <p class="text-muted small">Globally enable or disable Google Drive backup functionality.</p>
                                </div>
                            </div>
                            <div class="form-group col-xs-12">
                                <label class="control-label">Target Google Drive Folder ID</label>
                                <div>
                                    <input type="text" class="form-control" name="pterodactyl:backups:gdrive_folder_id" value="{{ config('pterodactyl.backups.gdrive_folder_id') }}" placeholder="e.g. 1a2b3c4d5e6f7g8h9i0j...">
                                    <p class="text-muted small">The ID of the Google Drive folder where backup archives will be stored.</p>
                                </div>
                            </div>
                            <div class="form-group col-xs-12">
                                <label class="control-label">Authentication Method</label>
                                <div>
                                    <select class="form-control" id="gdrive_auth_method" name="pterodactyl:backups:gdrive_auth_method">
                                        <option value="oauth2" @if(config('pterodactyl.backups.gdrive_auth_method') === 'oauth2') selected @endif>OAuth2 Client (Client ID & Secret)</option>
                                        <option value="service_account" @if(config('pterodactyl.backups.gdrive_auth_method') === 'service_account') selected @endif>Service Account JSON Key File</option>
                                    </select>
                                    <p class="text-muted small">Select the method of authentication for your Google Drive connection.</p>
                                </div>
                            </div>
                        </div>

                        <!-- OAuth2 Fields -->
                        <div id="oauth2_fields">
                            <div class="row">
                                <div class="form-group col-xs-12">
                                    <label class="control-label">Client ID</label>
                                    <div>
                                        <input type="text" class="form-control" name="pterodactyl:backups:gdrive_client_id" value="{{ config('pterodactyl.backups.gdrive_client_id') }}">
                                    </div>
                                </div>
                                <div class="form-group col-xs-12">
                                    <label class="control-label">Client Secret</label>
                                    <div>
                                        <input type="password" class="form-control" name="pterodactyl:backups:gdrive_client_secret" value="{{ config('pterodactyl.backups.gdrive_client_secret') }}">
                                    </div>
                                </div>
                                <div class="form-group col-xs-12">
                                    <label class="control-label">Refresh Token</label>
                                    <div>
                                        <input type="password" class="form-control" name="pterodactyl:backups:gdrive_refresh_token" value="{{ config('pterodactyl.backups.gdrive_refresh_token') }}">
                                        <p class="text-muted small">Paste your OAuth2 Refresh token here to maintain dynamic connection access.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Service Account Fields -->
                        <div id="service_account_fields" style="display: none;">
                            <div class="row">
                                <div class="form-group col-xs-12">
                                    <label class="control-label">Service Account JSON Content</label>
                                    <div>
                                        <textarea class="form-control" name="pterodactyl:backups:gdrive_service_account" rows="6" placeholder="Paste the content of your Google Cloud Service Account credentials .json file here">{{ config('pterodactyl.backups.gdrive_service_account') }}</textarea>
                                        <p class="text-muted small">Recommended: Paste the full JSON credentials file generated from the Google Cloud Console.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h4><i class="fa fa-clock-o"></i> Automated Backup Scheduler</h4>
                        <div class="row">
                            <div class="form-group col-md-4">
                                <label class="control-label">Automated Scheduler</label>
                                <div>
                                    <select class="form-control" name="pterodactyl:backups:schedule_enabled">
                                        <option value="1" @if(config('pterodactyl.backups.schedule_enabled') == '1') selected @endif>Enabled</option>
                                        <option value="0" @if(config('pterodactyl.backups.schedule_enabled') != '1') selected @endif>Disabled</option>
                                    </select>
                                    <p class="text-muted small">Enable or disable periodic background backups.</p>
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="control-label">Backup Interval (Hours)</label>
                                <div>
                                    <input type="number" class="form-control" name="pterodactyl:backups:schedule_interval" value="{{ config('pterodactyl.backups.schedule_interval', 24) }}" min="1" required>
                                    <p class="text-muted small">Specify how often to run the backup in hours (e.g. 24 for daily).</p>
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="control-label">Scheduled Backup Type</label>
                                <div>
                                    <select class="form-control" name="pterodactyl:backups:schedule_type">
                                        <option value="both" @if(config('pterodactyl.backups.schedule_type') === 'both') selected @endif>Both Database & Servers</option>
                                        <option value="database" @if(config('pterodactyl.backups.schedule_type') === 'database') selected @endif>Database Only</option>
                                        <option value="all_servers" @if(config('pterodactyl.backups.schedule_type') === 'all_servers') selected @endif>All Servers Files Only</option>
                                    </select>
                                    <p class="text-muted small">Select the scope of automated backups.</p>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-xs-12">
                                <label class="control-label">Ignore Server Backup Limits</label>
                                <div>
                                    <select class="form-control" name="pterodactyl:backups:ignore_limits">
                                        <option value="1" @if(config('pterodactyl.backups.ignore_limits') == '1') selected @endif>Yes, Ignore Limits (Allows backing up servers even if backup limit is 0 or reached)</option>
                                        <option value="0" @if(config('pterodactyl.backups.ignore_limits') != '1') selected @endif>No, Respect Limits</option>
                                    </select>
                                    <p class="text-muted small">If enabled, universal backups will temporarily bypass standard server backup limit checks in memory.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="box-footer">
                        <button type="submit" class="btn btn-primary pull-right">Save Configurations</button>
                    </div>
                </div>
            </form>

            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-terminal"></i> Node Backup Agent Setup</h3>
                </div>
                <div class="box-body">
                    <p>Each Node VPS must run the Backup Agent script regularly (e.g. via crontab) to check for pending server file backups and restore tasks.</p>
                    <label>Installation script / config command:</label>
                    <pre style="background: #272822; color: #f8f8f2; padding: 10px; border-radius: 4px;">curl -sSL -o /usr/local/bin/node_backup_agent.sh {{ url('/node_backup_agent.sh') }} && chmod +x /usr/local/bin/node_backup_agent.sh</pre>
                    <p class="text-muted small">Create a cron job to run the agent every minute:</p>
                    <pre style="background: #272822; color: #f8f8f2; padding: 10px; border-radius: 4px;">* * * * * /usr/local/bin/node_backup_agent.sh >/dev/null 2>&1</pre>
                </div>
            </div>
        </div>

        <div class="col-xs-12 col-md-6">
            <form action="{{ route('admin.settings.backups.trigger') }}" method="POST">
                @csrf
                <div class="box box-success">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-play-circle"></i> Trigger Instant Backup</h3>
                    </div>
                    <div class="box-body">
                        <div class="form-group">
                            <div class="checkbox checkbox-primary">
                                <label>
                                    <input type="checkbox" name="backup_database" value="1" checked>
                                    <strong>Back up Panel Database & Users Structure</strong>
                                </label>
                            </div>
                            <p class="text-muted small">Will generate an SQL dump of the panel database and upload it directly to Google Drive instantly.</p>
                        </div>

                        <hr>

                        <div class="form-group">
                            <label class="control-label">Select Node VPS (Optional)</label>
                            <select class="form-control" name="backup_node_id" id="backup_node_id">
                                <option value="">-- Choose Node to Backup All of its Servers --</option>
                                @foreach($nodes as $node)
                                    <option value="{{ $node->id }}">{{ $node->name }} ({{ $node->fqdn }})</option>
                                @endforeach
                            </select>
                            <p class="text-muted small">Selecting a node will queue file backups for all servers hosted on that node.</p>
                        </div>

                        <div class="form-group" id="server_select_group">
                            <label class="control-label">Or Select Specific Servers</label>
                            <div style="max-height: 250px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 4px; background: #fafafa;">
                                @if($servers->count() > 0)
                                    @foreach($servers as $server)
                                        <div class="checkbox">
                                            <label>
                                                <input type="checkbox" name="backup_servers[]" value="{{ $server->id }}">
                                                <strong>{{ $server->name }}</strong>
                                                <small class="text-muted">({{ $server->uuidShort }} - Node: {{ $server->node->name }})</small>
                                            </label>
                                        </div>
                                    @endforeach
                                @else
                                    <p class="text-muted text-center no-margin">No servers configured on this panel.</p>
                                @endif
                            </div>
                            <p class="text-muted small">Select individual servers to queue for file backups.</p>
                        </div>
                    </div>
                    <div class="box-footer">
                        <button type="submit" class="btn btn-success pull-right">Trigger Backup Task(s)</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-xs-12">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-history"></i> Google Drive Cloud Backup History</h3>
                </div>
                <div class="box-body table-responsive no-padding">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Backup Type</th>
                                <th>Target Details</th>
                                <th>Filename</th>
                                <th>Size</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($backups->count() > 0)
                                @foreach($backups as $backup)
                                    <tr>
                                        <td><code>#{{ $backup->id }}</code></td>
                                        <td>
                                            @if($backup->backup_type === 'database')
                                                <span class="label label-primary"><i class="fa fa-database"></i> Database</span>
                                            @else
                                                <span class="label label-info"><i class="fa fa-server"></i> Server File Backup</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($backup->backup_type === 'database')
                                                <span>Panel Database & Configuration</span>
                                            @else
                                                <span>Server: <strong>{{ $backup->server_name }}</strong></span>
                                            @endif
                                        </td>
                                        <td>{{ $backup->filename }}</td>
                                        <td>{{ $backup->readable_size }}</td>
                                        <td>
                                            @if($backup->status === 'completed')
                                                <span class="label label-success">Completed</span>
                                            @elseif($backup->status === 'pending')
                                                <span class="label label-warning">Pending Node Agent</span>
                                            @elseif($backup->status === 'backing_up')
                                                <span class="label label-info">Backing Up...</span>
                                            @elseif($backup->status === 'restore_pending')
                                                <span class="label label-warning">Restore Pending</span>
                                            @elseif($backup->status === 'restoring')
                                                <span class="label label-info">Restoring...</span>
                                            @else
                                                <span class="label label-danger">Failed</span>
                                            @endif
                                        </td>
                                        <td>{{ $backup->created_at }}</td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-xs">
                                                @if($backup->status === 'completed' || $backup->status === 'failed')
                                                    <!-- Restore Action Form -->
                                                    <form action="{{ route('admin.settings.backups.restore') }}" method="POST" style="display:inline;">
                                                        @csrf
                                                        <input type="hidden" name="backup_id" value="{{ $backup->id }}">
                                                        <button type="submit" class="btn btn-default" onclick="return confirm('Are you sure you want to restore this backup? Doing so will overwrite existing database records or target node server files.')" title="Restore Backup">
                                                            <i class="fa fa-undo text-success"></i> Restore
                                                        </button>
                                                    </form>
                                                @endif
                                                <!-- Delete Action Form -->
                                                <form action="{{ route('admin.settings.backups.delete') }}" method="POST" style="display:inline;">
                                                    @method('DELETE')
                                                    @csrf
                                                    <input type="hidden" name="backup_id" value="{{ $backup->id }}">
                                                    <button type="submit" class="btn btn-default" onclick="return confirm('Are you sure you want to delete this backup from the database and Google Drive?')" title="Delete Backup">
                                                        <i class="fa fa-trash-o text-danger"></i> Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="8" class="text-muted text-center">No backups found on Google Drive cloud storage.</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        $(document).ready(function() {
            $('#gdrive_auth_method').on('change', function() {
                var method = $(this).val();
                if (method === 'service_account') {
                    $('#oauth2_fields').slideUp();
                    $('#service_account_fields').slideDown();
                } else {
                    $('#oauth2_fields').slideDown();
                    $('#service_account_fields').slideUp();
                }
            }).trigger('change');

            $('#backup_node_id').on('change', function() {
                if ($(this).val()) {
                    $('#server_select_group').slideUp();
                } else {
                    $('#server_select_group').slideDown();
                }
            });
        });
    </script>
@endsection
