<?php

namespace App\Services;

use App\Models\WhatsAppIntegration;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class WhatsAppCloudApi
{
    public function sendText(WhatsAppIntegration $integration, string $recipient, string $message): Response
    {
        return Http::withToken($integration->access_token)
            ->acceptJson()
            ->timeout(15)
            ->post(
                'https://graph.facebook.com/'.config('services.whatsapp.api_version', 'v22.0')
                    .'/'.$integration->phone_number_id.'/messages',
                [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $recipient,
                    'type' => 'text',
                    'text' => [
                        'preview_url' => false,
                        'body' => $message,
                    ],
                ]
            );
    }
}
