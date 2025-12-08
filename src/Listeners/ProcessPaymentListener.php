<?php

namespace TomasManuelTM\ApyPayment\Listeners;

use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use TomasManuelTM\ApyPayment\Events\PaymentProcessEvent;
use TomasManuelTM\ApyPayment\Models\ApySys;

class ProcessPaymentListener implements ShouldQueue
{
    public function handle(PaymentProcessEvent $event): void
    {
        $filePath = $event->paymentData['file'];
        
        if (!file_exists($filePath)) {
            return;
        }
        
        $result = json_decode(file_get_contents($filePath), true);
        
        $merchantIds = array_column($result['payments'], 'merchantTransactionId');
        $existing = ApySys::whereIn('merchantTransactionId', $merchantIds)
            ->pluck('merchantTransactionId')
            ->toArray();
        
        $toInsert = [];
        $toUpdate = [];
        
        foreach ($result['payments'] as $payment) {
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
                    'dueDate' => Carbon::parse($payment['reference']['dueDate'] ?? date('Y-m-d', strtotime('+2 days'))),
                    'entity' => $payment['reference']['entity'] ?? '00083',
                ],
            ];
            
            if (in_array($payment['merchantTransactionId'], $existing)) {
                $toUpdate[] = $paymentData;
            } else {
                $toInsert[] = $paymentData;
            }
        }
        
        if (!empty($toInsert)) {
            ApySys::insert($toInsert);
        }
        
        foreach ($toUpdate as $data) {
            ApySys::where('merchantTransactionId', $data['merchantTransactionId'])->update($data);
        }
        
        @unlink($filePath);
    }
}