<?php

namespace TomasManuelTM\ApyPayment\Services;

use Carbon\Carbon;
use TomasManuelTM\ApyPayment\Models\ApySys;
use \Psr\Http\Message\ResponseInterface as IJson;

abstract class ApyRepository
{
    
      /**
     * Gera um merchantTransactionId com prefixo configurável garantindo unicidade
     */
    public function generateMerchantId(string $customPrefix = null, int $maxAttempts = 100): string 
    {
        $prefixes = config('apypayment.prefixes');
        $prefix = $customPrefix ?? ($customPrefix ? $prefixes['renewal'] : $prefixes['default']);
        
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
            app('apylogger')->error('Falha ao gerar ID único para transação', [
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
     * Prepara os dados para uma nova tentativa baseada no tipo de erro
     * 
     * @param array $originalData Dados originais da requisição
     * @param array $retryInfo Informações do retorno de handlePaymentResponse()
     * @return array Novos dados para tentativa com:
     *               - Referência ou Merchant ID atualizados conforme necessário
     * 
     * Modifica os dados da requisição conforme necessário para casos onde:
     * - Merchant ID estava duplicado (será gerado novo automaticamente)
     * - Número de referência estava duplicado (gera nova referência)
     */
    protected function prepareRetryData(array $originalData, array $retryInfo): array
    {
        $newData = $originalData;
        
        if ($retryInfo['reason'] === 'reference_duplicated') {
            $newData['paymentInfo']['referenceNumber'] = $this->generateReference();
        }
        
        return $newData;
    }

    /**
     * Formata uma resposta de sucesso padrão para o cliente
     * 
     * @param array $responseData Dados completos da resposta da API
     * @return array Resposta padronizada com:
     *               - message: Mensagem descritiva
     *               - status: Status do pagamento
     *               - reference: Número de referência
     *               - entity: Entidade (banco/processador)
     *               - expiration: Data de expiração
     * 
     * Extrai e estrutura os dados mais importantes da resposta da API
     * para retornar ao cliente de forma consistente
     */

    protected function formatSuccessResponse(array $responseData): array
    {
        return [
            'message' => $responseData['responseStatus']['message'] ?? 'Pagamento criado com sucesso',
            'status' => $responseData['responseStatus']['status'] ?? 'Pending',
            'statusamount' => $responseData['responseStatus']['status'] ?? 'Pending',
            'reference' => $responseData['responseStatus']['reference']['referenceNumber'] ?? null,
            'entity' => $responseData['responseStatus']['reference']['entity'] ?? null,
            'expiration' => Carbon::parse($responseData['responseStatus']['reference']['dueDate'])->format("Y-m-d") ?? null,
            'type' => $responseData['responseStatus']['source'] ?? 'REF',
        ];
    }

    /**
     * Formata uma resposta de erro padrão para o cliente
     * 
     * @param array $responseData Dados completos da resposta da API
     * @return array Resposta de erro padronizada com:
     *               - error: true
     *               - code: Código do erro
     *               - message: Mensagem de erro
     *               - details: Detalhes adicionais
     * 
     * Transforma os diversos possíveis erros da API em um formato
     * consistente para tratamento pelo cliente/sistema
     */

    protected function formatErrorResponse(array $responseData): array
    {
        return [
            'error' => true,
            'code' => $responseData['responseStatus']['code'] ?? 'unknown',
            'message' => $responseData['responseStatus']['message'] ?? 'Erro desconhecido',
            'details' => $responseData['responseStatus']['sourceDetails'] ?? null
        ];
    }

    public function generateReference(): string
    {
        do {
            $reference = mt_rand(100000000, 999999999);
            $exists = ApySys::where('reference->referenceNumber', $reference)->exists();
        } while ($exists);

        return (string) $reference;
    }


    /**
     * Analisa a resposta da API de pagamento e determina a ação apropriada
     * 
     * @param array $requestData Dados originais enviados na requisição
     * @param array $responseData Resposta recebida da API
     * @param int $attempt Número da tentativa atual
     * @return array Resultado com:
     *               - 'status': 'success'|'retry'|'error'
     *               - 'reason': Motivo específico (para retry/erro)
     *               - 'new_data': Dados modificados (apenas para 'retry')
     * 
     * Esta função examina os códigos de erro específicos da API e decide se:
     * - O pagamento foi bem-sucedido
     * - Deve ser tentado novamente (com dados modificados)
     * - Deve ser considerado como erro definitivo
     */
    protected function handlePaymentResponse(array $requestData, array $responseData, int $attempt): array
    {
        // Caso de sucesso
        if (($responseData['status'] === 200  || $responseData['status'] === 202  || $responseData['responseStatus']['code'] === 101) && ($responseData['responseStatus']['successful'])) {
            return ['status' => 'success'];
        }

        // Casos que podem ser tentados novamente
        if ($responseData['status'] === 400) {
            $code = $responseData['responseStatus']['code'] ?? null;
            
            // Merchant ID duplicado - gerar novo
            if ($code === 726) {
                return [
                    'status' => 'retry',
                    'reason' => 'merchant_duplicated',
                    'new_data' => $requestData // Mantém os mesmos dados mas será gerado novo merchantId
                ];
            }
            
            // Referência duplicada - gerar nova referência
            if ($code === 763) {
                $newData = $requestData;
                $newData['paymentInfo']['referenceNumber'] = $this->generateReference();
                return [
                    'status' => 'retry',
                    'reason' => 'reference_duplicated',
                    'new_data' => $newData
                ];
            }
        }

        // Outros erros
        return [
            'status' => 'error',
            'code' => $responseData['responseStatus']['code'] ?? 'unknown',
            'message' => $responseData['responseStatus']['message'] ?? 'Erro desconhecido'
        ];
    }



    protected function transformPaymentData(array $apiData): array
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
     * Gera uma referência única para pagamento
    */



    /**
     * Configura ResponseJson
     * @return array || null
     */
    private function isSucess($response){
        return ($response) ? (json_decode($response->getBody(), true)) : [];
    }


    /*
    *
     * Processa os dados de um pagamento e armazena no banco de dados.
     * - Padroniza os dados do pagamento
     * - Converte datas para o formato Carbon
     * - Define valores padrão para campos opcionais
     * - Armazena/atualiza no banco via ApySys
     * - Trata pagamentos com status 'Success' (comentado para implementação futura)
     *
     * @param array $payment Dados brutos do pagamento recebidos da API
     * @return void
    */
    public function setPaymentTable(IJson $array): void
    {
        $payments = $this->isSucess($array) ? ($this->isSucess($array)) : [];
        try {
            // app('apylogger')->info('setPayments', ['payments' => $payments]);
            foreach ($payments['payments'] as $key => $payment) {
                # code...
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
                    'applicationFeeAmount' => $payment['applicationFeeAmount'] ?? 0,
                    'options' => $payment['options'] ?? [],
                    'createdDate' => Carbon::parse($payment['createdDate']),
                    'updatedDate' => Carbon::parse($payment['updatedDate']),
                    'reference' => [
                        'referenceNumber' => $payment['reference']['referenceNumber'] ?? time(), // Usa timestamp se não existir
                        'dueDate' => Carbon::parse($payment['reference']['dueDate'] ?? date('Y-m-d', strtotime('+2 days'))),
                        'entity' => $payment['reference']['entity'] ?? '00000', // Valor padrão para entidade
                    ],
                ];
        
                // Armazena/atualiza o registro no banco de dados
                ApySys::updateOrCreate(['merchantTransactionId' => $paymentData['merchantTransactionId']],  $paymentData);
                
                // Lógica para pagamentos bem-sucedidos (implementação futura)
                if ($paymentData['status'] === 'Success') {
                    // Exemplo comentado para tratamento futuro:
                    // app('apypayment.status_update')->executeOnSuccess($paymentData);
                    // $dbPayment = Payment::where('reference', $paymentData['reference']['referenceNumber'])
                    //                 ->where('state', '0')
                    //                 ->first();
                }
            }
        } catch (\Throwable $th) {
            app('apylogger')->error('setMethods', [$th->getMessage()]);        
        }
    }
}