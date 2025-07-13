<?php

namespace TomasManuelTM\ApyPayment\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use TomasManuelTM\ApyPayment\Models\ApyToken;

class ApyAuthService
{
    private Client $client;
    private string $authUrl;
    private string $clientId;
    private string $clientSecret;
    private string $resource;
    private ?string $accessToken = null;

    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->authUrl = config('apypayment.auth_url');
        $this->clientId = config('apypayment.client_id');
        $this->clientSecret = config('apypayment.client_secret');
        $this->resource = config('apypayment.resource');
    }

    public function getAccessToken(): ?string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        $this->checkExpiredTokens();

        try {
            $credential = ApyToken::where('istoken', true)->first();

            if ($credential) {
                return $this->accessToken = $credential->token;
            }

            $response = $this->client->post($this->authUrl, [
                'form_params' => $this->getAuthParams()
            ]);

            $data = json_decode($response->getBody(), true);
            $this->storeToken($data);
            
            return $this->accessToken = $data['access_token'];

        } catch (\Exception $e) {
            Log::error('Falha na autenticação: ' . $e->getMessage());
            return null;
        }
    }

    private function getAuthParams(): array
    {
        return [
            'grant_type' => 'client_credentials',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'resource' => $this->resource,
        ];
    }

    private function storeToken(array $tokenData): void
    {
        ApyToken::updateOrCreate(
            ['istoken' => true],
            [
                'token' => $tokenData['access_token'],
                'expires_on' => $tokenData['expires_on'],
                'expires_in' => $tokenData['expires_in'],
                'istoken' => true,
            ]
        );
    }

    private function checkExpiredTokens(): void
    {
        ApyToken::where('expires_on', '<', now()->timestamp)
            ->update(['istoken' => false]);
    }
}