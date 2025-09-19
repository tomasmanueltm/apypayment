````markdown
# Documentação Completa do ApyPayment

## 📌 Índice

1. [Visão Geral](#-visão-geral)
2. [Instalação](#-instalação)
3. [Configuração](#-configuração)
4. [Uso Básico](#-uso-básico)
5. [Métodos Principais](#-métodos-principais)
6. [Webhooks](#-webhooks)
7. [Customização](#-customização)
8. [Testes](#-testes)
9. [FAQ](#-faq)
10. [Contribuição](#-contribuição)

## 🌟 Visão Geral

O *ApyPayment* é um pacote Laravel completo para integração com sistema de pagamento da AppyPay, oferecendo:

- Processamento de transações seguras
- Gestão de tokens de acesso
- Atualização automática de status
- Webhooks configuráveis
- Sistema de logs detalhado

## 📥 Instalação

```bash
composer require tomasmanueltm/apypayment

````

Publique os arquivos necessários:

```bash
php artisan apypayment:publish    

```

## ⚙️ Configuração

Adicione ao seu arquivo `.env`:

```ini
APY_CLIENT_ID=seu_client_id
APY_CLIENT_SECRET=seu_client_secret
APY_RESOURCE=2aed7612-de64-46b5-9e59-1f48f8902d14
APY_GRANT_TYPE=client_credentials
```

### Configurações Opcionais:

```ini
# URLs da API (padrão: ambiente de teste)
APY_API_URL=https://gwy-api-tst.appypay.co.ao/v2.0
APY_AUTH_URL=https://login.microsoftonline.com/appypaydev.onmicrosoft.com/oauth2/token

# Configurações HTTP
APY_HTTP_TIMEOUT=30
APY_HTTP_VERIFY_SSL=false
```

## 🚀 Uso Básico

### Inicializar o serviço:

```php
use TomasManuelTM\ApyPayment\Services\ApyService;

$service = app('ApyService');
// ou
$service = app(ApyService::class);
```

### Criar um pagamento:

**Parâmetros obrigatórios:** `amount`, `description`

```php
try {
    $payment = $service->createPayment([
        'amount' => 100.00,
        'description' => 'Pagamento do pedido #12345',
        'reference' => 'REF-001' // opcional, será gerado automaticamente
    ]);
    
    if ($payment['success']) {
        echo "Pagamento criado: {$payment['merchantTransactionId']}";
        echo "Referência: {$payment['reference']}";
    }
} catch (\Exception $e) {
    echo "Erro: {$e->getMessage()}";
}
```

### Listar pagamentos:

```php
$payments = $service->getPayments();

foreach ($payments['data'] ?? [] as $payment) {
    echo "ID: {$payment['merchantTransactionId']} - Status: {$payment['status']}";
}
```

### Obter métodos de pagamento:

```php
$methods = $service->getApplications();

foreach ($methods['data'] ?? [] as $method) {
    echo "Método: {$method['name']} - Tipo: {$method['type']}";
}
```

### Consultar status de pagamento:

```php
$status = $service->getPaymentStatus('PT000000001');

if ($status['success']) {
    echo "Status: {$status['status']}";
    echo "Valor: {$status['amount']}";
}
```





## 🔧 Métodos Principais

### createPayment(array $data)

**Parâmetros obrigatórios:**
- `amount` (float): Valor do pagamento
- `description` (string): Descrição do pagamento

**Parâmetros opcionais:**
- `reference` (string): Referência personalizada
- `paymentMethod` (string): Método de pagamento específico

**Retorno:**
```php
[
    'success' => true,
    'merchantTransactionId' => 'PT000000001',
    'reference' => 'REF-12345',
    'amount' => 100.00,
    'status' => 'pending',
    'expiration' => '2025-01-15T10:30:00Z'
]
```

### getPayments()

**Retorno:** Lista todos os pagamentos
```php
[
    'success' => true,
    'data' => [
        [
            'merchantTransactionId' => 'PT000000001',
            'status' => 'completed',
            'amount' => 100.00
        ]
    ]
]
```

### getApplications()

**Retorno:** Lista métodos de pagamento disponíveis
```php
[
    'success' => true,
    'data' => [
        [
            'name' => 'Referência Bancária',
            'type' => 'REF',
            'isDefault' => true
        ]
    ]
]
```

### getPaymentStatus(string $merchantTransactionId)

**Parâmetros:**
- `merchantTransactionId` (string): ID da transação

**Retorno:**
```php
[
    'success' => true,
    'status' => 'completed|pending|failed',
    'amount' => 100.00,
    'reference' => 'REF-12345'
]
```

## 🌐 Webhooks

> ⚠️ **Esta funcionalidade estará disponível em uma futura atualização.**

## 🎨 Customização

### 1. Atualização condicional:

```php
ApyPayment::addUpdateRule(
    'orders', 
    'transaction_id',
    'merchantTransactionId',
    'TEST123',
    'completed'
);
```

### 2. Prefixos customizados:

```php
// No arquivo config/apypayment.php
'prefixes' => [
    'default' => 'PS',
    'renewal' => 'PC',
    'custom' => 'CX'
],
```

## 🧪 Testes

Execute a suíte de testes:

```bash
composer test
```

Cobertura de testes:

```bash
composer test-coverage
```

## ❓ FAQ

### Tratamento de Erros

**Códigos de Status HTTP:**
- `200`: Sucesso
- `400`: Dados inválidos
- `401`: Token inválido/expirado
- `404`: Pagamento não encontrado
- `500`: Erro interno do servidor

**Exemplo de tratamento:**
```php
try {
    $payment = $service->createPayment($data);
    
    if (!$payment['success']) {
        throw new Exception($payment['error']);
    }
    
} catch (\TomasManuelTM\ApyPayment\Exceptions\PaymentCreationException $e) {
    // Erro específico de criação de pagamento
    Log::error('Falha ao criar pagamento', ['error' => $e->getMessage()]);
    
} catch (\TomasManuelTM\ApyPayment\Exceptions\InvalidRequestException $e) {
    // Dados de entrada inválidos
    return response()->json(['error' => 'Dados inválidos'], 400);
    
} catch (\Exception $e) {
    // Erro genérico
    Log::error('Erro inesperado', ['error' => $e->getMessage()]);
}
```

### Problemas Comuns

**Q: Token expirado constantemente?**
A: O sistema gerencia tokens automaticamente. Verifique suas credenciais no `.env`

**Q: Pagamento não encontrado?**
A: Verifique se o `merchantTransactionId` está correto e se o pagamento existe

**Q: Erro de SSL?**
A: Por padrão, a verificação SSL está desabilitada para desenvolvimento. Configure `APY_HTTP_VERIFY_SSL=true` em produção

**Q: Como debugar requisições?**
A: Ative os logs no Laravel e verifique o arquivo de log para detalhes das requisições

## 🤝 Contribuição

1. Faça um fork do projeto
2. Crie uma branch (`git checkout -b feature/SuaFuncionalidade`)
3. Faça commit das mudanças (`git commit -m 'Adiciona nova funcionalidade'`)
4. Faça push para sua branch (`git push origin feature/SuaFuncionalidade`)
5. Abra um Pull Request

## 📄 Licença

Direitos autorais (c) 2025 **TomasManuelTM**

É concedida permissão, gratuitamente, a qualquer pessoa que obtenha uma cópia deste software e seus arquivos de documentação (o “Software”), para usar, copiar, modificar, mesclar, publicar, distribuir, sublicenciar e/ou vender cópias do Software, e permitir o mesmo a terceiros, desde que os avisos de direitos autorais e esta permissão estejam incluídos em todas as cópias relevantes.

O SOFTWARE É FORNECIDO “NO ESTADO EM QUE SE ENCONTRA”, SEM GARANTIAS DE QUALQUER TIPO, EXPRESSAS OU IMPLÍCITAS, INCLUINDO, MAS NÃO SE LIMITANDO A GARANTIAS DE COMERCIALIZAÇÃO, ADEQUAÇÃO A UM FIM ESPECÍFICO E NÃO VIOLAÇÃO. EM NENHUMA CIRCUNSTÂNCIA OS AUTORES SERÃO RESPONSÁVEIS POR QUAISQUER DANOS OU RESPONSABILIDADES DECORRENTES DO USO DESTE SOFTWARE.

## ✉️ Contato

Tomas Manuel — [GitHub](https://github.com/tomasmanueltm) — **[antoniomanuelbaptistabaptista@gmail.com](mailto:antoniomanuelbaptistabaptista@gmail.com)**

---

Documentação atualizada em: **12/07/2025**
Versão do pacote: **1.0.3**