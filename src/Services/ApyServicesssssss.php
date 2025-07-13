<?php

namespace TomasManuelTM\ApyPayment\Services;

use Carbon\Carbon;
use RuntimeException;
use GuzzleHttp\Client;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\GuzzleException;
use TomasManuelTM\ApyPayment\Models\ApyToken;
use TomasManuelTM\ApyPayment\Models\ApySys;
use TomasManuelTM\ApyPayment\Models\ApyMethod;
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
                "Accept-Language" => config('apypayment.accept_language'),
                'Accept' => config('apypayment.accept'),
                'Content-Type' => config('apypayment.content_type'),
            ],
            'http_errors' => false
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
                // Token de acesso recuperado do banco de dados;
            }


            $response = $this->client->post($this->authUrl, [
                'form_params' => [
                    'grant_type' => config('apypayment.grant_type'),
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'resource' => $this->resource,
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            $this->currentToken($data);
            
            Log::info('Novo token de acesso gerado com sucesso');
            return $this->accessToken = $data['access_token'];

        } catch (GuzzleException $e) {
            return $e->getMessage();
            Log::error('Erro ao obter token de acesso: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Cabeçalhos padrão para requisições
     */
    private function getRequestHeaders(string $token): array
    {
        return [
            'Accept-Language' => config('apypayment.accept_language'),
            'Accept' => config('apypayment.accept'),
            'Content-Type' => config('apypayment.content_type'),
            'Authorization' => 'Bearer ' . $token,
        ];
    }

    
    /**
     * Armazena novo token no banco de dados
     */
    private function currentToken(array $tokenData): void
    {
         try {
            ApyToken::updateOrCreate(
                ['istoken' => true],
                [
                    'token' => $tokenData['access_token'],
                    'expires_on' => $tokenData['expires_on'],
                    'expires_in' => $tokenData['expires_in'],
                    'istoken' => true,
                ]
            );
            Log::debug('Token armazenado no banco de dados');
        } catch (\Exception $e) {
            Log::error('Falha ao armazenar token: ' . $e->getMessage());
        }
    }

    
    /**
     * Gera uma referência única para pagamento
     */
    public function generateReference(): string
    {
        do {
            $reference = mt_rand(100000000, 999999999);
            $exists = ApySys::where('reference->referenceNumber', $reference)->exists();
        } while ($exists);

        return (string) $reference;
    }

    /**
     * Processa a resposta da API e toma ações corretivas quando necessário
     * 
     * @param array $paymentData Dados originais do pagamento
     * @param array $apiResponse Resposta da API
     * @param int $attempt Número da tentativa atual
     * @return array Retorna o status processado ou novo conjunto de dados para retentativa
    */
    private function handlePaymentResponse(array $paymentData, array $apiResponse, int $attempt = 1): array
    {
        $statusCode = $apiResponse['status'] ?? 200;
        $responseStatus = $apiResponse['responseStatus'] ?? [];
        $isSuccessful = $responseStatus['successful'] ?? false;
        $errorCode = $responseStatus['code'] ?? 0;
        $errorMessage = $responseStatus['message'] ?? 'Erro desconhecido';

        // Log::info('Resposta da API de pagamento recebida', [
        //     'codigo_status' => $statusCode,
        //     'tentativa' => $attempt
        // ]);
        
        // Casos de sucesso (200 OK ou 202 Accepted com código 101)
         if (($statusCode === 200 || $statusCode === 202) && ($isSuccessful || $errorCode === 101)) {
            return [
                'status' => 'success',
                'data' => $apiResponse,
                'attempts' => $attempt
            ];
        }
        

        $paymentData['currency'] = config('apypayment.default_currency', 'AOA');
        
        // Tratamento de erros específicos
        switch ($errorCode) {
            // Caso de merchantTransactionId duplicado
            case 726:
                if ($attempt >= 3) {
                    \Log::error('Payment failed - Duplicate merchantTransactionId after max attempts', [
                        'error' => $errorMessage,
                        'code' => $errorCode,
                        'attempts' => $attempt
                    ]);
                    return [
                        'status' => 'error',
                        'error' => 'Número máximo de tentativas atingido para merchantTransactionId',
                        'original_error' => $errorMessage
                    ];
                }
                
                // Gera novo merchantTransactionId
                $newTransactionId = $this->generateMerchantId();
                $paymentData['merchantTransactionId'] = $newTransactionId;
                
                \Log::warning('Retrying payment - Duplicate merchantTransactionId', [
                    'new_id' => $newTransactionId,
                    'attempt' => $attempt + 1
                ]);
                
                return [
                    'status' => 'retry',
                    'new_data' => $paymentData,
                    'reason' => 'merchantTransactionId duplicado',
                    'attempts' => $attempt + 1
                ];

            // Caso de referência duplicada
            case 763:
                if ($attempt >= 3) {
                    \Log::error('Payment failed - Duplicate reference after max attempts', [
                        'error' => $errorMessage,
                        'code' => $errorCode,
                        'attempts' => $attempt
                    ]);
                    return [
                        'status' => 'error',
                        'error' => 'Número máximo de tentativas atingido para referência',
                        'original_error' => $errorMessage
                    ];
                }
                
                // Gera nova referência
                $newReference = $this->generateUniqueReference();
                $paymentData['paymentInfo']['referenceNumber'] = $newReference;
                
                \Log::warning('Retrying payment - Duplicate reference', [
                    'new_reference' => $newReference,
                    'attempt' => $attempt + 1
                ]);
                
                return [
                    'status' => 'retry',
                    'new_data' => $paymentData,
                    'reason' => 'Referência duplicada',
                    'attempts' => $attempt + 1
                ];

            // Outros erros não recuperáveis
            default:
                \Log::error('Payment failed', [
                    'status' => 'error',
                    'error' => $errorMessage,
                    'code' => $errorCode,
                    'attempts' => $attempt
                ]);
                
                return [
                    'status' => 'error',
                    'error' => $errorMessage,
                    'code' => $errorCode,
                    'attempts' => $attempt
                ];
        }
    }

    /**
     * Obter todos metodos de pagamento disponíveis
     */
    public function getPaymentMethods(): ?array
    {
        $token = $this->getAccessToken();
        if (!$token) {
            return null;
        }

        try {
            $response = $this->client->get($this->apiUrl . '/applications', [
                'headers' => $this->getRequestHeaders($token),
            ]);
            $this->getPaymentMethodsType(json_decode($response->getBody(), true));
            return json_decode($response->getBody(), true);

        } catch (GuzzleException $e) {
            Log::error('IPay Get Payment Methods Error: ' . $e->getMessage());
            return null;
        }
    }

    

    /*
    *  Sincroniza tipos de pagamento com o banco de dados local
    */
    private function getPaymentMethodsType(array $paymentData): void
    {
        try {
            foreach ($paymentData['applications'] as $data) {
                ApyMethod::updateOrCreate(['hash' => $data['id']], [
                    'hash' => $data['id'],
                    'name' => $data['name'],
                    'method'=> $data['paymentMethod'],
                    'isActive'=> $data['isActive'],
                    'isDefault'=> $data['isDefault'],
                    'type'=> $data['paymentMethod'].'_'.$data['applicationKyes'][0]['apiKey'],
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Register Types Payment : ' . $e->getMessage());
        }
    }


    /**
     * Cria um novo pagamento na API
     * 
     * @param array $paymentData Dados do pagamento
     * @return array|null Resposta da API ou null em caso de erro
     */

    public function createPayment(array $paymentData, int $maxAttempts = 3): ?array
    {
        $attempt = 1;
        $lastError = null;
        
        $paymentData['merchantTransactionId'] = $this->generateMerchantId();
        $paymentData['currency'] = config('apypayment.default_currency', 'AOA');
        $paymentData['paymentMethod'] =  $this->getPaymentType(config('apypayment.default_payment_method'));


        do {
            $token = $this->getAccessToken();
            if (!$token) {
                Log::error('Failed to get access token');
                return null;
            }


            
            try {
                $response = $this->client->post($this->apiUrl . '/charges', [
                    'headers' => $this->getRequestHeaders($token),
                    'json' => $paymentData,
                    'http_errors' => false
                ]);

                $responseData = json_decode($response->getBody(), true);
                $responseData['status'] = $response->getStatusCode();
                $this->listPayments();
                // Processa a resposta
                $result = $this->handlePaymentResponse($paymentData, $responseData, $attempt);
                
                switch ($result['status']) {
                    case 'success':
                        // Log::info('Referencia gerada');
                        $this->syncPaymentToDatabase(array_merge($responseData, ['merchantTransactionId'=>$paymentData['merchantTransactionId'], 'description'=>$paymentData['description'].' '.$paymentData['paymentInfo']['PaymentInfo1'], 'amount'=>$paymentData['amount']]));
                        return [
                            'message' => $responseData['responseStatus']['message'],
                            "status" => $responseData['responseStatus']['status'],
                            "reference" => $responseData['responseStatus']['reference']['referenceNumber'],
                            "entity" => $responseData['responseStatus']['reference']['entity'],
                            "expiration" => $responseData['responseStatus']['reference']['dueDate'],
                        ];
                        
                    case 'retry':
                        $paymentData = $result['new_data'];
                        Log::warning(['Retrying payment'=>$result]);
                        break;
                        
                    case 'error':
                        Log::error(['Payment failed'=>$result]);
                        return null;
                }
                
            } catch (\Exception $e) {
                Log::error(['Payment request failed'=>[
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'attempt' => $attempt
                ]]);
                return null;
            }
            
            $attempt++;
            sleep(1); // Pequena pausa entre tentativas
                
        } while ($attempt <= $maxAttempts);

            Log::error(['Max payment attempts reached'=>[
                'last_data' => $paymentData,
                'last_error' => $lastError
                ]
            ]);
        
        return null;
    }
  

    /**
     * Busca o tipo de pagamento com fallback para o método padrão
     * 
     * @param string $type Método de pagamento a ser pesquisado
     * @return string Tipo de pagamento encontrado ou o padrão
     * @throws \RuntimeException Quando nenhum método é encontrado
     */
    private function getPaymentType(string $type): string
    {
        // Busca o método específico
        $paymentMethod = ApyMethod::where('method', $type)->first();
        
        if ($paymentMethod) {
            return $paymentMethod->type;
        }
        
        // Fallback para o método padrão
        $defaultMethod = ApyMethod::where('isDefault', true)->first();
        
        if (!$defaultMethod) {
            throw new \RuntimeException('Nenhum método de pagamento padrão configurado');
        }
        
        return $defaultMethod->type;
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
            // Log::error('Payment sync failed: ' . $e->getMessage(). ' Line '. $e->getLine());
        }
    }

    /**
     * Transforma os dados da API para o formato do banco de dados
     */
    private function transformPaymentData(array $apiData): array
    {
        return [
            'type' => $apiData['responseStatus']['source'],
            'amount' => $apiData['amount'],
            'status' => $apiData['responseStatus']['status'],
            'description' => $apiData['description'] ?? null,
            'dueDate' => Carbon::parse($apiData['responseStatus']['reference']['dueDate'] ?? now()->addDays(2)),
            'merchantTransactionId' => ($apiData['merchantTransactionId']),
            'reference' => $apiData['responseStatus']['reference']['referenceNumber'],
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
                $query = ApySys::query();
                
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
     * Gera um merchantTransactionId com prefixo configurável garantindo unicidade
     */
    public function generateMerchantId(bool $isRenewal = false, ?string $customPrefix = null, int $maxAttempts = 100): string 
    {
        $prefixes = config('apypayment.prefixes');
        $prefix = $customPrefix ?? ($isRenewal ? $prefixes['renewal'] : $prefixes['default']);
        
        // Primeiro tenta obter o último ID sequencial
        $lastId = ApySys::where('merchantTransactionId', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('merchantTransactionId');
            
        $lastNum = $lastId ? (int) substr($lastId, strlen($prefix)) : 0;
        $newId = $prefix . str_pad($lastNum + 1, 9, '0', STR_PAD_LEFT);
        
        // Verifica se o ID já existe (caso raro de concorrência)
        $exists = ApySys::where('merchantTransactionId', $newId)->exists();
        
        // Se existir, tenta encontrar um ID disponível
        $attempts = 0;
        while ($exists && $attempts < $maxAttempts) {
            $lastNum++;
            $newId = $prefix . str_pad($lastNum + 1, 9, '0', STR_PAD_LEFT);
            $exists = ApySys::where('merchantTransactionId', $newId)->exists();
            $attempts++;
        }
        
        if ($exists) {
            Log::error('Falha ao gerar ID único para transação', [
                'prefixo' => $prefix,
                'ultimo_id' => $lastId,
                'tentativas' => $attempts,
                'proximo_id_tentado' => $newId
            ]);
            throw new \RuntimeException("Falha ao gerar ID único após {$maxAttempts} tentativas");
        }
        
        return $newId;
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

    public function listPayments() :void
    {
        // Dispara a execução em segundo plano sem esperar
        try {
            $token = $this->getAccessToken();
            if (!$token) return;

            $response = $this->client->get($this->apiUrl . '/charges?limit=1000000', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                ],
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
                'referenceNumber' => $payment['reference']['referenceNumber'] ?? time(),
                'dueDate' => Carbon::parse(($payment['reference']['dueDate'] ?? date('Y-m-d', strtotime('+2 days'))))->toDateTimeString(),
                'entity' => $payment['reference']['entity'] ?? '00083',
            ],
        ];

        if($paymentData['reference']) {
            // Cria ou atualiza o pagamento
            ApySys::updateOrCreate(
                ['merchantTransactionId' => $paymentData['merchantTransactionId']],
                $paymentData
            );
    
            // Processa pagamentos com sucesso
            if ($paymentData['status'] === 'Success') {
                //  app('apypayment.status_update')->executeOnSuccess($paymentData);
                // $dbPayment = Payment::where('reference', $payment['reference']['referenceNumber'])
                //                 ->where('state', '0')
                //                 ->first();
            }
        }
        
    }
}