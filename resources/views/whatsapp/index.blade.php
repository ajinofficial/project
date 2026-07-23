@extends('layouts.admin', ['title' => 'WhatsApp'])

@section('content')
    <section class="whatsapp-page">
        <header class="whatsapp-hero">
            <div>
                <p class="eyebrow">Growth plan integration</p>
                <h1>WhatsApp messaging</h1>
                <p>Connect your Meta WhatsApp Business account and send customer messages from your inventory workspace.</p>
            </div>
            <span class="whatsapp-status {{ $integration?->is_active ? 'is-connected' : '' }}">
                <i></i>{{ $integration?->is_active ? 'Connected' : 'Not connected' }}
            </span>
        </header>

        @if (session('status'))
            <div class="auth-status" role="status">{{ session('status') }}</div>
        @endif
        @error('connection') <div class="whatsapp-alert" role="alert">{{ $message }}</div> @enderror
        @error('send') <div class="whatsapp-alert" role="alert">{{ $message }}</div> @enderror

        <div class="whatsapp-grid">
            <article class="whatsapp-card">
                <div class="section-title">
                    <div><p class="eyebrow">Cloud API</p><h2>Connection</h2></div>
                </div>

                @if ($canConfigure)
                    <form method="POST" action="{{ route('whatsapp.update') }}" class="whatsapp-form">
                        @csrf
                        @method('PUT')
                        <label>
                            <span>Business account ID</span>
                            <input type="text" name="business_account_id" value="{{ old('business_account_id', $integration?->business_account_id) }}" placeholder="Meta business account ID" required>
                            @error('business_account_id') <small class="field-error">{{ $message }}</small> @enderror
                        </label>
                        <label>
                            <span>Phone number ID</span>
                            <input type="text" name="phone_number_id" value="{{ old('phone_number_id', $integration?->phone_number_id) }}" placeholder="WhatsApp phone number ID" required>
                            @error('phone_number_id') <small class="field-error">{{ $message }}</small> @enderror
                        </label>
                        <label>
                            <span>Permanent access token</span>
                            <input type="password" name="access_token" autocomplete="new-password" placeholder="{{ $integration ? 'Leave blank to keep saved token' : 'Paste the Meta access token' }}" @required(! $integration)>
                            <small>The token is encrypted and is never displayed again.</small>
                            @error('access_token') <small class="field-error">{{ $message }}</small> @enderror
                        </label>
                        <label class="check-row">
                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $integration?->is_active ?? true))>
                            <span>Enable message sending</span>
                        </label>
                        <button class="primary-link" type="submit">Save connection</button>
                    </form>
                @else
                    <div class="whatsapp-owner-note">Only the account owner can change Meta API credentials.</div>
                @endif
            </article>

            <article class="whatsapp-card">
                <div class="section-title">
                    <div><p class="eyebrow">New message</p><h2>Send to customer</h2></div>
                </div>
                <form method="POST" action="{{ route('whatsapp.send') }}" class="whatsapp-form">
                    @csrf
                    <label>
                        <span>WhatsApp number</span>
                        <input type="text" inputmode="numeric" name="recipient" value="{{ old('recipient') }}" placeholder="919876543210" required>
                        <small>Include the country code; use digits only.</small>
                        @error('recipient') <small class="field-error">{{ $message }}</small> @enderror
                    </label>
                    <label>
                        <span>Message</span>
                        <textarea name="message" rows="7" maxlength="4096" placeholder="Write your customer message…" required>{{ old('message') }}</textarea>
                        @error('message') <small class="field-error">{{ $message }}</small> @enderror
                    </label>
                    <button class="whatsapp-send-button" type="submit" @disabled(! $integration?->is_active)>
                        Send WhatsApp message
                    </button>
                    <small>Free-form messages require an open 24-hour customer conversation window. Use Meta-approved templates outside that window.</small>
                </form>
            </article>
        </div>

        <article class="whatsapp-card whatsapp-history">
            <div class="section-title">
                <div><p class="eyebrow">Activity</p><h2>Recent messages</h2></div>
            </div>
            <div class="whatsapp-table-wrap">
                <table>
                    <thead><tr><th>Recipient</th><th>Message</th><th>Sent by</th><th>Status</th><th>Date</th></tr></thead>
                    <tbody>
                    @forelse ($messages as $item)
                        <tr>
                            <td>+{{ $item->recipient }}</td>
                            <td><span class="whatsapp-message-copy" title="{{ $item->message }}">{{ $item->message }}</span></td>
                            <td>{{ $item->sender?->name ?? 'User' }}</td>
                            <td><span class="whatsapp-message-status is-{{ $item->status }}">{{ ucfirst($item->status) }}</span></td>
                            <td>{{ $item->created_at->format('d M Y, h:i A') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><div class="empty-state tight-empty">No WhatsApp messages sent yet.</div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            {{ $messages->links() }}
        </article>
    </section>
@endsection
