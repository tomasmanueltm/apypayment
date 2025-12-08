<?php

namespace TomasManuelTM\ApyPayment\Services;

use Carbon\Carbon;
use RuntimeException;
use Illuminate\Support\Collection;
use TomasManuelTM\ApyPayment\Models\ApySys;
use TomasManuelTM\ApyPayment\Services\ApyBase;
use TomasManuelTM\ApyPayment\Services\ApyAuth;
use TomasManuelTM\ApyPayment\Models\ApyMethod;
use TomasManuelTM\ApyPayment\Models\ApyPayment;
use TomasManuelTM\ApyPayment\Events\PaymentProcessEvent;


class ApyService extends ApyBase
{
    private ApyAuth $auth;

    public function __construct(ApyAuth $auth) {
        $this->auth = $auth;

    }
    
    /**
     * Obter todos metodos de pagamento disponíveis
     */
    public function getApplications() : Array
    {
        $response = $this->auth->applications(); 
        $this->setMethods($response);
        return ($response)  ? json_decode($response->getBody(), true) : [];
    }

    /**
     * Obter todos os pagamentos 
     */
    public function getPayments(): Array
    {
        $response = $this->auth->payments();
        $this->setPayments($response);
        //app('apylogger')->sucesso('getPayments', $response);
     //   \Illuminate\Support\Facades\Log::info([$response]);
     return ($response) ? json_decode($response->getBody(), true) :  [];
    }

    public function getPaymentAsyncFix(): Array
    {

        $response = $this->auth->payments();
        $result = ($response) ? json_decode($response->getBody(), true) :  [];
        
        register_shutdown_function(function() use ($result) {
            $this->setPayment($result);
        });
        
        return $result;
    }
    
    
    public function getPaymentAsync(): Array
    {
        $response = $this->auth->payments();
        $result = ($response) ? json_decode($response->getBody(), true) :  [];
        
        $filePath = storage_path('app/payment.json');
        file_put_contents($filePath, json_encode($result));
        
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        
        ignore_user_abort(true);
        set_time_limit(0);
        
         $this->setPayment($filePath);
        
        return $result;
    }

    public function setPayment($filePath) {
        if (!file_exists($filePath)) {
            return;
        }
        
        $res = json_decode(file_get_contents($filePath), true);
        $allData = [];
        
        foreach ($res['payments'] as $payment) {
            $allData[] = [
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
                'options' => json_encode($payment['options']),
                'createdDate' => Carbon::parse($payment['createdDate']),
                'updatedDate' => Carbon::parse($payment['updatedDate']),
                'reference' => json_encode([
                    'referenceNumber' => $payment['reference']['referenceNumber'] ?? time(),
                    'dueDate' => Carbon::parse($payment['reference']['dueDate'] ?? date('Y-m-d', strtotime('+2 days'))),
                    'entity' => $payment['reference']['entity'] ?? '00083',
                ]),
            ];
        }
        
        if (!empty($allData)) {
            ApySys::upsert($allData, ['merchantTransactionId'], ['status', 'updatedDate', 'amount', 'currency', 'description', 'paymentMethod', 'disputes', 'applicationFeeAmount', 'options', 'reference']);
        }
        
        @unlink($filePath);
    }


    public function createPayment(array $json) {
      return  $this->auth->create($json);
    }
}