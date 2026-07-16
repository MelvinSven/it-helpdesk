<?php

namespace App\Mail\Transport;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;

class GmailApiTransport extends AbstractTransport
{
    public function __construct(
        protected string $clientId,
        protected string $clientSecret,
        protected string $refreshToken,
    ) {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        // Gmail API expects the full RFC 2822 message, base64url-encoded.
        $raw = rtrim(strtr(base64_encode($message->toString()), '+/', '-_'), '=');

        $response = Http::withToken($this->accessToken())
            ->post('https://gmail.googleapis.com/gmail/v1/users/me/messages/send', [
                'raw' => $raw,
            ]);

        if ($response->failed()) {
            throw new TransportException('Gmail API send failed: '.$response->body());
        }
    }

    protected function accessToken(): string
    {
        // Access tokens live ~60 minutes; refresh a bit early.
        return Cache::remember('gmail_api_access_token', now()->addMinutes(50), function (): string {
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'refresh_token' => $this->refreshToken,
                'grant_type' => 'refresh_token',
            ]);

            if ($response->failed() || ! $response->json('access_token')) {
                throw new TransportException('Gmail API token refresh failed: '.$response->body());
            }

            return $response->json('access_token');
        });
    }

    public function __toString(): string
    {
        return 'gmail';
    }
}
