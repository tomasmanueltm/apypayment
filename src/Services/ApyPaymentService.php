<?php

namespace TomasManuelTM\ApyPayment\Services;

use TomasManuelTM\ApyPayment\Models\ApyToken;
use TomasManuelTM\ApyPayment\Models\ApyPayment;
use Illuminate\Support\Facades\Log;

class ApyPaymentService
{
    protected $credentials;
    protected $initialized = false;
    
    public function __construct()
    {
        $this->initializeService();
    }
    
    protected function initializeService()
    {
        try {
            $this->credentials = ApyToken::first();
            $this->initialized = (bool)$this->credentials;
            
            if (!$this->initialized) {
                Log::warning('APY payment service initialized without active credentials');
            }
            
        } catch (\Exception $e) {
            Log::error('APY service initialization failed: ' . $e->getMessage());
            $this->initialized = false;
        }
    }
    
    public function isReady(): bool
    {
        return $this->initialized;
    }
    
    public function createPayment()
    {
        if (!$this->isReady()) {
            throw new \RuntimeException('Payment service not ready - missing credentials');
        }
        return response()->json([
            'status' => 'pending',
            'apy_token_id' => $this->credentials->id,
        ]);
        // Restante do método...
    }
    
    // Outros métodos permanecem iguais
}