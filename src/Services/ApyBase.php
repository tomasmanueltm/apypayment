<?php

namespace TomasManuelTM\ApyPayment\Services;

use Carbon\Carbon;
use \Psr\Http\Message\ResponseInterface as IJson;
use TomasManuelTM\ApyPayment\Models\ApySys;
use TomasManuelTM\ApyPayment\Models\ApyMethod;
use TomasManuelTM\ApyPayment\Models\ApyPayment;

abstract class ApyBase
{
    /*
    * Metodos de HttpClients
    */



    protected function applications($token, ){
        $response =  $this->client->get($this->apiUrl . '/applications', [
                    'headers' => $this->getRequestHeaders($token)
        ]);

        return $this;
    }






    protected function generateMerchantId(bool $isRenewal = false, ?string $customPrefix = null): string
    {
        $prefixes = config('apypayment.prefixes');
        $prefix = $customPrefix ?? ($isRenewal ? $prefixes['renewal'] : $prefixes['default']);
        
        $lastId = ApyPayment::where('merchantTransactionId', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('merchantTransactionId');
            
        $lastNum = $lastId ? (int) substr($lastId, strlen($prefix)) : 0;
        
        return $prefix . str_pad($lastNum + 1, 9, '0', STR_PAD_LEFT);
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
    public function generateReference(): string
    {
        do {
            $reference = mt_rand(100000000, 999999999);
            $exists = ApySys::where('reference->referenceNumber', $reference)->exists();
        } while ($exists);

        return (string) $reference;
    }


    /**
     * Configura ResponseJson
     * @return array || null
     */
    private function isSucess($response){
        return ($response) ? (json_decode($response->getBody(), true)) : [];
    }

    /*
    *  Registrar metodos de pagamento 
    */
    protected function setMethods(IJson $array): void
    {
        $payments =  $this->isSucess($array) ? ($this->isSucess($array)) : [];
 
        try {
            foreach ($payments['applications'] as $data) {
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
            app('apylogger')->error('generateToken', ['Falha ao armazenar metodos de pagamentos '=> $e->getMessage()]);
        }
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
    protected function setPayments(IJson $array): void
    {
        $payments = $this->isSucess($array) ? ($this->isSucess($array)) : [];
        try {
            
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
                    'applicationFeeAmount' => $payment['applicationFeeAmount'],
                    'options' => $payment['options'],
                    'createdDate' => Carbon::parse($payment['createdDate']),
                    'updatedDate' => Carbon::parse($payment['updatedDate']),
                    'reference' => [
                        'referenceNumber' => $payment['reference']['referenceNumber'] ?? time(), // Usa timestamp se não existir
                        'dueDate' => Carbon::parse($payment['reference']['dueDate'] ?? date('Y-m-d', strtotime('+2 days'))),
                        'entity' => $payment['reference']['entity'] ?? '00083', // Valor padrão para entidade
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
            throw $th;
            app('apylogger')->error('setMethods', $th->getMessage());        


        }
    }
}