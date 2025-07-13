<?php

namespace TomasManuelTM\ApyPayment\Services;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Collection;
use TomasManuelTM\ApyPayment\Models\ApyMethod;
use TomasManuelTM\ApyPayment\Models\ApyToken;
use TomasManuelTM\ApyPayment\Models\ApyPayment;
use RuntimeException;

class ApyService
{
    private Client $client;
    private string $authUrl;
    private string $apiUrl;
    private string $clientId;
    private string $clientSecret;
    private string $resource;
    private ?string $accessToken = null;

    public function __construct()
    {
        $this->initializeConfig();
        $this->initializeHttpClient();
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

    /**
     * Obtém token de acesso OAuth2
     */
    public function getAccessToken(): ?string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        $this->invalidarTokensExpirados();

        try {
            $tokenAtivo = $this->obterTokenAtivoDoBanco();
            if ($tokenAtivo) {
                return $this->accessToken = $tokenAtivo;
            }

            return $this->gerarNovoToken();
        } catch (GuzzleException $e) {
            Log::error('Erro na autenticação com a API: ' . $e->getMessage());
            return null;
        }
    }

    private function obterTokenAtivoDoBanco(): ?string
    {
        $credential = ApyToken::where('istoken', true)->first();
        return $credential->token;
    }

    private function gerarNovoToken(): ?string
    {
        $response = $this->client->post($this->authUrl, [
            'form_params' => $this->getAuthParams()
        ]);

        $data = json_decode($response->getBody(), true);
        $this->armazenarToken($data);
        
        return $this->accessToken = $data['access_token'];
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

    /**
     * Cabeçalhos padrão para requisições
     */
    private function getRequestHeaders(string $token): array
    {
        return [
            'Accept-Language' => config('apypayment.accept_language', 'pt-BR'),
            'Accept' => config('apypayment.accept', 'application/json'),
            'Content-Type' => config('apypayment.content_type', 'application/json'),
            'Authorization' => 'Bearer ' . $token,
        ];
    }

    /**
     * Armazena novo token no banco de dados
     */
    private function armazenarToken(array $tokenData): void
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

    /**
     * Gera uma referência única para pagamento
     */
    public function generateReference(): string
    {
        do {
            $reference = mt_rand(100000000, 999999999);
            $exists = ApyPayment::where('reference->referenceNumber', $reference)->exists();
        } while ($exists);

        return (string) $reference;
    }

    /**
     * Processa a resposta da API e toma ações corretivas quando necessário
     */
    private function handlePaymentResponse(array $paymentData, array $apiResponse, int $attempt = 1): array
    {
        $statusCode = $apiResponse['status'] ?? 200;
        $responseStatus = $apiResponse['responseStatus'] ?? [];
        $isSuccessful = $responseStatus['successful'] ?? false;
        $errorCode = $responseStatus['code'] ?? 0;
        $errorMessage = $responseStatus['message'] ?? 'Erro desconhecido';

        $this->logRespostaPagamento($statusCode, $responseStatus, $attempt, $apiResponse);

        if ($this->isRespostaDeSucesso($statusCode, $isSuccessful, $errorCode)) {
            return $this->formatarRespostaSucesso($apiResponse, $attempt);
        }

        $paymentData['currency'] = config('apypayment.default_currency', 'AOA');
        
        return $this->tratarErroPagamento($paymentData, $errorCode, $errorMessage, $attempt);
    }

    private function isRespostaDeSucesso(int $statusCode, bool $isSuccessful, int $errorCode): bool
    {
        return ($statusCode === 200 || $statusCode === 202) && 
               ($isSuccessful || $errorCode === 101);
    }

    private function formatarRespostaSucesso(array $apiResponse, int $attempt): array
    {
        return [
            'status' => 'success',
            'data' => $apiResponse,
            'attempts' => $attempt
        ];
    }

    private function tratarErroPagamento(array $paymentData, int $errorCode, string $errorMessage, int $attempt): array
    {
        switch ($errorCode) {
            case 726: // MerchantTransactionId duplicado
                return $this->tratarErroIdDuplicado($paymentData, $errorMessage, $attempt);
                
            case 763: // Referência duplicada
                return $this->tratarErroReferenciaDuplicada($paymentData, $errorMessage, $attempt);
                
            default:
                return $this->tratarErroGenerico($errorMessage, $errorCode, $attempt);
        }
    }

    /**
     * Atualiza tokens expirados no banco de dados
     */
    private function invalidarTokensExpirados(): void
    {
        ApyToken::where('expires_on', '<', now()->timestamp)
            ->update(['istoken' => false]);
    }

    /**
 * Trata erro de MerchantTransactionId duplicado
 */
private function tratarErroIdDuplicado(array $paymentData, string $errorMessage, int $attempt): array
{
    if ($attempt >= 3) {
        Log::error('Limite de tentativas atingido para MerchantTransactionId duplicado', [
            'codigo_erro' => 726,
            'tentativas' => $attempt,
            'mensagem_original' => $errorMessage
        ]);
        
        return [
            'status' => 'error',
            'error' => 'Número máximo de tentativas atingido para merchantTransactionId',
            'original_error' => $errorMessage,
            'code' => 726,
            'attempts' => $attempt
        ];
    }
    
    $novoTransactionId = $this->generateMerchantId();
    $paymentData['merchantTransactionId'] = $novoTransactionId;
    
    Log::warning('Tentando novamente com novo MerchantTransactionId', [
        'novo_id' => $novoTransactionId,
        'tentativa' => $attempt + 1
    ]);
    
    return [
        'status' => 'retry',
        'new_data' => $paymentData,
        'reason' => 'merchantTransactionId duplicado',
        'attempts' => $attempt + 1
    ];
}

    /**
     * Trata erro de referência duplicada
     */
    private function tratarErroReferenciaDuplicada(array $paymentData, string $errorMessage, int $attempt): array
    {
        if ($attempt >= 3) {
            Log::error('Limite de tentativas atingido para referência duplicada', [
                'codigo_erro' => 763,
                'tentativas' => $attempt,
                'mensagem_original' => $errorMessage
            ]);
            
            return [
                'status' => 'error',
                'error' => 'Número máximo de tentativas atingido para referência',
                'original_error' => $errorMessage,
                'code' => 763,
                'attempts' => $attempt
            ];
        }
        
        $novaReferencia = $this->generateReference();
        $paymentData['paymentInfo']['referenceNumber'] = $novaReferencia;
        
        Log::warning('Tentando novamente com nova referência', [
            'nova_referencia' => $novaReferencia,
            'tentativa' => $attempt + 1
        ]);
        
        return [
            'status' => 'retry',
            'new_data' => $paymentData,
            'reason' => 'Referência duplicada',
            'attempts' => $attempt + 1
        ];
    }

    /**
     * Trata erros genéricos de pagamento
     */
    private function tratarErroGenerico(string $errorMessage, int $errorCode, int $attempt): array
    {
        Log::error('Falha no processamento do pagamento', [
            'codigo_erro' => $errorCode,
            'mensagem' => $errorMessage,
            'tentativa' => $attempt
        ]);
        
        return [
            'status' => 'error',
            'error' => $errorMessage,
            'code' => $errorCode,
            'attempts' => $attempt
        ];
    }

    /**
     * Registra a resposta do pagamento para análise
     */
    private function logRespostaPagamento(int $statusCode, array $responseStatus, int $attempt, array $apiResponse): void
    {
        Log::info('Resposta da API de pagamento', [
            'codigo_status' => $statusCode,
            'status_resposta' => $responseStatus,
            'tentativa' => $attempt,
            'dados' => [
                'id_transacao' => $apiResponse['id'] ?? null,
                'status' => $apiResponse['status'] ?? null,
                'valor' => $apiResponse['amount'] ?? null
            ]
        ]);
    }

    /**
     * Obtém o tipo de pagamento com fallback para o método padrão
     */
    private function getPaymentType(string $type): string
    {
        $paymentMethod = ApyMethod::where('method', $type)->first();
        
        if ($paymentMethod) {
            return $paymentMethod->type;
        }
        
        $defaultMethod = ApyMethod::where('isDefault', true)->first();
        
        if (!$defaultMethod) {
            Log::error('Nenhum método de pagamento padrão configurado');
            throw new RuntimeException('Nenhum método de pagamento padrão configurado');
        }
        
        Log::warning('Usando método de pagamento padrão', [
            'metodo_solicitado' => $type,
            'metodo_usado' => $defaultMethod->method
        ]);
        
        return $defaultMethod->type;
    }

    /**
     * Sincroniza pagamento com o banco de dados local
     */
    private function syncPaymentToDatabase(array $paymentData): void
    {
        try {
            $dadosTransformados = $this->transformPaymentData($paymentData);
            
            ApyPayment::updateOrCreate(
                ['merchantTransactionId' => $paymentData['merchantTransactionId']],
                $dadosTransformados
            );
            
            Log::info('Pagamento sincronizado com sucesso', [
                'id_transacao' => $paymentData['id'] ?? null,
                'merchant_id' => $paymentData['merchantTransactionId'] ?? null
            ]);
        } catch (\Exception $e) {
            Log::error('Falha ao sincronizar pagamento', [
                'erro' => $e->getMessage(),
                'dados' => $paymentData
            ]);
        }
    }
}