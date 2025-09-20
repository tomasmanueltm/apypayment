<?php

namespace TomasManuelTM\ApyPayment\Services;

abstract class ApyRepository
{
    protected function generateMerchantId(): string
    {
        return 'PT' . str_pad(random_int(1, 999999999), 9, '0', STR_PAD_LEFT);
    }

    protected function transformJson(array $data): array
    {
        return $data;
    }

    protected function handlePaymentResponse(array $payload, array $response, int $attempt): array
    {
        if ($response['status'] === 200) {
            return ['status' => 'success'];
        }
        return ['status' => 'error'];
    }

    protected function formatSuccessResponse(array $data): array
    {
        return [
            'success' => true,
            'merchantTransactionId' => $data['merchantTransactionId'] ?? null,
            'reference' => $data['reference'] ?? null,
            'status' => $data['status'] ?? 'pending'
        ];
    }

    protected function formatErrorResponse(array $data): array
    {
        return [
            'success' => false,
            'error' => $data['message'] ?? 'Erro desconhecido'
        ];
    }

    protected function prepareRetryData(array $data, array $result): array
    {
        return $data;
    }

    protected function setPaymentTable($response): void
    {
        // Implementar se necessário
    }
}