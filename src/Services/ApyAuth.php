<?php

namespace TomasManuelTM\ApyPayment\Services;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use TomasManuelTM\ApyPayment\Models\{
    ApySys,
    ApyToken,
    ApyMethod,
    ApyPayment,
};

use TomasManuelTM\ApyPayment\Exceptions\{
    InvalidRequestException,
    MerchantIdGenerationException,
    PaymentCreationException,
    PaymentNotFoundException
};
use Illuminate\Support\Facades\DB;
use \Psr\Http\Message\ResponseInterface as IJson;
use TomasManuelTM\ApyPayment\Services\ApyRepository;


class ApyAuth extends ApyRepository
{
    public Client $client;
    private string $authUrl;
    private string $clientId;
    private string $clientSecret;
    private string $resource;
    private ?string $accessToken = null;
    public string $apiUrl;
    private $logger;
    
    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->initializeConfig();
        $this->logger = app('apylogger');


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
     * Configura o cliente HTTP para não lançar exceções automaticamente
     */
    private function disableHttpErrors(): void
    {
        // Método recomendado - recriar o client com nova configuração
        $config = array_merge($this->client->getConfig(), [
            'http_errors' => false
        ]);
        
        $this->client = new \GuzzleHttp\Client($config);
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

        /**
     * Busca o tipo de pagamento com fallback para o método padrão
     * 
     * @param string $type Método de pagamento a ser pesquisado
     * @return string Tipo de pagamento encontrado ou o padrão
     * @throws \RuntimeException Quando nenhum método é encontrado
     */
    private function getMethods(string $type): string
    {
        // Busca o método específico
        $paymentMethod = ApyMethod::where('method', $type)->first();
        
        if ($paymentMethod) {
            return $paymentMethod->type;
        }
        
        // Fallback para o método padrão
        $defaultMethod = ApyMethod::where('isDefault', true)->first();
        
        if (!$defaultMethod) {
            $this->logger->error('getMethods', ['Nenhum método de pagamento padrão configurado']);
        }
        
        return $defaultMethod->type;
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
            app('apylogger')->success('generateToken', ['Token armazenado no banco de dados ']);
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


    /**
     * Obtém a tabela padrão para armazenamento
    */
    public function getDefaultStorageTable(): string
    {
        return config('apypayment.storage.default_table', 'apy_payments');
    }


    private function defaultPaymentTable(array $data): void
    {
        $this->logger->info('defaultPaymentTable', ['Dados do pagamento' => $data]);
       // Define quais campos são permitidos para inserção
        $allowedFields = [
            'reference',
            'merchantTransactionId', 
            'type',
            'description',
            'status',
            'amount',
            'expiration'
        ];

        // Filtra apenas os campos permitidos
        $filteredData = array_intersect_key($data, array_flip($allowedFields));

        // Insere os dados filtrados
        ApyPayment::insert($filteredData);
        // DB::table($this->getDefaultStorageTable())->create($data);
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



    /**ENDPOINTS APPYPAY**/ 
    
    /**
     * Obtém a lista de métodos de pagamento disponíveis
     * @return Response|null Resposta da API ou null em caso de falha
    */
    public  function applications() : ? IJson
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
    
    public function payments(): ?IJson
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

    /**
     * Obtém a lista de pagamentos e atualizar os registros
     * @param string $token Token de acesso
     * @return Response|null Resposta da API ou null em caso de falha   
     * */
    public function paymentsRefresh() : void
    {
        try {
            $token = $this->getAccessToken();
            if (!$token) {
                app('apylogger')->error('paymentsRefresh', ['Falha ao obter token de acesso ']);
            }

            $response = $this->client->get($this->apiUrl . '/charges?limit=100000000', [
                'headers' => $this->getRequestHeaders($token)
            ]);
            $this->setPaymentTable($response);
        } catch (GuzzleException $e) {
            app('apylogger')->error('paymentsRefresh', ['Erro ao obter refresh de pagamentos ']);
        }
    }
 

    /**
     * Cria um novo pagamento
     * 
     * @param array $data Dados do pagamento (deve conter 'amount', 'description', 'reference') // caso reference for null sera criado
     * @return array Dados do pagamento criado
     * @throws PaymentCreationException|MerchantIdGenerationException
     */
    public function creates(array $data)
    {
        try {
            // 1. Autenticação
            $token = $this->getAccessToken();
            if (!$token) {
                throw new \RuntimeException('Falha ao obter token de acesso');
            }

            // . Geração do Merchant ID
            $merchantId = $this->generateMerchantId();
            if (!$merchantId) {
                throw new MerchantIdGenerationException(100);
            }

            $this->disableHttpErrors();

            // . Configuração dos dados básicos
            $payload = array_merge($data, [
                'merchantTransactionId' => $merchantId,
                // 'currency' => config('apypayment.default_currency', 'AOA'),
                'paymentMethod' => $this->getMethods(config('apypayment.default_payment_method'))
            ]);
            
            // . Requisição à API
            $response = $this->client->post($this->apiUrl . '/charges', [
                'headers' => $this->getRequestHeaders($token),
                'json' => $payload,
            ]);
            
            // 5. Processamento da resposta
            $payment = json_decode($response->getBody(), true);
            return response()->json($payment);
            
            // . Log e retorno
            $this->logger->paymentSuccess($payment);
            return $payment;

        } catch (\Throwable $e) {
            return  [$e->getLine(), $e->getFile(), $e->getMessage()];
        }
    }
    

    public function create(array $data) : array
    {
        $maxAttempts = 50;
        $attempt = 1;
        $lastError = null;
        
        // return app('ApyBase')->generateMerchantIdOld();
        
        // 1. Autenticação
            $token = $this->getAccessToken();
            if (!$token) {
                $this->logger->error('Falha ao obter token de acesso');
                return null;
            }
            
            do {
                try {
                // 2. Geração do Merchant ID (com regeneração em caso de duplicado)
                $merchantId = $this->generateMerchantId();
                if (!$merchantId) {
                    throw new MerchantIdGenerationException($attempt);
                }
                // 3. Preparar payload
                $payload = array_merge($data, [
                    'merchantTransactionId' => $merchantId,
                    'paymentMethod' => $this->getMethods(config('apypayment.default_payment_method'))
                ]);
                
                // return [$merchantId];

                // 4. Fazer requisição
                $response = $this->client->post($this->apiUrl . '/charges', [
                    'headers' => $this->getRequestHeaders($token),
                    'json' => $payload,
                    'http_errors' => false
                ]);

                // 5. Processar resposta
                $responseData = json_decode($response->getBody(), true);
                $responseData['status'] = $response->getStatusCode();

                // 6. Analisar resposta
                $result = $this->handlePaymentResponse($payload, $responseData, $attempt);
                
                switch ($result['status']) {
                    case 'success':
                        $this->logger->paymentSuccess($responseData);
                        $this->defaultPaymentTable(array_merge(
                            $this->formatSuccessResponse($responseData),
                            ['merchantTransactionId'=>$payload['merchantTransactionId'], 'amount'=>$payload['amount'],  'description'=>$payload['description']]
                        ));
                        $this->paymentsRefresh();
                        return $this->formatSuccessResponse($responseData);
                        
                    case 'retry':
                        $this->logger->warning('Tentando novamente', [
                            'attempt' => $attempt,
                            'reason' => $result['reason']
                        ]);
                        $data = $this->prepareRetryData($data, $result);
                        break;
                        
                    case 'error':
                        $this->logger->error('Falha no pagamento', $responseData);
                        return $this->formatErrorResponse($responseData);
                }

            } catch (MerchantIdGenerationException $e) {
                $this->logger->error('Falha ao gerar Merchant ID', [
                    'attempt' => $attempt,
                    'error' => $e->getMessage()
                ]);
                return null;
            } catch (\Exception $e) {
                $lastError = [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'message' => $e->getMessage()
                ];
                $this->logger->error('Erro na requisição', $lastError);
            }

            $attempt++;
            if ($attempt <= $maxAttempts) {
                sleep(1); // Pausa entre tentativas
            }

        } while ($attempt <= $maxAttempts);

        $this->logger->error('Número máximo de tentativas atingido', [
            'last_error' => $lastError
        ]);
        return [];
    }

  



   
}