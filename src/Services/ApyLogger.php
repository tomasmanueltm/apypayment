<?php

namespace TomasManuelTM\ApyPayment\Services;

use Illuminate\Support\Facades\Log;

class ApyLogger
{
    public function info(string $message, array $context = []): void
    {
        Log::info("[APY] $message", $context);
    }

    public function error(string $message, array $context = []): void
    {
        Log::error("[APY] $message", $context);
    }

    public function warning(string $message, array $context = []): void
    {
        Log::warning("[APY] $message", $context);
    }

    public function debug(string $message, array $context = []): void
    {
        Log::debug("[APY] $message", $context);
    }

    public function paymentSuccess(array $response): void
    {
        $this->info('Pagamento processado com sucesso', [
            'id_transacao' => $response['id'] ?? null,
            'status' => $response['status'] ?? null,
            'valor' => $response['amount'] ?? null
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