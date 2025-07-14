<?php

namespace TomasManuelTM\ApyPayment\Services;

use TomasManuelTM\ApyPayment\Models\ApySys;
use TomasManuelTM\ApyPayment\Models\ApyPayment;

abstract class ApyBase
{
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
    
    
    protected function getRequestHeaders(string $token): array
    {
        return [
            'Accept-Language' => config('apypayment.accept_language'),
            'Accept' => config('apypayment.accept'),
            'Content-Type' => config('apypayment.content_type'),
            'Authorization' => 'Bearer ' . $token,
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
    public function generateReference(): string
    {
        do {
            $reference = mt_rand(100000000, 999999999);
            $exists = ApySys::where('reference->referenceNumber', $reference)->exists();
        } while ($exists);

        return (string) $reference;
    }
}