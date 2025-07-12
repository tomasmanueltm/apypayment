<?php

namespace TomasManuelTM\ApyPayment\Services;

class PaymentStatusUpdater
{
    protected $updateRules = [];

    /**
     * Adiciona uma regra de atualização
     */
    public function addUpdateRule(
        string $targetTable,
        string $targetColumn,
        string $paymentKey,
        $expectedValue,
        $newValue
    ): void {
        $this->updateRules[] = [
            'table' => $targetTable,
            'column' => $targetColumn,
            'payment_key' => $paymentKey,
            'expected_value' => $expectedValue,
            'new_value' => $newValue
        ];
    }

    /**
     * Executa todas as atualizações quando o status muda para Success
     */
    public function executeOnSuccess(array $paymentData): void
    {
        if ($paymentData['status'] !== 'Success') {
            return;
        }

        foreach ($this->updateRules as $rule) {
            $this->applyUpdateRule($paymentData, $rule);
        }
    }

    /**
     * Aplica uma única regra de atualização
     */
    protected function applyUpdateRule(array $paymentData, array $rule): void
    {
        try {
            // Verifica se o valor do pagamento corresponde ao esperado
            $actualValue = data_get($paymentData, $rule['payment_key']);
            
            if ($actualValue == $rule['expected_value']) {
                // Atualiza a tabela de destino
                \DB::table($rule['table'])
                    ->where($rule['column'], $actualValue)
                    ->update(['status' => $rule['new_value']]);
            }
        } catch (\Exception $e) {
            logger()->error("Failed to apply update rule: " . $e->getMessage(), [
                'rule' => $rule,
                'payment' => $paymentData
            ]);
        }
    }
}