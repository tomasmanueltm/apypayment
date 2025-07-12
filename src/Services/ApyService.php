<?php

namespace TomasManuelTM\ApyPayment\Services;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\GuzzleException;
use TomasManuelTM\ApyPayment\Models\ApyToken;
use TomasManuelTM\ApyPayment\Models\ApyPayment;

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
     * Configura o cliente HTTP
     */
    private function initializeHttpClient(): void
    {
        $this->client = new Client([
            'verify' => false,
            'timeout' => 30,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
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
        $this->checkExpiredTokens();

        try {
            $credential = ApyToken::where('istoken', true)->first();

            if ($credential) {
                $this->accessToken = $credential->token;
                return $this->accessToken;
            }


            $response = $this->client->post($this->authUrl, [
                'form_params' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'resource' => $this->resource,
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            $this->storeNewToken($data);
            
            return $this->accessToken = $data['access_token'];

        } catch (GuzzleException $e) {
            return $e->getMessage();
            Log::error('IPay Auth Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Armazena novo token no banco de dados
     */
    private function storeNewToken(array $tokenData): void
    {
        ApyToken::first()->update(
            [
                'token' => $tokenData['access_token'],
                'expires_on' => $tokenData['expires_on'],
                'expires_in' => $tokenData['expires_in'],
                'istoken' => true,
            ]
        );
    }

    /**
     * Cria um novo pagamento
     */
    public function createPayment(array $paymentData): ?array
    {
        $token = $this->getAccessToken();
        if (!$token) {
            return null;
        }

        $paymentData = $this->preparePaymentData($paymentData);

        try {
            $response = $this->client->post($this->apiUrl . '/charges', [
                'headers' => $this->getRequestHeaders($token),
                'json' => $paymentData,
            ]);

            $responseData = json_decode($response->getBody(), true);
            $this->syncPaymentToDatabase($responseData);
            
            return $responseData;

        } catch (GuzzleException $e) {
            Log::error('IPay Create Payment Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Prepara dados do pagamento com valores padrão
     */
    private function preparePaymentData(array $paymentData): array
    {
        return array_merge([
            'paymentMethod' => config('apypayment.default_payment_method'),
            'merchantTransactionId' => $this->generateMerchantTransactionId(),
        ], $paymentData);
    }

    /**
     * Sincroniza pagamento com o banco de dados local
     */
    private function syncPaymentToDatabase(array $paymentData): void
    {
        try {
            ApyPayment::updateOrCreate(
                ['merchantTransactionId' => $paymentData['merchantTransactionId']],
                $this->transformPaymentData($paymentData)
            );
        } catch (\Exception $e) {
            Log::error('Payment sync failed: ' . $e->getMessage());
        }
    }

    /**
     * Transforma os dados da API para o formato do banco de dados
     */
    private function transformPaymentData(array $apiData): array
    {
        return [
            'id' => $apiData['id'],
            'type' => $apiData['type'],
            'operation' => $apiData['operation'],
            'amount' => $apiData['amount'],
            'currency' => $apiData['currency'],
            'status' => $apiData['status'],
            'description' => $apiData['description'] ?? null,
            'paymentMethod' => $apiData['paymentMethod'],
            'disputes' => $apiData['disputes'] ?? false,
            'applicationFeeAmount' => $apiData['applicationFeeAmount'] ?? 0,
            'options' => json_encode($apiData['options'] ?? []),
            'createdDate' => Carbon::parse($apiData['createdDate']),
            'updatedDate' => Carbon::parse($apiData['updatedDate']),
            'reference' => json_encode($apiData['reference'] ?? []),
        ];
    }

 
    /**
     * Executa a busca nas tabelas especificadas
     */
    private function executeSearch(array $filters): array
    {
        $results = [];
        
        foreach ($filters as $filter) {
            $table = $filter['table'] ?? 'apy_payments';
            $column = $filter['column'];
            $value = $filter['value'];
            $referenceType = $filter['referenceType'] ?? 'merchantTransactionId';

            if ($table === 'apy_payments') {
                $query = ApyPayment::query();
                
                if ($referenceType === 'merchantTransactionId') {
                    $query->where('merchantTransactionId', $value);
                } else {
                    $query->whereJsonContains('reference->referenceNumber', $value);
                }
                
                $results[$table] = $query->get()->toArray();
            }
            // Adicione outros casos de tabelas aqui se necessário
        }
        
        return $results;
    }


    /**
     * Verifica e atualiza tokens expirados
     */
    private function checkExpiredTokens(): void
    {
        ApyToken::where('expires_on', '<', now()->timestamp)
            ->update(['istoken' => false]);
    }

    /**
     * Cabeçalhos padrão para requisições
     */
    private function getRequestHeaders(string $token): array
    {
        return [
            "Accept-Language" => 'pt-BR',
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $token,
        ];
    }

    /**
     * Busca pagamentos nas tabelas configuradas
     */
    public function searchPayments(array $filters): Collection
    {
        $results = collect();
        
        foreach ($filters as $filter) {
            $searchResults = $this->searchInTables(
                $filter['value'],
                $filter['type'] ?? 'merchant'
            );
            
            $results = $results->merge($searchResults);
        }
        
        return $results;
    }

     /**
     * Busca um valor em todas as tabelas configuradas
     */
    private function searchInTables(string $value, string $type): Collection
    {
        $results = collect();
        $tables = config('apypayment.tables', []);
        
        foreach ($tables as $tableConfig) {
            $model = app($tableConfig['model']);
            $query = $model::query();
            
            $column = $this->getSearchColumn($tableConfig['columns'], $type);
            
            if (str_contains($column, '->')) {
                $query->whereJsonContains($column, $value);
            } else {
                $query->where($column, $value);
            }
            
            $results = $results->merge($query->get());
        }
        
        return $results;
    }
    

    /**
     * Obtém a coluna correta para busca baseada no tipo
     */
    private function getSearchColumn(array $columns, string $type): string
    {
        return $type === 'reference' 
            ? $columns['reference']
            : $columns['merchant_transaction_id'];
    }

    /**
     * Gera um merchantTransactionId com prefixo configurável
     */
    public function generateMerchantId(
        bool $isRenewal = false, 
        ?string $customPrefix = null
    ): string {
        $prefixes = config('apypayment.prefixes');
        $prefix = $customPrefix ?? ($isRenewal ? $prefixes['renewal'] : $prefixes['default']);
        
        $lastId = ApyPayment::where('merchantTransactionId', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('merchantTransactionId');
            
        $lastNum = $lastId ? (int) substr($lastId, strlen($prefix)) : 0;
        
        return $prefix . str_pad($lastNum + 1, 9, '0', STR_PAD_LEFT);
    }


    /**
     * Verifica se um prefixo é válido
     */
    public function isValidPrefix(string $prefix): bool
    {
        return strlen($prefix) === 2 && ctype_alpha($prefix);
    }

    /**
     * Obtém a tabela padrão para armazenamento
     */
    public function getDefaultStorageTable(): string
    {
        return config('apypayment.storage.default_table', 'apy_payments');
    }

    public function listPayments() //:void
    {
        // Dispara a execução em segundo plano sem esperar
        try {
            $token = $this->getAccessToken();
            if (!$token) return;

            $response = $this->client->get($this->apiUrl . '/charges', [
                'headers' => ['Authorization' => 'Bearer ' . $token],
            ]);

            $payments = json_decode($response->getBody(), true)['payments'] ?? [];
            
            foreach ($payments as $payment) {
                $this->processPayment($payment);
            }

        } catch (\Exception $e) {
            \Log::error('Background payment processing error: ' . $e->getMessage());
        }
        dispatch(function () {
        });
    }

    private function processPayment(array $payment): void
    {

        $paymentData = [
            'id' => $payment['id'],
            'merchantTransactionId' => $payment['merchantTransactionId'],
            'type' => $payment['type'],
            'operation' => $payment['operation'],
            'amount' => $payment['amount'],
            'currency' => $payment['currency'],
            'status' => $payment['status'],
            'description' => $payment['description'],
            'paymentMethod' => $payment['paymentMethod'],
            'disputes' => $payment['disputes'],
            'applicationFeeAmount' => $payment['applicationFeeAmount'],
            'options' => $payment['options'],
            'createdDate' => Carbon::parse($payment['createdDate']),
            'updatedDate' => Carbon::parse($payment['updatedDate']),
            'reference' => [
                'referenceNumber' => $payment['reference']['referenceNumber'],
                'dueDate' => Carbon::parse($payment['reference']['dueDate'])->toDateTimeString(),
                'entity' => $payment['reference']['entity'],
            ],
        ];
        // Cria ou atualiza o pagamento
        ApyPayment::updateOrCreate(
            ['merchantTransactionId' => $paymentData['merchantTransactionId']],
            $paymentData
        );

        // Processa pagamentos com sucesso
        if ($paymentData['status'] === 'Success') {
             app('apypayment.updater')->executeOnSuccess($paymentData);
            // $dbPayment = Payment::where('reference', $payment['reference']['referenceNumber'])
            //                 ->where('state', '0')
            //                 ->first();
        }
    }
}