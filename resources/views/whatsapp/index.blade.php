@extends('layouts.admin', ['title' => __('common.whatsapp_b336fc')])

@section('content')
    <section class="whatsapp-page">
        <header class="whatsapp-hero">
            <div>
                <p class="eyebrow">{{ __('settings.growth_plan_integration_fff38c') }}</p>
                <h1>{{ __('common.whatsapp_messaging_9ebd23') }}</h1>
                <p>{{ __('products.connect_your_meta_whatsapp_business_account_and_send_customer_messages_b7a923') }}</p>
            </div>
            <span class="whatsapp-status {{ $integration?->is_active ? 'is-connected' : '' }}">
                <i></i>{{ $integration?->is_active ? __('common.connected_c2f9b7') : __('common.not_connected_8b02f3') }}
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
                    <div><p class="eyebrow">{{ __('common.cloud_api_b3a47b') }}</p><h2>{{ __('settings.connection_6512ee') }}</h2></div>
                </div>

                @if ($canConfigure)
                    <form method="POST" action="{{ route('whatsapp.update') }}" class="whatsapp-form">
                        @csrf
                        @method('PUT')
                        <label>
                            <span>{{ __('common.business_account_id_3cdaac') }}</span>
                            <input type="text" name="business_account_id" value="{{ old('business_account_id', $integration?->business_account_id) }}" placeholder="{{ __('common.meta_business_account_id_69de90') }}" required>
                            @error('business_account_id') <small class="field-error">{{ $message }}</small> @enderror
                        </label>
                        <label>
                            <span>{{ __('common.phone_number_id_8996af') }}</span>
                            <input type="text" name="phone_number_id" value="{{ old('phone_number_id', $integration?->phone_number_id) }}" placeholder="{{ __('common.whatsapp_phone_number_id_6d78ba') }}" required>
                            @error('phone_number_id') <small class="field-error">{{ $message }}</small> @enderror
                        </label>
                        <label>
                            <span>{{ __('common.permanent_access_token_3f1a03') }}</span>
                            <input type="password" name="access_token" autocomplete="new-password" placeholder="{{ $integration ? __('common.leave_blank_to_keep_saved_token_f1b451') : __('common.paste_the_meta_access_token_b2d185') }}" @required(! $integration)>
                            <small>{{ __('common.the_token_is_encrypted_and_is_never_displayed_again_e3fb24') }}</small>
                            @error('access_token') <small class="field-error">{{ $message }}</small> @enderror
                        </label>
                        <label class="check-row">
                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $integration?->is_active ?? true))>
                            <span>{{ __('common.enable_message_sending_67ae53') }}</span>
                        </label>
                        <button class="primary-link" type="submit">{{ __('settings.save_connection_07796d') }}</button>
                    </form>
                @else
                    <div class="whatsapp-owner-note">{{ __('common.only_the_account_owner_can_change_meta_api_credentials_1b0973') }}</div>
                @endif
            </article>

            <article class="whatsapp-card">
                <div class="section-title">
                    <div><p class="eyebrow">{{ __('common.new_message_1ed2e7') }}</p><h2>{{ __('orders.send_to_customer_37a27b') }}</h2></div>
                </div>
                <form method="POST" action="{{ route('whatsapp.send') }}" class="whatsapp-form">
                    @csrf
                    <label>
                        <span>{{ __('common.whatsapp_number_4955eb') }}</span>
                        <input type="text" inputmode="numeric" name="recipient" value="{{ old('recipient') }}" placeholder="919876543210" required>
                        <small>Include the country code; use digits only.</small>
                        @error('recipient') <small class="field-error">{{ $message }}</small> @enderror
                    </label>
                    <label>
                        <span>{{ __('common.message_68f414') }}</span>
                        <textarea name="message" rows="7" maxlength="4096" placeholder="{{ __('orders.write_your_customer_message_b4bc5c') }}" required>{{ old('message') }}</textarea>
                        @error('message') <small class="field-error">{{ $message }}</small> @enderror
                    </label>
                    <button class="whatsapp-send-button" type="submit" @disabled(! $integration?->is_active)>
                        {{ __('common.send_whatsapp_message_5f85af') }}
                    </button>
                    <small>{{ __('orders.free_form_messages_require_an_open_24_hour_customer_conversation_windo_173ccd') }}</small>
                </form>
            </article>
        </div>

        <article class="whatsapp-card whatsapp-history">
            <div class="section-title">
                <div><p class="eyebrow">{{ __('common.activity_81c0d9') }}</p><h2>{{ __('common.recent_messages_6c0d05') }}</h2></div>
            </div>
            <div class="whatsapp-table-wrap">
                <table>
                    <thead><tr><th>{{ __('common.recipient_903432') }}</th><th>{{ __('common.message_68f414') }}</th><th>{{ __('common.sent_by_226c99') }}</th><th>{{ __('common.status_bae7d5') }}</th><th>{{ __('common.date_eb9a4b') }}</th></tr></thead>
                    <tbody>
                    @forelse ($messages as $item)
                        <tr>
                            <td>+{{ $item->recipient }}</td>
                            <td><span class="whatsapp-message-copy" title="{{ $item->message }}">{{ $item->message }}</span></td>
                            <td>{{ $item->sender?->name ?? __('common.user_9f8a23') }}</td>
                            <td><span class="whatsapp-message-status is-{{ $item->status }}">{{ ucfirst($item->status) }}</span></td>
                            <td>{{ $item->created_at->format('d M Y, h:i A') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><div class="empty-state tight-empty">{{ __('common.no_whatsapp_messages_sent_yet_968c71') }}</div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            {{ $messages->links() }}
        </article>
    </section>
@endsection
