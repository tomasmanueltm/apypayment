````markdown
# 🚀 ApyPayment - Integração Laravel com AppyPay

[![Latest Version](https://img.shields.io/packagist/v/tomasmanueltm/apypayment.svg)](https://packagist.org/packages/tomasmanueltm/apypayment)
[![License](https://img.shields.io/packagist/l/tomasmanueltm/apypayment.svg)](https://packagist.org/packages/tomasmanueltm/apypayment)
[![PHP Version](https://img.shields.io/packagist/php-v/tomasmanueltm/apypayment.svg)](https://packagist.org/packages/tomasmanueltm/apypayment)

## 📌 Índice

1. [Visão Geral](#-visão-geral)
2. [Requisitos](#-requisitos)
3. [Instalação](#-instalação)
4. [Configuração](#-configuração)
5. [Uso Básico](#-uso-básico)
6. [API Reference](#-api-reference)
7. [Arquitetura DDD](#-arquitetura-ddd)
8. [Webhooks](#-webhooks)
9. [Customização](#-customização)
10. [Testes](#-testes)
11. [FAQ](#-faq)
12. [Contribuição](#-contribuição)
13. [Changelog](#-changelog)

## 🌟 Visão Geral

O **ApyPayment** é um pacote Laravel robusto e completo para integração com o sistema de pagamento da AppyPay, desenvolvido seguindo os princípios de Domain-Driven Design (DDD).

### ✨ Características Principais

- 🔐 **Processamento Seguro**: Transações criptografadas e tokens OAuth2
- 🤖 **Gestão Automática**: Tokens renovados automaticamente
- 📊 **Monitoramento**: Sistema de logs detalhado e rastreamento
- 🏗️ **Arquitetura DDD**: Código limpo e manutenível
- 🧪 **Testável**: Cobertura completa de testes
- ⚡ **Performance**: Cache inteligente e retry automático
- 🔄 **Webhooks**: Notificações em tempo real (em breve)

### 🎯 Casos de Uso

- E-commerce e lojas online
- Sistemas de assinatura
- Marketplaces
- Aplicações fintech
- Plataformas de serviços

## 📋 Requisitos

- PHP 8.0+
- Laravel 9.0+
- Extensão JSON
- Extensão cURL
- MySQL/PostgreSQL

## 📥 Instalação

### 1. Instalar via Composer

```bash
composer require tomasmanueltm/apypayment
```

### 2. Publicar Arquivos

```bash
# Publicar tudo
php artisan apypayment:publish

# Ou publicar individualmente
php artisan vendor:publish --tag=apypayment-config
php artisan vendor:publish --tag=apypayment-migrations
```

### 3. Executar Migrações

```bash
php artisan migrate
```

## ⚙️ Configuração

### 🔑 Variáveis Obrigatórias

Adicione ao seu arquivo `.env`:

```ini
# Credenciais AppyPay (obrigatório)
APY_CLIENT_ID=seu_client_id_aqui
APY_CLIENT_SECRET=seu_client_secret_aqui
APY_RESOURCE=2aed7612-de64-46b5-9e59-1f48f8902d14
APY_GRANT_TYPE=client_credentials
```

### 🛠️ Configurações Opcionais

```ini
# Ambiente (teste/produção)
APY_API_URL=https://gwy-api-tst.appypay.co.ao/v2.0
APY_AUTH_URL=https://login.microsoftonline.com/appypaydev.onmicrosoft.com/oauth2/token

# Performance e Segurança
APY_HTTP_TIMEOUT=30
APY_HTTP_VERIFY_SSL=false

# Logs e Debug
APY_LOG_LEVEL=info
APY_DEBUG_MODE=false
```

### 📝 Arquivo de Configuração

Personalize em `config/apypayment.php`:

```php
return [
    'default_currency' => 'AOA',
    'default_payment_method' => 'REF',
    'prefixes' => [
        'default' => 'PS',
        'subscription' => 'SUB',
    ],
];
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





## 📚 API Reference

### 💳 Pagamentos

#### `createPayment(array $data): array`

Cria um novo pagamento na AppyPay.

**Parâmetros:**

| Campo | Tipo | Obrigatório | Descrição |
|-------|------|-------------|----------|
| `amount` | `float` | ✅ | Valor do pagamento (> 0) |
| `description` | `string` | ✅ | Descrição do pagamento |
| `reference` | `string` | ❌ | Referência personalizada |
| `paymentMethod` | `string` | ❌ | Método específico (REF, MB, etc) |

**Exemplo:**
```php
$payment = $service->createPayment([
    'amount' => 1500.00,
    'description' => 'Compra de produto #123',
    'reference' => 'ORDER-2025-001'
]);
```

**Resposta:**
```php
[
    'success' => true,
    'merchantTransactionId' => 'PT000000001',
    'reference' => 'ORDER-2025-001',
    'amount' => 1500.00,
    'status' => 'pending',
    'expiration' => '2025-01-15T10:30:00Z',
    'paymentUrl' => 'https://...' // URL para pagamento
]
```

#### `capturePayment(string $merchantTransactionId): array`

Captura um pagamento previamente autorizado.

```php
$result = $service->capturePayment('PT000000001');
// Retorna: ['success' => true, 'data' => [...]]
```

#### `refundPayment(string $merchantTransactionId, ?float $amount = null): array`

Reembolsa um pagamento (total ou parcial).

```php
// Reembolso total
$refund = $service->refundPayment('PT000000001');

// Reembolso parcial
$refund = $service->refundPayment('PT000000001', 500.00);
```

#### `getPaymentStatus(string $merchantTransactionId): array`

Consulta o status atual de um pagamento.

```php
$status = $service->getPaymentStatus('PT000000001');
```

**Status Possíveis:**
- `pending` - Aguardando pagamento
- `completed` - Pagamento confirmado
- `failed` - Pagamento falhou
- `cancelled` - Pagamento cancelado
- `refunded` - Pagamento reembolsado

### 📋 Listagens

#### `getPayments(): array`

Lista todos os pagamentos.

```php
$payments = $service->getPayments();
```

#### `getApplications(): array`

Lista métodos de pagamento disponíveis.

```php
$methods = $service->getApplications();
```

## 🏗️ Arquitetura DDD

O pacote segue Domain-Driven Design para melhor organização:

### 📁 Estrutura de Pastas

```
src/
├── Domain/              # Regras de negócio
│   └── Payment/
│       ├── Entities/    # Entidades do domínio
│       ├── ValueObjects/# Objetos de valor
│       └── Repositories/# Contratos de repositório
├── Application/         # Casos de uso
│   └── Services/       # Serviços de aplicação
├── Infrastructure/      # Implementações técnicas
│   └── Repositories/   # Repositórios concretos
└── Services/           # Serviços de integração
```

### 🎯 Usando DDD Diretamente

```php
use TomasManuelTM\ApyPayment\Application\Services\PaymentService;
use TomasManuelTM\ApyPayment\Domain\Payment\ValueObjects\Amount;

// Injeção de dependência
$paymentService = app(PaymentService::class);

// Criar pagamento com validação de domínio
$payment = $paymentService->createPayment([
    'amount' => 100.00,
    'description' => 'Teste DDD'
]);

// Capturar usando regras de negócio
$captured = $paymentService->capturePayment('PT000000001');
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

### 🚨 Tratamento de Erros

#### Códigos de Status HTTP

| Código | Significado | Ação Recomendada |
|--------|-------------|------------------|
| `200` | ✅ Sucesso | Continuar processamento |
| `400` | ❌ Dados inválidos | Validar parâmetros |
| `401` | 🔐 Token inválido | Verificar credenciais |
| `404` | 🔍 Não encontrado | Verificar ID da transação |
| `429` | ⏱️ Rate limit | Implementar retry |
| `500` | 💥 Erro interno | Contatar suporte |

#### Exceções Personalizadas

```php
try {
    $payment = $service->createPayment($data);
    
} catch (\TomasManuelTM\ApyPayment\Exceptions\PaymentCreationException $e) {
    // Erro na criação - verificar dados
    Log::error('Pagamento falhou', [
        'data' => $data,
        'error' => $e->getMessage()
    ]);
    
} catch (\TomasManuelTM\ApyPayment\Exceptions\InvalidRequestException $e) {
    // Dados inválidos - mostrar erro ao usuário
    return back()->withErrors(['payment' => 'Dados de pagamento inválidos']);
    
} catch (\TomasManuelTM\ApyPayment\Exceptions\PaymentNotFoundException $e) {
    // Pagamento não existe
    return response()->json(['error' => 'Pagamento não encontrado'], 404);
    
} catch (\Exception $e) {
    // Erro genérico - log e notificar admin
    Log::critical('Erro crítico no pagamento', [
        'exception' => $e,
        'trace' => $e->getTraceAsString()
    ]);
}
```

### 🔧 Problemas Comuns

#### Q: Token expira constantemente?
**A:** Verifique:
- Credenciais corretas no `.env`
- Conectividade com a API
- Horário do servidor sincronizado

```bash
# Verificar configuração
php artisan config:cache
php artisan config:clear
```

#### Q: Pagamento não encontrado?
**A:** Confirme:
- `merchantTransactionId` correto
- Pagamento existe na AppyPay
- Ambiente correto (teste/produção)

#### Q: Erro de SSL em produção?
**A:** Configure:
```ini
APY_HTTP_VERIFY_SSL=true
```

#### Q: Como debugar requisições?
**A:** Ative logs detalhados:
```ini
APY_DEBUG_MODE=true
LOG_LEVEL=debug
```

#### Q: Performance lenta?
**A:** Otimize:
- Use cache Redis/Memcached
- Configure timeout adequado
- Implemente retry inteligente

### 📊 Monitoramento

#### Logs Importantes

```php
// Monitorar estes eventos
Log::info('Payment created', ['id' => $merchantId]);
Log::warning('Payment retry', ['attempt' => $attempt]);
Log::error('Payment failed', ['error' => $error]);
```

#### Métricas Recomendadas

- Taxa de sucesso de pagamentos
- Tempo médio de resposta da API
- Frequência de renovação de tokens
- Erros por tipo/código

### 🔒 Segurança

#### Boas Práticas

1. **Nunca** exponha credenciais no código
2. Use HTTPS em produção
3. Valide todos os dados de entrada
4. Implemente rate limiting
5. Monitore tentativas de fraude

```php
// Validação robusta
class PaymentRequest extends FormRequest
{
    public function rules()
    {
        return [
            'amount' => 'required|numeric|min:0.01|max:999999.99',
            'description' => 'required|string|max:255',
            'reference' => 'nullable|string|max:100|unique:payments,reference'
        ];
    }
}
```

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

## 📈 Changelog

### v1.0.3 (2025-01-15)
- ✅ Implementação completa de DDD
- ✅ Métodos de captura e reembolso
- ✅ Melhorias na documentação
- ✅ Testes unitários completos
- 🐛 Correções de bugs menores

### v1.0.2 (2025-01-10)
- ✅ Sistema de logs aprimorado
- ✅ Gestão automática de tokens
- ✅ Validações de entrada

### v1.0.1 (2025-01-05)
- ✅ Correções de compatibilidade
- ✅ Melhorias de performance

### v1.0.0 (2025-01-01)
- 🎉 Lançamento inicial
- ✅ Integração básica com AppyPay
- ✅ CRUD de pagamentos

---

## 📞 Suporte

- 📧 **Email**: antoniomanuelbaptistabaptista@gmail.com
- 🐛 **Issues**: [GitHub Issues](https://github.com/tomasmanueltm/apypayment/issues)
- 📖 **Wiki**: [Documentação Completa](https://github.com/tomasmanueltm/apypayment/wiki)
- 💬 **Discussões**: [GitHub Discussions](https://github.com/tomasmanueltm/apypayment/discussions)

---

**Documentação atualizada em: 15/09/2025**  
**Versão do pacote: 1.0.3**  
**Compatibilidade: Laravel 9.0+ | PHP 8.0+**
