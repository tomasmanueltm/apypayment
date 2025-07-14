<?php

namespace TomasManuelTM\ApyPayment\Logs;

use Illuminate\Support\Facades\Log;

class ApyLogger
{
    public function info(string $message, array $context = []): void
    {
        Log::info("[APYPAYMENT] $message", $context);
    }

    public function error(string $message, array $context = []): void
    {
        Log::error("[APYPAYMENT] $message", $context);
    }

    public function success(string $message, array $context = []): void
    {
        Log::warning("[APYPAYMENT] $message", $context);
    }

    public function warning(string $message, array $context = []): void
    {
        Log::warning("[APYPAYMENT] $message", $context);
    }

    public function debug(string $message, array $context = []): void
    {
        Log::debug("[APYPAYMENT] $message", $context);
    }

    public function paymentSuccess(array $response): void
    {
        $this->info('Pagamento processado com sucesso', [
            'id_transacao' => $response['id'] ?? null,
            'status' => $response['status'] ?? null,
            'valor' => $response['amount'] ?? null,
            'reference' => $response['responseStatus']['reference']['referenceNumber'] ?? null
        ]);
    }

    public function paymentError(array $error): void
    {
        $this->error('Falha no processamento do pagamento', [
            'codigo' => $error['code'] ?? null,
            'mensagem' => $error['message'] ?? null,
            'tentativas' => $error['attempts'] ?? null
        ]);
    }
}