<?php

namespace App\Http\Controllers;

use App\Models\WhatsAppIntegration;
use App\Models\WhatsAppMessage;
use App\Models\User;
use App\Services\WhatsAppCloudApi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class WhatsAppController extends Controller
{
    public function index(Request $request): View
    {
        $tenantId = $request->user()->tenant_id;

        return view('whatsapp.index', [
            'integration' => WhatsAppIntegration::where('tenant_id', $tenantId)->first(),
            'messages' => WhatsAppMessage::with('sender')
                ->where('tenant_id', $tenantId)
                ->latest()
                ->paginate(15),
            'canConfigure' => (int) $request->user()->role === User::ROLE_OWNER,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless((int) $request->user()->role === User::ROLE_OWNER, 403);

        $existing = WhatsAppIntegration::where('tenant_id', $request->user()->tenant_id)->first();
        $data = $request->validate([
            'business_account_id' => ['required', 'string', 'max:100'],
            'phone_number_id' => ['required', 'string', 'max:100'],
            'access_token' => [Rule::requiredIf(! $existing), 'nullable', 'string', 'min:20', 'max:4096'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (blank($data['access_token'] ?? null)) {
            unset($data['access_token']);
        }

        $data['is_active'] = $request->boolean('is_active');

        WhatsAppIntegration::updateOrCreate(
            ['tenant_id' => $request->user()->tenant_id],
            $data
        );

        return back()->with('status', 'WhatsApp connection saved.');
    }

    public function send(Request $request, WhatsAppCloudApi $api): RedirectResponse
    {
        $data = $request->validate([
            'recipient' => ['required', 'regex:/^[1-9][0-9]{7,14}$/'],
            'message' => ['required', 'string', 'max:4096'],
        ], [
            'recipient.regex' => 'Enter the number with country code and digits only, for example 919876543210.',
        ]);

        $integration = WhatsAppIntegration::where('tenant_id', $request->user()->tenant_id)
            ->where('is_active', true)
            ->first();

        if (! $integration) {
            return back()->withErrors(['connection' => 'Connect and enable WhatsApp before sending a message.'])->withInput();
        }

        $message = WhatsAppMessage::create([
            'tenant_id' => $request->user()->tenant_id,
            'sent_by' => $request->user()->id,
            'recipient' => $data['recipient'],
            'message' => $data['message'],
            'status' => 'pending',
        ]);

        try {
            $response = $api->sendText($integration, $data['recipient'], $data['message']);

            if ($response->successful()) {
                $message->update([
                    'status' => 'sent',
                    'provider_message_id' => $response->json('messages.0.id'),
                ]);
                $integration->update(['last_used_at' => now()]);

                return back()->with('status', 'WhatsApp message sent.');
            }

            $error = $response->json('error.message') ?: 'WhatsApp rejected the message.';
            $message->update(['status' => 'failed', 'error_message' => $error]);

            return back()->withErrors(['send' => $error])->withInput();
        } catch (Throwable $exception) {
            report($exception);
            $message->update([
                'status' => 'failed',
                'error_message' => 'Unable to reach the WhatsApp service.',
            ]);

            return back()->withErrors(['send' => 'Unable to reach the WhatsApp service. Try again.'])->withInput();
        }
    }
}
