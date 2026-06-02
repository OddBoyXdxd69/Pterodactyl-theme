@extends('layouts.admin')
@include('partials/admin.settings.nav', ['activeTab' => 'mail'])

@section('title')
    Mail Settings
@endsection

@section('content-header')
    <h1>Mail Settings<small>Configure how Pterodactyl should handle sending emails.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Settings</li>
    </ol>
@endsection

@section('content')
    @yield('settings::nav')
    <div class="row">
        <div class="col-xs-12">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Email Settings</h3>
                </div>
                @if($disabled)
                    <div class="box-body">
                        <div class="row">
                            <div class="col-xs-12">
                                <div class="alert alert-info no-margin-bottom">
                                    This interface is limited to instances using SMTP as the mail driver. Please either use <code>php artisan p:environment:mail</code> command to update your email settings, or set <code>MAIL_DRIVER=smtp</code> in your environment file.
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <form>
                        <div class="box-body">
                            <div class="row">
                                <div class="form-group col-md-12">
                                    <label class="control-label">Quick Provider Preset</label>
                                    <div>
                                        <select id="mailProviderPreset" class="form-control">
                                            <option value="custom" selected>-- Custom / Manual Configuration --</option>
                                            <option value="gmail_ssl">Gmail (Port 465, SSL - Recommended for App Passwords)</option>
                                            <option value="gmail_tls">Gmail (Port 587, TLS)</option>
                                            <option value="outlook">Outlook / Office365 (Port 587, TLS)</option>
                                            <option value="mailgun">Mailgun (Port 587, TLS)</option>
                                            <option value="sendgrid">SendGrid (Port 587, TLS)</option>
                                            <option value="resend">Resend (Port 465, SSL)</option>
                                            <option value="smtp2go">SMTP2Go (Port 2525, TLS)</option>
                                        </select>
                                        <p class="text-muted small">Select a preconfigured provider to automatically fill in the host, port, and encryption type below.</p>
                                    </div>
                                </div>
                                <div class="col-xs-12" id="presetNoticeContainer" style="display: none;">
                                    <div class="alert alert-info" id="presetNoticeText" style="margin-bottom: 15px;">
                                        <!-- Preset specific instructions will appear here -->
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label class="control-label">SMTP Host</label>
                                    <div>
                                        <input required type="text" class="form-control" name="mail:mailers:smtp:host" id="smtpHost" value="{{ old('mail:mailers:smtp:host', config('mail.mailers.smtp.host')) }}" />
                                        <p class="text-muted small">Enter the SMTP server address that mail should be sent through.</p>
                                    </div>
                                </div>
                                <div class="form-group col-md-2">
                                    <label class="control-label">SMTP Port</label>
                                    <div>
                                        <input required type="number" class="form-control" name="mail:mailers:smtp:port" id="smtpPort" value="{{ old('mail:mailers:smtp:port', config('mail.mailers.smtp.port')) }}" />
                                        <p class="text-muted small">Enter the SMTP server port that mail should be sent through.</p>
                                    </div>
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="control-label">Encryption</label>
                                    <div>
                                        @php
                                            $encryption = old('mail:mailers:smtp:encryption', config('mail.mailers.smtp.encryption'));
                                        @endphp
                                        <select name="mail:mailers:smtp:encryption" id="smtpEncryption" class="form-control">
                                            <option value="" @if($encryption === '') selected @endif>None</option>
                                            <option value="tls" @if($encryption === 'tls') selected @endif>Transport Layer Security (TLS)</option>
                                            <option value="ssl" @if($encryption === 'ssl') selected @endif>Secure Sockets Layer (SSL)</option>
                                        </select>
                                        <p class="text-muted small">Select the type of encryption to use when sending mail.</p>
                                    </div>
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="control-label">Username <span class="field-optional"></span></label>
                                    <div>
                                        <input type="text" class="form-control" name="mail:mailers:smtp:username" value="{{ old('mail:mailers:smtp:username', config('mail.mailers.smtp.username')) }}" />
                                        <p class="text-muted small">The username to use when connecting to the SMTP server.</p>
                                    </div>
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="control-label">Password <span class="field-optional"></span></label>
                                    <div>
                                        <input type="password" class="form-control" name="mail:mailers:smtp:password"/>
                                        <p class="text-muted small">The password to use in conjunction with the SMTP username. Leave blank to continue using the existing password. To set the password to an empty value enter <code>!e</code> into the field.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <hr />
                                <div class="form-group col-md-6">
                                    <label class="control-label">Mail From</label>
                                    <div>
                                        <input required type="email" class="form-control" name="mail:from:address" value="{{ old('mail:from:address', config('mail.from.address')) }}" />
                                        <p class="text-muted small">Enter an email address that all outgoing emails will originate from.</p>
                                    </div>
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="control-label">Mail From Name <span class="field-optional"></span></label>
                                    <div>
                                        <input type="text" class="form-control" name="mail:from:name" value="{{ old('mail:from:name', config('mail.from.name')) }}" />
                                        <p class="text-muted small">The name that emails should appear to come from.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="box-footer">
                            {{ csrf_field() }}
                            <div class="pull-right">
                                <button type="button" id="testButton" class="btn btn-sm btn-success">Test</button>
                                <button type="button" id="saveButton" class="btn btn-sm btn-primary">Save</button>
                            </div>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('footer-scripts')
    @parent

    <script>
        function saveSettings() {
            return $.ajax({
                method: 'PATCH',
                url: '/admin/settings/mail',
                contentType: 'application/json',
                data: JSON.stringify({
                    'mail:mailers:smtp:host': $('input[name="mail:mailers:smtp:host"]').val(),
                    'mail:mailers:smtp:port': $('input[name="mail:mailers:smtp:port"]').val(),
                    'mail:mailers:smtp:encryption': $('select[name="mail:mailers:smtp:encryption"]').val(),
                    'mail:mailers:smtp:username': $('input[name="mail:mailers:smtp:username"]').val(),
                    'mail:mailers:smtp:password': $('input[name="mail:mailers:smtp:password"]').val(),
                    'mail:from:address': $('input[name="mail:from:address"]').val(),
                    'mail:from:name': $('input[name="mail:from:name"]').val()
                }),
                headers: { 'X-CSRF-Token': $('input[name="_token"]').val() }
            }).fail(function (jqXHR) {
                showErrorDialog(jqXHR, 'save');
            });
        }

        function testSettings() {
            swal({
                type: 'info',
                title: 'Test Mail Settings',
                text: 'Click "Test" to begin the test.',
                showCancelButton: true,
                confirmButtonText: 'Test',
                closeOnConfirm: false,
                showLoaderOnConfirm: true
            }, function () {
                $.ajax({
                    method: 'POST',
                    url: '/admin/settings/mail/test',
                    headers: { 'X-CSRF-TOKEN': $('input[name="_token"]').val() }
                }).fail(function (jqXHR) {
                    showErrorDialog(jqXHR, 'test');
                }).done(function () {
                    swal({
                        title: 'Success',
                        text: 'The test message was sent successfully.',
                        type: 'success'
                    });
                });
            });
        }

        function saveAndTestSettings() {
            saveSettings().done(testSettings);
        }

        function showErrorDialog(jqXHR, verb) {
            console.error(jqXHR);
            var errorText = '';
            if (!jqXHR.responseJSON) {
                errorText = jqXHR.responseText;
            } else if (jqXHR.responseJSON.error) {
                errorText = jqXHR.responseJSON.error;
            } else if (jqXHR.responseJSON.errors) {
                $.each(jqXHR.responseJSON.errors, function (i, v) {
                    if (v.detail) {
                        errorText += v.detail + ' ';
                    }
                });
            }

            swal({
                title: 'Whoops!',
                text: 'An error occurred while attempting to ' + verb + ' mail settings: ' + errorText,
                type: 'error'
            });
        }

        $(document).ready(function () {
            const presets = {
                custom: { host: '', port: '', encryption: '', notice: '' },
                gmail_ssl: {
                    host: 'smtp.gmail.com',
                    port: '465',
                    encryption: 'ssl',
                    notice: '🔑 <strong>Gmail SSL Setup:</strong> Make sure you have enabled <strong>2-Step Verification</strong> on your Google Account, then generate a 16-character <strong>App Password</strong>. Enter the App Password into the Password field (not your personal Google account password).'
                },
                gmail_tls: {
                    host: 'smtp.gmail.com',
                    port: '587',
                    encryption: 'tls',
                    notice: '🔑 <strong>Gmail TLS Setup:</strong> Make sure you have enabled <strong>2-Step Verification</strong> on your Google Account, then generate a 16-character <strong>App Password</strong>. Enter the App Password into the Password field (not your personal Google account password).'
                },
                outlook: {
                    host: 'smtp.office365.com',
                    port: '587',
                    encryption: 'tls',
                    notice: '🔑 <strong>Outlook Setup:</strong> If you have Multi-Factor Authentication enabled, you must generate and use an <strong>App Password</strong> instead of your normal password.'
                },
                mailgun: {
                    host: 'smtp.mailgun.org',
                    port: '587',
                    encryption: 'tls',
                    notice: '🚀 <strong>Mailgun Setup:</strong> Enter your Mailgun SMTP credentials. Use the full SMTP login username (e.g., postmaster@yourdomain.com) and the SMTP password provided by Mailgun.'
                },
                sendgrid: {
                    host: 'smtp.sendgrid.net',
                    port: '587',
                    encryption: 'tls',
                    notice: '🚀 <strong>SendGrid Setup:</strong> Set Username to <code>apikey</code> (exactly that word) and the Password to your SendGrid API key.'
                },
                resend: {
                    host: 'smtp.resend.com',
                    port: '465',
                    encryption: 'ssl',
                    notice: '🚀 <strong>Resend Setup:</strong> Set Username to <code>resend</code> (exactly that word) and the Password to your Resend API key (starts with re_).'
                },
                smtp2go: {
                    host: 'mail.smtp2go.com',
                    port: '2525',
                    encryption: 'tls',
                    notice: '🚀 <strong>SMTP2Go Setup:</strong> Use your SMTP2Go Username and SMTP Password.'
                }
            };

            $('#mailProviderPreset').on('change', function () {
                const val = $(this).val();
                const preset = presets[val];
                if (val !== 'custom') {
                    $('#smtpHost').val(preset.host);
                    $('#smtpPort').val(preset.port);
                    $('#smtpEncryption').val(preset.encryption);
                    $('#presetNoticeText').html(preset.notice);
                    $('#presetNoticeContainer').fadeIn();
                } else {
                    $('#presetNoticeContainer').fadeOut();
                }
            });

            $('#testButton').on('click', saveAndTestSettings);
            $('#saveButton').on('click', function () {
                saveSettings().done(function () {
                    swal({
                        title: 'Success',
                        text: 'Mail settings have been updated successfully and the queue worker was restarted to apply these changes.',
                        type: 'success'
                    });
                });
            });
        });
    </script>
@endsection
