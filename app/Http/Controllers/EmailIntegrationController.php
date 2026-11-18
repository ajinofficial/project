<?php

namespace App\Http\Controllers;

use App\Models\EmailIntegration;
use App\Models\EmailMessage;
use App\Services\TenantEmailSender;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class EmailIntegrationController extends Controller
{
    public function index(Request $request): View
    {
        $tenantId = $request->user()->tenant_id;

        return view('email-integration.index', [
            'integration' => EmailIntegration::where('tenant_id', $tenantId)->first(),
            'messages' => EmailMessage::with('sender')
                ->where('tenant_id', $tenantId)
                ->latest()
                ->paginate(15),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $existing = EmailIntegration::where('tenant_id', $request->user()->tenant_id)->first();
        $data = $request->validate([
            'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'integer', 'between:1,65535'],
            'encryption' => ['nullable', Rule::in(['tls', 'ssl'])],
            'username' => ['required', 'string', 'max:255'],
            'password' => [Rule::requiredIf(! $existing), 'nullable', 'string', 'max:4096'],
            'from_address' => ['required', new \App\Rules\SafeEmail, 'max:255'],
            'from_name' => ['required', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $data['is_active'] = $request->boolean('is_active');

        EmailIntegration::updateOrCreate(
            ['tenant_id' => $request->user()->tenant_id],
            $data
        );

        return back()->with('status', __('settings.email_connection_saved_eca88e'));
    }

    public function send(Request $request, TenantEmailSender $sender): RedirectResponse
    {
        $data = $request->validate([
            'recipient' => ['required', new \App\Rules\SafeEmail, 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:20000'],
        ]);

        $integration = EmailIntegration::where('tenant_id', $request->user()->tenant_id)
            ->where('is_active', true)
            ->first();

        if (! $integration) {
            return back()->withErrors(['connection' => __('settings.configure_and_enable_smtp_before_sending_email_83b673')])->withInput();
        }

        $businessName = $request->user()->tenant?->business_name ?: config('app.name');
        $subject = Str::limit($businessName.' - '.$data['subject'], 255, '');

        $log = EmailMessage::create([
            'tenant_id' => $request->user()->tenant_id,
            'sent_by' => $request->user()->id,
            'recipient' => $data['recipient'],
            'subject' => $subject,
            'message' => $data['message'],
            'status' => 'pending',
        ]);

        try {
            $sender->send($integration, $data['recipient'], $subject, $data['message']);
            $log->update(['status' => 'sent']);
            $integration->update(['last_used_at' => now()]);

            return back()->with('status', __('common.email_sent_successfully_65abf2'));
        } catch (Throwable $exception) {
            report($exception);
            $log->update([
                'status' => 'failed',
                'error_message' => __('validation.the_smtp_server_could_not_send_this_email_7a5c60'),
            ]);

            return back()->withErrors(['send' => __('validation.the_smtp_server_could_not_send_this_email_check_the_connection_setting_c5120b')])->withInput();
        }
    }
}
