# Novas Classes PHP - Documentação

Este documento descreve as três novas classes criadas para o projeto Novo Site: **PDF**, **WhatsApp** e **IA**.

## 📋 Índice

1. [PDF.php](#pdfphp)
2. [WhatsApp.php](#whatsappphp)
3. [IA.php](#iaphp)
4. [Configuração](#configuração)
5. [Logs](#logs)

---

## PDF.php

### Objetivo
Gera documentos PDF profissionais para orçamentos, termos de garantia e recibos usando a biblioteca TCPDF.

### Métodos Principais

#### `generateOrcamento($dados, $download = false)`
**Função:** Cria PDF com detalhes completos de orçamento comercial

**Parâmetros:**
```php
$dados = [
    'numero' => 'ORÇ-001',              // Número único do orçamento
    'data' => '2024-02-10',              // Data de emissão
    'validade' => '2024-03-10',          // Data de validade
    'cliente' => [
        'nome' => 'Nome Cliente',
        'email' => 'email@example.com',
        'telefone' => '(11) 99999-9999',
        'endereco' => 'Endereço completo'
    ],
    'produtos' => [                      // Array de itens
        [
            'descricao' => 'Descrição do serviço',
            'quantidade' => 1,
            'preco' => 1000.00,
            'total' => 1000.00
        ]
    ],
    'desconto' => 100.00,                // Desconto em reais
    'tipo_desconto' => 'fixo',           // 'fixo' ou 'percentual'
    'observacoes' => 'Termos e condições'
];
```

**Saída:** PDF com:
- Logo e informações da empresa
- Dados do cliente
- Tabela de produtos/serviços
- Cálculo de subtotal, desconto e total
- Observações
- Rodapé com contatos

**Retorno:** Arquivo PDF (download ou exibição no navegador)

#### `generateGarantia($dados, $download = false)`
**Função:** Cria termo de garantia profissional com termos legais

**Parâmetros:**
```php
$dados = [
    'numero' => 'GARANT-001',
    'data' => '2024-02-10',
    'cliente' => [
        'nome' => 'Nome Cliente',
        'email' => 'email@example.com',
        'telefone' => '(11) 99999-9999'
    ],
    'produto' => 'Descrição do produto/serviço',
    'periodo' => '12 meses',             // Duração da garantia
    'data_inicio' => '2024-02-10',
    'data_fim' => '2025-02-10',
    'cobertura' => 'O que está coberto...',
    'exclusoes' => 'O que não está coberto...',
    'condicoes' => 'Termos e condições aplicáveis...'
];
```

**Saída:** PDF com:
- Dados do cliente
- Descrição do produto/serviço
- Período de garantia
- O que está coberto
- O que está excluído
- Termos e condições

#### `generateRecibo($dados, $download = false)`
**Função:** Cria comprovante de pagamento/recebimento

**Parâmetros:**
```php
$dados = [
    'numero' => 'REC-001',
    'data' => '2024-02-10 14:30:00',
    'cliente' => [
        'nome' => 'Nome Cliente',
        'cpf_cnpj' => '123.456.789-00'
    ],
    'descricao' => 'Descrição do pagamento',
    'valor' => 1500.00,                  // Valor em reais
    'forma_pagamento' => 'Transferência Bancária',
    'referencia' => 'ORÇ-001',           // Referência da compra
    'observacoes' => 'Observações adicionais'
];
```

**Saída:** PDF com:
- Número e data do recibo
- Dados de quem recebeu
- Descrição do pagamento
- Valor em destaque
- Forma de pagamento
- Espaço para assinatura

### Configuração da Empresa
Editar na classe `__construct()`:
```php
$this->empresa = [
    'nome' => 'Seu Nome Empresa',
    'cnpj' => '00.000.000/0000-00',
    'endereco' => 'Rua Exemplo, 123',
    'cidade' => 'São Paulo, SP',
    'telefone' => '(11) 99999-9999',
    'email' => 'contato@empresa.com.br',
    'website' => 'www.empresa.com.br'
];
```

### Dependências
- TCPDF (instalar via Composer: `composer require tecnickcom/tcpdf`)

---

## WhatsApp.php

### Objetivo
Integra a API oficial do WhatsApp Business para enviar mensagens, documentos e templates.

### Métodos Principais

#### `sendMessage($numero, $mensagem)`
**Função:** Envia mensagem de texto simples via WhatsApp

**Parâmetros:**
- `$numero` (string): Número WhatsApp com código de país (ex: 5511999999999)
- `$mensagem` (string): Texto da mensagem (máx 4096 caracteres)

**Retorno:** Array com resposta da API incluindo `message_id`

**Exemplo:**
```php
$whatsapp = new WhatsApp();
$resultado = $whatsapp->sendMessage('5511999999999', 'Olá!');
// Retorna: ['messages' => [['id' => 'wamid.xxxxx']]]
```

#### `sendDocument($numero, $caminhoArquivo, $tipoArquivo, $caption = null)`
**Função:** Envia arquivo (PDF, imagem, vídeo, áudio) para WhatsApp

**Parâmetros:**
- `$numero` (string): Número WhatsApp
- `$caminhoArquivo` (string): Caminho local OU URL pública do arquivo
- `$tipoArquivo` (string): Tipo - 'document', 'image', 'video', 'audio', 'sticker'
- `$caption` (string, opcional): Legenda (apenas para image/video)

**Limites de Tamanho:**
- document: 100MB
- image: 16MB
- video: 16MB
- audio: 16MB

**Exemplo:**
```php
$whatsapp = new WhatsApp();
$whatsapp->sendDocument(
    '5511999999999',
    'https://example.com/orcamento.pdf',
    'document'
);
```

#### `sendTemplate($numero, $nomeTemplate, $parametros, $idioma)`
**Função:** Envia mensagem usando template pré-aprovado pela WhatsApp

**Parâmetros:**
- `$numero` (string): Número WhatsApp
- `$nomeTemplate` (string): Nome do template registrado
- `$parametros` (array): Valores para placeholders ({{1}}, {{2}}, etc)
- `$idioma` (string): Código do idioma ('pt_BR', 'en_US', 'es_ES', etc)

**Exemplo:**
```php
$whatsapp = new WhatsApp();
$whatsapp->sendTemplate(
    '5511999999999',
    'bem_vindo',
    ['João Silva', 'João@example.com'],
    'pt_BR'
);
```

**Como criar template na WhatsApp Business:**
1. Acesse o painel WhatsApp Business
2. Vá para Templates > Criar novo
3. Defina nome, categoria e conteúdo
4. Use {{1}}, {{2}}, {{3}} para placeholders
5. Aguarde aprovação (geralmente 5-15 min)

### Configuração Necessária

Adicionar constantes em `public_html/config/constants.php`:

```php
// WhatsApp Business API
define('WHATSAPP_API_URL', 'https://graph.facebook.com/v18.0/');
define('WHATSAPP_ACCESS_TOKEN', 'EAAxxxxxxxxxxxxxxxxxx'); // Seu token
define('WHATSAPP_PHONE_NUMBER_ID', '1234567890'); // Seu ID de telefone
```

**Como obter as credenciais:**
1. Criar conta em [Meta Business Platform](https://business.facebook.com)
2. Registrar WhatsApp Business Account
3. Gerar Access Token com permissões: whatsapp_business_messaging, whatsapp_business_management
4. Obter Phone Number ID na seção de números de telefone

### Tratamento de Erros
Todos os métodos lançam `Exception` em caso de erro:

```php
try {
    $whatsapp->sendMessage('5511999999999', 'Mensagem');
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
```

---

## IA.php

### Objetivo
Integra serviços de Inteligência Artificial (OpenAI, Google Gemini, Anthropic Claude) para melhorias de texto, geração de conteúdo e assistência contextual.

### Métodos Principais

#### `improveText($texto, $estilo, $idioma)`
**Função:** Melhora qualidade, clareza e profissionalismo de um texto

**Parâmetros:**
- `$texto` (string): Texto a melhorar (máx 5000 caracteres)
- `$estilo` (string): 'profissional', 'casual', 'formal', 'técnico', 'criativo'
- `$idioma` (string): 'pt_BR', 'en_US', 'es_ES', etc

**Retorno:** String com texto melhorado

**Exemplo:**
```php
$ia = new IA();
$original = "oi meu amigo, ta bom? preciso de ajuda com um projeto";
$melhorado = $ia->improveText($original, 'profissional', 'pt_BR');
// Retorna texto profissional e bem estruturado
```

#### `generateChecklist($descricao, $tipo, $idioma)`
**Função:** Gera checklist/lista de tarefas automaticamente

**Parâmetros:**
- `$descricao` (string): Descrição do projeto/processo (máx 2000 caracteres)
- `$tipo` (string): 'projeto', 'vendas', 'atendimento', 'manutencao', 'testes', 'implantacao'
- `$idioma` (string): Código do idioma

**Retorno:** Array com estrutura:
```php
[
    'titulo' => 'Nome do Checklist',
    'descricao' => 'Descrição breve',
    'items' => [
        [
            'tarefa' => 'Descrição da tarefa',
            'prioridade' => 'alta|média|baixa',
            'tempo' => 'Estimativa de tempo'
        ],
        // ... mais items
    ]
]
```

**Exemplo:**
```php
$ia = new IA();
$checklist = $ia->generateChecklist(
    'Lançar novo produto SaaS com integração de pagamento',
    'projeto',
    'pt_BR'
);

foreach ($checklist['items'] as $item) {
    echo $item['tarefa'] . " (" . $item['prioridade'] . ") - " . $item['tempo'] . "\n";
}
```

#### `assistente($pergunta, $contexto, $tipo)`
**Função:** Fornece respostas e assistência contextual para perguntas gerais, técnicas ou estratégicas

**Parâmetros:**
- `$pergunta` (string): Pergunta a fazer (máx 3000 caracteres)
- `$contexto` (string, opcional): Contexto adicional (empresa, projeto, etc)
- `$tipo` (string): Tipo de resposta - 'resposta', 'explicacao', 'dica', 'codigo', 'lista', 'analise'

**Retorno:** String com resposta da IA

**Exemplo:**
```php
$ia = new IA();

// Pergunta simples
$resposta = $ia->assistente(
    "Como melhorar taxa de conversão em e-commerce?",
    "Loja online de eletrônicos com 1000 visitantes/mês",
    'resposta'
);

// Gerar código
$codigo = $ia->assistente(
    "Crie função PHP para validar CPF",
    "Projeto de sistema de vendas em PHP",
    'codigo'
);
```

### Configuração

Escolha um provedor e adicione constantes em `public_html/config/constants.php`:

#### Opção 1: OpenAI (Recomendado)
```php
define('IA_PROVIDER', 'openai');
define('IA_API_KEY', 'sk-xxxxxxxxxxxxxxxxxxxxxxxx');
define('IA_MODEL', 'gpt-3.5-turbo'); // ou 'gpt-4'
define('IA_MAX_TOKENS', 1500);
define('IA_TEMPERATURE', 0.7);
```

**Onde obter:**
1. Criar conta em [OpenAI](https://platform.openai.com)
2. Ir para [API Keys](https://platform.openai.com/account/api-keys)
3. Criar nova chave secreta
4. Adicionar crédito/assinatura

#### Opção 2: Google Gemini
```php
define('IA_PROVIDER', 'gemini');
define('IA_API_KEY', 'sua-chave-gemini-aqui');
define('IA_MODEL', 'gemini-pro');
define('IA_MAX_TOKENS', 2000);
define('IA_TEMPERATURE', 0.7);
```

#### Opção 3: Anthropic Claude
```php
define('IA_PROVIDER', 'claude');
define('IA_API_KEY', 'sk-ant-xxxxxxxxxxxxxxxxxxxxxxxx');
define('IA_MODEL', 'claude-3-sonnet-20240229');
define('IA_MAX_TOKENS', 2000);
define('IA_TEMPERATURE', 0.7);
```

### Custos Aproximados

**OpenAI:**
- GPT-3.5-turbo: $0.0005 / 1K tokens entrada, $0.0015 / 1K tokens saída
- GPT-4: $0.03 / 1K tokens entrada, $0.06 / 1K tokens saída

**Google Gemini:**
- Gratuito até 60 requisições/minuto
- API paga: $0.00025 / 1K tokens

**Claude:**
- $3 / 1M tokens entrada, $15 / 1M tokens saída

---

## Configuração

### Arquivo de Configuração (config/constants.php)

```php
<?php

// ===== WHATSAPP =====
define('WHATSAPP_API_URL', 'https://graph.facebook.com/v18.0/');
define('WHATSAPP_ACCESS_TOKEN', 'EAAxxxxxxxxxx');
define('WHATSAPP_PHONE_NUMBER_ID', '1234567890');

// ===== IA (escolha um provider) =====
define('IA_PROVIDER', 'openai'); // 'openai', 'gemini', 'claude'
define('IA_API_KEY', 'sk-xxxxx');
define('IA_MODEL', 'gpt-3.5-turbo');
define('IA_MAX_TOKENS', 1500);
define('IA_TEMPERATURE', 0.7);

// ===== PDF =====
// Nenhuma configuração necessária, mas editar dados da empresa na classe PDF

?>
```

### Estrutura de Diretórios Necessária

```
public_html/
├── classes/
│   ├── PDF.php
│   ├── WhatsApp.php
│   ├── IA.php
│   ├── Database.php
│   ├── Auth.php
│   └── Validator.php
├── config/
│   ├── constants.php    ← Adicionar configurações
│   └── database.php
├── logs/                ← Criado automaticamente
│   ├── pdf_YYYY-MM-DD.log
│   ├── whatsapp_YYYY-MM-DD.log
│   └── ia_YYYY-MM-DD.log
├── uploads/
│   └── whatsapp/        ← Para arquivos enviados
└── vendor/
    └── autoload.php     ← TCPDF instalado via Composer
```

### Instalação de Dependências

```bash
cd public_html
composer require tecnickcom/tcpdf
```

---

## Logs

Todas as classes registram atividades e erros em arquivos de log:

### Localização
- **PDF:** `logs/pdf_YYYY-MM-DD.log`
- **WhatsApp:** `logs/whatsapp_YYYY-MM-DD.log`
- **IA:** `logs/ia_YYYY-MM-DD.log`

### Formato
```
[2024-02-10 14:30:45] [INFO] Mensagem enviada para 5511999999999. ID: wamid.xxxxx
[2024-02-10 14:31:02] [ERROR] Erro ao chamar API de IA: Connection timeout
```

### Análise de Logs
```php
<?php
// Ver últimos 100 linhas do log
$logFile = 'logs/pdf_' . date('Y-m-d') . '.log';
if (file_exists($logFile)) {
    $linhas = array_slice(file($logFile), -100);
    foreach ($linhas as $linha) {
        echo htmlspecialchars($linha) . "<br>";
    }
}
?>
```

---

## Tratamento de Erros

### Padrão Recomendado

```php
<?php
require_once 'classes/PDF.php';
require_once 'classes/WhatsApp.php';
require_once 'classes/IA.php';

try {
    $pdf = new PDF();
    $pdf->generateOrcamento($dados, true);
    
} catch (Exception $e) {
    // Log do erro
    error_log("Erro PDF: " . $e->getMessage());
    
    // Responder ao usuário
    http_response_code(500);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Erro ao processar sua solicitação. Tente novamente mais tarde.'
    ]);
}
?>
```

---

## Boas Práticas

### 1. Validação de Entrada
```php
// Sempre validar dados antes de enviar
if (empty($numero) || !preg_match('/^\d{13,}$/', preg_replace('/[^0-9]/', '', $numero))) {
    throw new Exception("Número WhatsApp inválido");
}
```

### 2. Rate Limiting
```php
// Respeitar limites de API
// WhatsApp: 80 mensagens/segundo
// OpenAI: Varia conforme plano
// Implementar fila/delay se necessário
```

### 3. Cache de Respostas
```php
// Cachear respostas de IA para evitar chamadas repetidas
$cacheKey = md5($pergunta);
if ($respuestaCacheada = $cache->get($cacheKey)) {
    return $respuestaCacheada;
}
```

### 4. Ambiente de Teste
```php
// Usar números/tokens de teste antes de produção
// WhatsApp: Usar número de teste fornecido
// OpenAI: Usar modelo gpt-3.5-turbo em testes
```

---

## Suporte

Para dúvidas sobre:
- **PDF:** Consultar documentação TCPDF em https://tcpdf.org
- **WhatsApp:** https://developers.facebook.com/docs/whatsapp
- **IA:** https://platform.openai.com/docs ou https://ai.google.dev

