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

O ApyPayment é um pacote Laravel completo para integração com sistemas de pagamento, oferecendo:

- Processamento de transações seguras
- Gestão de tokens de acesso
- Atualização automática de status
- Webhooks configuráveis
- Sistema de logs detalhado

## 📥 Instalação

```bash
composer require tomasmanueltm/apypayment
```

Publique os arquivos necessários:

```bash
php artisan vendor:publish --provider="TomasManuelTM\ApyPayment\Providers\ApyPaymentServiceProvider"
```

## ⚙️ Configuração

Adicione no seu `.env`:

```ini
APY_API_URL=https://api.payment.com
APY_AUTH_URL=https://auth.payment.com
APY_CLIENT_ID=seu_client_id
APY_CLIENT_SECRET=seu_client_secret
APY_RESOURCE=seu_resource_id
```

## 🚀 Uso Básico

### Criar um pagamento:

```php
use TomasManuelTM\ApyPayment\Facades\ApyPayment;

$payment = ApyPayment::createPayment([
    'amount' => 100.00,
    'currency' => 'BRL',
    'reference' => 'ORD-12345'
]);
```

### Buscar pagamentos:

```php
$payments = ApyPayment::searchPayments([
    ['value' => 'REF123', 'type' => 'reference'],
    ['value' => 'PS00001', 'type' => 'merchant']
]);
```

## 🔧 Métodos Principais

| Método                                 | Parâmetros | Retorno | Descrição          |
|----------------------------------------|------------|---------|--------------------|
| `getAccessToken()`                     | - | `string|null` | Obtém token de acesso |
| `createPayment(array $data)` | Dados do pagamento | `array` | Cria nova transação |
| `capturePayment(string $id)` | ID do pagamento | `array` | Captura pagamento autorizado |
| `refundPayment(string $id, float $amount = null)` | ID e valor | `array` | Estorna transação |
| `getPaymentStatus(string $id)` | ID do pagamento | `array` | Consulta status |

## 🌐 Webhooks

Configure sua rota:

```php
Route::post('/apy/webhook', function(Request $request) {
    return ApyPayment::handleWebhook($request->all());
});
```

### Eventos disponíveis:

- `payment.created`
- `payment.completed`
- `payment.failed`
- `payment.refunded`

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
// No config/apypayment.php
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

### Como tratar erros?

```php
try {
    $payment = ApyPayment::createPayment($data); 
} catch (\TomasManuelTM\ApyPayment\Exceptions\PaymentException $e) {
    // Tratamento personalizado
}
```

### Como debugar problemas?

Ative os logs detalhados no `.env`:

```ini
APY_DEBUG=true
```

## 🤝 Contribuição

1. Faça um fork do projeto
2. Crie sua branch (`git checkout -b feature/AmazingFeature`)
3. Commit suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. Push para a branch (`git push origin feature/AmazingFeature`)
5. Abra um Pull Request

## 📄 Licença

Distribuído sob a licença MIT. Veja `LICENSE` para mais informações.

## ✉️ Contato

Tomas Manuel - [GitHub](https://github.com/tomasmanueltm) - **antoniomanuelbaptistabaptista@gmail.com**

---

Documentação atualizada em: **12.07.2025**  
Versão do pacote: **1.0.0**