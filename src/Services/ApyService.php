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
use TomasManuelTM\ApyPayment\Exceptions\InvalidRequestException;


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
        return ($response) ? json_decode($response->getBody(), true) :  [];
    }


    /**
     * Criar um novo pagamento
     * @param array $data Dados do pagamento (amount, description obrigatórios)
     * @return array Resposta da criação do pagamento
     */
    public function createPayment(array $data): array
    {
        $this->validatePaymentData($data);
        return $this->auth->create($data);
    }

    /**
     * Capturar um pagamento autorizado
     * @param string $merchantTransactionId ID da transação
     * @return array Status do pagamento
     */
    public function capturePayment(string $merchantTransactionId): array
    {
        return $this->auth->capture($merchantTransactionId);
    }

    /**
     * Reembolsar um pagamento
     * @param string $merchantTransactionId ID da transação
     * @param float|null $amount Valor do reembolso (null = total)
     * @return array Resultado do reembolso
     */
    public function refundPayment(string $merchantTransactionId, ?float $amount = null): array
    {
        return $this->auth->refund($merchantTransactionId, $amount);
    }

    /**
     * Consultar status de um pagamento
     * @param string $merchantTransactionId ID da transação
     * @return array Status atual do pagamento
     */
    public function getPaymentStatus(string $merchantTransactionId): array
    {
        return $this->auth->getStatus($merchantTransactionId);
    }

    /**
     * Validar dados de entrada para pagamento
     * @param array $data Dados a serem validados
     * @throws \InvalidArgumentException
     */
    private function validatePaymentData(array $data): void
    {
        $required = ['amount', 'description'];
        
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new \InvalidArgumentException("Campo '{$field}' é obrigatório");
            }
        }

        if (!is_numeric($data['amount']) || $data['amount'] <= 0) {
            throw new \InvalidArgumentException('Valor deve ser um número positivo');
        }
    }
}