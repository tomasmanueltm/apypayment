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
APY_API_TYPE=Local ou Prodution 
APY_CLIENT_ID=seu_client_id
APY_CLIENT_SECRET=seu_client_secret
```

## 🚀 Uso Básico

### Criar um pagamento:

Esta biblioteca permite criar pagamentos com o mínimo de parâmetros obrigatórios, tornando o uso mais simples e limpo.

```php
$payment = $service->createPayment([
    'amount' => 100.00,
    'description' => 'Pagamento -12345',
]);


```

### Listar pagamentos:

```php
$service = app('ApyService');
$payments = $service->getPayments();
```


### lista metodos de pagamentos:

```php
$service = app('ApyService');
$payments = $service->getPaymentMethods();
```


### Buscar pagamentos:

```php
$service = app('ApyService');
$payments = $service->capturePayment(PT000000001);
```





## 🔧 Métodos Principais

| Método                                                               | Parâmetros                         | Retorno  | Descrição                      |                         |
| -------------------------------------------------------------------- | ---------------------------------- | -------- | ------------------------------ | ----------------------- |
| `createPayment(array $data)`                                         | Dados do pagamento                 | `array`  | Cria uma nova transação        |                         |
| `capturePayment(string $merchantTransactionId)`                      | ID da transação do comerciante     | `array`  | Captura o pagamento autorizado |                         |
| `refundPayment(string $merchantTransactionId, float $amount = null)` | ID da transação e valor (opcional) | `array`  | Estorna uma transação          |                         |
| `getPaymentStatus(string $merchantTransactionId)`                    | ID da transação do comerciante     | `array`  | Consulta o status do pagamento |                         |

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

> ⚠️ **A seção de tratamento de erros será expandida em uma próxima versão.**

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