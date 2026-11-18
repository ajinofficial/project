@extends('layouts.admin', ['title' => __('common.email_84add5')])

@section('content')
    <section class="email-module-page">
        <header class="email-module-hero">
            <div>
                <p class="eyebrow">{{ __('common.vendor_communication_11ff80') }}</p>
                <h1>{{ __('common.email_workspace_418bb5') }}</h1>
                <p>{{ __('orders.connect_your_smtp_mailbox_and_send_account_or_service_messages_directl_54fefe') }}</p>
            </div>
            <span class="email-connection-status {{ $integration?->is_active ? 'is-connected' : '' }}">
                <i></i>{{ $integration?->is_active ? __('settings.smtp_connected_54b629') : __('common.not_connected_8b02f3') }}
            </span>
        </header>

        @if (session('status'))
            <div class="auth-status" role="status">{{ session('status') }}</div>
        @endif
        @error('connection') <div class="email-module-alert" role="alert">{{ $message }}</div> @enderror
        @error('send') <div class="email-module-alert" role="alert">{{ $message }}</div> @enderror

        <div class="email-module-grid">
            <article class="email-module-card">
                <div class="section-title">
                    <div><p class="eyebrow">{{ __('common.outgoing_mail_e331a5') }}</p><h2>{{ __('settings.smtp_connection_f55b10') }}</h2></div>
                </div>
                <form method="POST" action="{{ route('email.update') }}" class="email-module-form">
                    @csrf
                    @method('PUT')
                    <div class="email-module-field-grid">
                        <label>
                            <span>{{ __('settings.smtp_host_2d4a43') }}</span>
                            <input type="text" name="host" value="{{ old('host', $integration?->host) }}" placeholder="smtp.example.com" required>
                            @error('host') <small class="field-error">{{ $message }}</small> @enderror
                        </label>
                        <label>
                            <span>{{ __('common.port_fe0351') }}</span>
                            <input type="number" name="port" value="{{ old('port', $integration?->port ?? 587) }}" min="1" max="65535" required>
                            @error('port') <small class="field-error">{{ $message }}</small> @enderror
                        </label>
                    </div>
                    <div class="email-module-field-grid">
                        <label>
                            <span>{{ __('common.encryption_0af149') }}</span>
                            <select name="encryption">
                                <option value="">{{ __('common.none_6eef66') }}</option>
                                <option value="tls" @selected(old('encryption', $integration?->encryption ?? 'tls') === 'tls')>TLS</option>
                                <option value="ssl" @selected(old('encryption', $integration?->encryption) === 'ssl')>SSL</option>
                            </select>
                        </label>
                        <label>
                            <span>{{ __('common.username_84c290') }}</span>
                            <input type="text" name="username" value="{{ old('username', $integration?->username) }}" autocomplete="username" required>
                            @error('username') <small class="field-error">{{ $message }}</small> @enderror
                        </label>
                    </div>
                    <label>
                        <span>{{ __('auth.smtp_password_0c0c4b') }}</span>
                        <input type="password" name="password" autocomplete="new-password" placeholder="{{ $integration ? __('auth.leave_blank_to_keep_saved_password_06d97f') : 'Enter SMTP password' }}" @required(! $integration)>
                        <small>{{ __('auth.the_password_is_encrypted_and_never_shown_again_e7cb53') }}</small>
                        @error('password') <small class="field-error">{{ $message }}</small> @enderror
                    </label>
                    <div class="email-module-field-grid">
                        <label>
                            <span>{{ __('common.from_email_1288a2') }}</span>
                            <input type="email" name="from_address" value="{{ old('from_address', $integration?->from_address) }}" placeholder="support@example.com" required>
                            @error('from_address') <small class="field-error">{{ $message }}</small> @enderror
                        </label>
                        <label>
                            <span>{{ __('common.from_name_1e7763') }}</span>
                            <input type="text" name="from_name" value="{{ old('from_name', $integration?->from_name ?? auth()->user()->tenant?->business_name) }}" required>
                            @error('from_name') <small class="field-error">{{ $message }}</small> @enderror
                        </label>
                    </div>
                    <label class="check-row">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $integration?->is_active ?? true))>
                        <span>{{ __('common.enable_email_sending_3ca995') }}</span>
                    </label>
                    <button class="primary-link" type="submit">{{ __('settings.save_smtp_connection_162123') }}</button>
                </form>
            </article>

            <article class="email-module-card">
                <div class="section-title">
                    <div><p class="eyebrow">{{ __('common.compose_47da6f') }}</p><h2>{{ __('common.send_an_email_69815e') }}</h2></div>
                </div>
                <form method="POST" action="{{ route('email.send') }}" class="email-module-form">
                    @csrf
                    <label>
                        <span>{{ __('common.recipient_903432') }}</span>
                        <input type="email" name="recipient" value="{{ old('recipient') }}" placeholder="customer@example.com" required>
                        @error('recipient') <small class="field-error">{{ $message }}</small> @enderror
                    </label>
                    <label>
                        <span>{{ __('common.subject_8d183d') }}</span>
                        <input type="text" name="subject" value="{{ old('subject') }}" maxlength="255" required>
                        @error('subject') <small class="field-error">{{ $message }}</small> @enderror
                    </label>
                    <label>
                        <span>{{ __('common.message_68f414') }}</span>
                        <textarea name="message" rows="9" maxlength="20000" placeholder="{{ __('common.write_your_message_e4c609') }}" required>{{ old('message') }}</textarea>
                        @error('message') <small class="field-error">{{ $message }}</small> @enderror
                    </label>
                    <button class="email-send-button" type="submit" @disabled(! $integration?->is_active)>{{ __('common.send_email_619a90') }}</button>
                </form>
            </article>
        </div>

        <article class="email-module-card email-module-history">
            <div class="section-title">
                <div><p class="eyebrow">{{ __('common.delivery_log_d8e210') }}</p><h2>{{ __('common.recent_emails_76d513') }}</h2></div>
            </div>
            <div class="email-module-table-wrap">
                <table>
                    <thead><tr><th>{{ __('common.recipient_903432') }}</th><th>{{ __('common.subject_8d183d') }}</th><th>{{ __('common.sent_by_226c99') }}</th><th>{{ __('common.status_bae7d5') }}</th><th>{{ __('common.date_eb9a4b') }}</th></tr></thead>
                    <tbody>
                    @forelse ($messages as $item)
                        <tr>
                            <td>{{ $item->recipient }}</td>
                            <td><span class="email-subject-copy" title="{{ $item->subject }}">{{ $item->subject }}</span></td>
                            <td>{{ $item->sender?->name ?? __('common.user_9f8a23') }}</td>
                            <td><span class="email-message-status is-{{ $item->status }}">{{ ucfirst($item->status) }}</span></td>
                            <td>{{ $item->created_at->format('d M Y, h:i A') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><div class="empty-state tight-empty">{{ __('common.no_emails_sent_yet_0b5914') }}</div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            {{ $messages->links() }}
        </article>
    </section>
@endsection
