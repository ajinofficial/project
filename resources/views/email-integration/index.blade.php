@extends('layouts.admin', ['title' => 'Email'])

@section('content')
    <section class="email-module-page">
        <header class="email-module-hero">
            <div>
                <p class="eyebrow">Vendor communication</p>
                <h1>Email workspace</h1>
                <p>Connect your SMTP mailbox and send account or service messages directly from the vendor console.</p>
            </div>
            <span class="email-connection-status {{ $integration?->is_active ? 'is-connected' : '' }}">
                <i></i>{{ $integration?->is_active ? 'SMTP connected' : 'Not connected' }}
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
                    <div><p class="eyebrow">Outgoing mail</p><h2>SMTP connection</h2></div>
                </div>
                <form method="POST" action="{{ route('email.update') }}" class="email-module-form">
                    @csrf
                    @method('PUT')
                    <div class="email-module-field-grid">
                        <label>
                            <span>SMTP host</span>
                            <input type="text" name="host" value="{{ old('host', $integration?->host) }}" placeholder="smtp.example.com" required>
                            @error('host') <small class="field-error">{{ $message }}</small> @enderror
                        </label>
                        <label>
                            <span>Port</span>
                            <input type="number" name="port" value="{{ old('port', $integration?->port ?? 587) }}" min="1" max="65535" required>
                            @error('port') <small class="field-error">{{ $message }}</small> @enderror
                        </label>
                    </div>
                    <div class="email-module-field-grid">
                        <label>
                            <span>Encryption</span>
                            <select name="encryption">
                                <option value="">None</option>
                                <option value="tls" @selected(old('encryption', $integration?->encryption ?? 'tls') === 'tls')>TLS</option>
                                <option value="ssl" @selected(old('encryption', $integration?->encryption) === 'ssl')>SSL</option>
                            </select>
                        </label>
                        <label>
                            <span>Username</span>
                            <input type="text" name="username" value="{{ old('username', $integration?->username) }}" autocomplete="username" required>
                            @error('username') <small class="field-error">{{ $message }}</small> @enderror
                        </label>
                    </div>
                    <label>
                        <span>SMTP password</span>
                        <input type="password" name="password" autocomplete="new-password" placeholder="{{ $integration ? 'Leave blank to keep saved password' : 'Enter SMTP password' }}" @required(! $integration)>
                        <small>The password is encrypted and never shown again.</small>
                        @error('password') <small class="field-error">{{ $message }}</small> @enderror
                    </label>
                    <div class="email-module-field-grid">
                        <label>
                            <span>From email</span>
                            <input type="email" name="from_address" value="{{ old('from_address', $integration?->from_address) }}" placeholder="support@example.com" required>
                            @error('from_address') <small class="field-error">{{ $message }}</small> @enderror
                        </label>
                        <label>
                            <span>From name</span>
                            <input type="text" name="from_name" value="{{ old('from_name', $integration?->from_name ?? auth()->user()->tenant?->business_name) }}" required>
                            @error('from_name') <small class="field-error">{{ $message }}</small> @enderror
                        </label>
                    </div>
                    <label class="check-row">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $integration?->is_active ?? true))>
                        <span>Enable email sending</span>
                    </label>
                    <button class="primary-link" type="submit">Save SMTP connection</button>
                </form>
            </article>

            <article class="email-module-card">
                <div class="section-title">
                    <div><p class="eyebrow">Compose</p><h2>Send an email</h2></div>
                </div>
                <form method="POST" action="{{ route('email.send') }}" class="email-module-form">
                    @csrf
                    <label>
                        <span>Recipient</span>
                        <input type="email" name="recipient" value="{{ old('recipient') }}" placeholder="customer@example.com" required>
                        @error('recipient') <small class="field-error">{{ $message }}</small> @enderror
                    </label>
                    <label>
                        <span>Subject</span>
                        <input type="text" name="subject" value="{{ old('subject') }}" maxlength="255" required>
                        @error('subject') <small class="field-error">{{ $message }}</small> @enderror
                    </label>
                    <label>
                        <span>Message</span>
                        <textarea name="message" rows="9" maxlength="20000" placeholder="Write your message…" required>{{ old('message') }}</textarea>
                        @error('message') <small class="field-error">{{ $message }}</small> @enderror
                    </label>
                    <button class="email-send-button" type="submit" @disabled(! $integration?->is_active)>Send email</button>
                </form>
            </article>
        </div>

        <article class="email-module-card email-module-history">
            <div class="section-title">
                <div><p class="eyebrow">Delivery log</p><h2>Recent emails</h2></div>
            </div>
            <div class="email-module-table-wrap">
                <table>
                    <thead><tr><th>Recipient</th><th>Subject</th><th>Sent by</th><th>Status</th><th>Date</th></tr></thead>
                    <tbody>
                    @forelse ($messages as $item)
                        <tr>
                            <td>{{ $item->recipient }}</td>
                            <td><span class="email-subject-copy" title="{{ $item->subject }}">{{ $item->subject }}</span></td>
                            <td>{{ $item->sender?->name ?? 'User' }}</td>
                            <td><span class="email-message-status is-{{ $item->status }}">{{ ucfirst($item->status) }}</span></td>
                            <td>{{ $item->created_at->format('d M Y, h:i A') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><div class="empty-state tight-empty">No emails sent yet.</div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            {{ $messages->links() }}
        </article>
    </section>
@endsection
