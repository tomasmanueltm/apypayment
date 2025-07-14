<?php

namespace TomasManuelTM\ApyPayment\Services;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use TomasManuelTM\ApyPayment\Models\ApyToken;

class ApyAuth
{
    public Client $client;
    private string $authUrl;
    private string $clientId;
    private string $clientSecret;
    private string $resource;
    private ?string $accessToken = null;
    public string $apiUrl;

    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->initializeConfig();
    }

    /**
     * Inicializa configurações do serviço
     */
    private function initializeConfig(): void
    {
        $this->authUrl = config('apypayment.auth_url');
        $this->apiUrl = config('apypayment.api_url');
        $this->clientId = config('apypayment.client_id');
        $this->clientSecret = config('apypayment.client_secret');
        $this->resource = config('apypayment.resource');
    }

    /**
     * Configura o cliente HTTP com headers padrão
     */
    private function initializeHttpClient(): void
    {
        $this->client = new Client([
            'verify' => false, // Considerar usar true em produção com certificado válido
            'timeout' => 30,
            'http_errors' => false,
            'headers' => [
                "limit" => 10,
                "Accept-Language" => config('apypayment.accept_language', 'pt-BR'),
                'Accept' => config('apypayment.accept', 'application/json'),
                'Content-Type' => config('apypayment.content_type', 'application/json'),
            ],
        ]);
    }

    /*
    * Parametros para obter token
    * @return array Parâmetros de autenticação
    */
    private function getAuthParams(): array
    {
        return [
            'grant_type' => config('apypayment.grant_type'),
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'resource' => $this->resource,
        ];
    }

    /*
    * Obter token do ativo
    * @return string|null Token ativo ou null se não existir
    */
    private function getToken(): ?string
    {
        $credential = ApyToken::where('istoken', true)->first();
        return $credential->token ?? null;
    }

    /*
    * Calcular tempo de expiração do token
    * @return void
    */
    private function checkExpiredTokens(): void
    {
        ApyToken::where('expires_on', '<', now()->timestamp)
            ->update(['istoken' => false]);
    }

    /**
     * Obtém token de acesso OAuth2
     * @return string|null Token de acesso ou null em caso de falha
     */
    public function getAccessToken(): ?string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        $this->checkExpiredTokens();

        try {
            $tokenAtivo = $this->getToken();
            if ($tokenAtivo) {
                return $this->accessToken = $tokenAtivo;
            }
            return $this->generateToken();
        } catch (GuzzleException $e) {
            app('apylogger')->error('generateToken', ['Erro na autenticação com a API '=> $e->getMessage()]);
            return null;
        }
    }

    /*
    * Guardar token no database
    * @param array $tokenData Dados do token a serem armazenados
    * @return void
    */ 
    private function storeToken(array $tokenData): void
    {
        try {
            ApyToken::updateOrCreate(
                ['istoken' => false],
                [
                    'token' => $tokenData['access_token'],
                    'expires_on' => $tokenData['expires_on'],
                    'expires_in' => $tokenData['expires_in'],
                    'istoken' => true,
                ]
            );
            app('apylogger')->success('generateToken', ['Token armazenado no banco de dados '=> $e->getMessage()]);
        } catch (\Exception $e) {
            app('apylogger')->error('generateToken', ['Falha ao armazenar token '=> $e->getMessage()]);
        }
    }

    /*
    * Gerar novo token
    * @return string|null Novo token gerado ou null em caso de falha
    */
    private function generateToken(): ?string
    {
        try {
            $response = $this->client->post($this->authUrl, [
                'form_params' => $this->getAuthParams()
            ]);

            $data = json_decode($response->getBody(), true);
            $this->storeToken($data);
            
            return $this->accessToken = $data['access_token'];
        } catch (GuzzleException $e) {
            app('apylogger')->error('generateToken', ['Erro ao gerar token'=> $e->getMessage()]);
            return null;
        }
    }


    /*
    * Metodos de HttpClients    *
    * @param string $token Token de acesso
    *    
    */
    protected function getRequestHeaders(string $token): array
    {
        return [
            'Accept-Language' => config('apypayment.accept_language'),
            'Accept' => config('apypayment.accept'),
            'Content-Type' => config('apypayment.content_type'),
            'Authorization' => 'Bearer ' . $token,
        ];
    }

    
    /**
     * Obtém a lista de métodos de pagamento disponíveis
     * @return Response|null Resposta da API ou null em caso de falha
    */
    public  function applications() : ?\Psr\Http\Message\ResponseInterface 
    {
        $token = $this->getAccessToken();
        if (!$token) {
            app('apylogger')->error('applications', ['Falha ao obter token de acesso ']);
            return null;
        }

        $response = $this->client->get($this->apiUrl . '/applications', [
            'headers' => $this->getRequestHeaders($token) 
        ]);
        return $response;
    }

    /**
     * Obtém a lista de pagamentos
     * @param string $token Token de acesso
     * @return Response|null Resposta da API ou null em caso de falha   
     * */
    
    public function payments(): ?\Psr\Http\Message\ResponseInterface
    {
        try {
            $token = $this->getAccessToken();
            if (!$token) {
                app('apylogger')->error('payments', ['Falha ao obter token de acesso ']);
                return null;
            }

            $response = $this->client->get($this->apiUrl . '/charges?limit=100000000', [
                'headers' => $this->getRequestHeaders($token)
            ]);
            return $response;
        } catch (GuzzleException $e) {
            app('apylogger')->error('payments', ['Erro ao obter pagamentos '=> $e->getMessage()]);
            return null;
        }
    }


}