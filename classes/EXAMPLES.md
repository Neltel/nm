# Exemplos de Uso das Classes PDF, WhatsApp e IA

## 1. Classe PDF - Geração de Documentos PDF

### 1.1 Gerar Orçamento

```php
<?php
require_once 'classes/PDF.php';

$pdf = new PDF();

$dados = [
    'numero' => 'ORÇ-2024-001',
    'data' => '2024-02-10',
    'validade' => '2024-03-10',
    'cliente' => [
        'nome' => 'João Silva',
        'email' => 'joao@example.com',
        'telefone' => '(11) 99999-9999',
        'endereco' => 'Rua Principal, 123'
    ],
    'produtos' => [
        [
            'descricao' => 'Desenvolvimento Website',
            'quantidade' => 1,
            'preco' => 2500.00,
            'total' => 2500.00
        ],
        [
            'descricao' => 'Integração com WhatsApp API',
            'quantidade' => 1,
            'preco' => 1500.00,
            'total' => 1500.00
        ]
    ],
    'desconto' => 200.00,
    'observacoes' => 'Prazo de entrega: 30 dias. Valor da entrada: 50%'
];

$pdf->generateOrcamento($dados, true); // true = download, false = visualizar
?>
```

### 1.2 Gerar Termo de Garantia

```php
<?php
require_once 'classes/PDF.php';

$pdf = new PDF();

$dados = [
    'numero' => 'GARANT-2024-001',
    'data' => '2024-02-10',
    'cliente' => [
        'nome' => 'Maria Santos',
        'email' => 'maria@example.com',
        'telefone' => '(11) 98888-8888'
    ],
    'produto' => 'Website Institucional com CMS e 5 páginas',
    'periodo' => '12 meses',
    'data_inicio' => '2024-02-10',
    'data_fim' => '2025-02-10',
    'cobertura' => 'Manutenção técnica, correção de bugs, atualizações de segurança, suporte por email',
    'exclusoes' => 'Alterações de design, adição de novas funcionalidades, hospedagem',
    'condicoes' => 'A garantia é válida a partir da data de entrega. Requer manutenção de backups regulares.'
];

$pdf->generateGarantia($dados);
?>
```

### 1.3 Gerar Recibo de Pagamento

```php
<?php
require_once 'classes/PDF.php';

$pdf = new PDF();

$dados = [
    'numero' => 'REC-2024-001',
    'data' => date('Y-m-d H:i:s'),
    'cliente' => [
        'nome' => 'Carlos Oliveira',
        'cpf_cnpj' => '123.456.789-00'
    ],
    'descricao' => 'Pagamento referente ao orçamento ORÇ-2024-001 - Desenvolvimento Website',
    'valor' => 1500.00,
    'forma_pagamento' => 'Transferência Bancária',
    'referencia' => 'ORÇ-2024-001',
    'observacoes' => 'Primeira parcela de 50% do total do projeto'
];

$pdf->generateRecibo($dados, false); // false = exibir no navegador
?>
```

---

## 2. Classe WhatsApp - Envio de Mensagens

### Configuração Inicial

Adicionar constantes em arquivo de configuração (config/constants.php):

```php
<?php
// Credenciais WhatsApp
define('WHATSAPP_API_URL', 'https://graph.facebook.com/v18.0/');
define('WHATSAPP_ACCESS_TOKEN', 'seu_token_de_acesso_aqui');
define('WHATSAPP_PHONE_NUMBER_ID', 'seu_numero_id_aqui');
?>
```

### 2.1 Enviar Mensagem de Texto

```php
<?php
require_once 'classes/WhatsApp.php';

$whatsapp = new WhatsApp();

try {
    $resultado = $whatsapp->sendMessage(
        '5511999999999', // Número com código do país
        'Olá João! Seu orçamento ORÇ-2024-001 foi enviado. Acesse o link para visualizar.'
    );
    
    if (isset($resultado['messages'])) {
        echo "Mensagem enviada! ID: " . $resultado['messages'][0]['id'];
    }
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
?>
```

### 2.2 Enviar Documento (PDF)

```php
<?php
require_once 'classes/WhatsApp.php';

$whatsapp = new WhatsApp();

try {
    $resultado = $whatsapp->sendDocument(
        '5511999999999',
        '/caminho/para/orcamento.pdf',
        'document',
        'Seu orçamento de 2024'
    );
    
    echo "Documento enviado com sucesso!";
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
?>
```

Ou com URL pública:

```php
<?php
$whatsapp = new WhatsApp();

$resultado = $whatsapp->sendDocument(
    '5511999999999',
    'https://exemplo.com/documentos/orcamento.pdf',
    'document'
);
?>
```

### 2.3 Enviar Template Pré-aprovado

```php
<?php
require_once 'classes/WhatsApp.php';

$whatsapp = new WhatsApp();

try {
    // Template criado no painel WhatsApp com 3 parâmetros
    $resultado = $whatsapp->sendTemplate(
        '5511999999999',
        'orcamento_enviado', // Nome do template
        [
            'João Silva',        // {{1}} - Nome do cliente
            'ORÇ-2024-001',     // {{2}} - Número do orçamento
            'R$ 4.000,00'       // {{3}} - Valor
        ],
        'pt_BR' // Idioma
    );
    
    echo "Template enviado!";
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
?>
```

---

## 3. Classe IA - Integração com Inteligência Artificial

### Configuração Inicial

Adicionar constantes (escolher um provider):

#### Para OpenAI:
```php
<?php
define('IA_PROVIDER', 'openai');
define('IA_API_KEY', 'sk-seu_token_openai_aqui');
define('IA_MODEL', 'gpt-3.5-turbo'); // ou gpt-4
define('IA_MAX_TOKENS', 1000);
define('IA_TEMPERATURE', 0.7);
?>
```

#### Para Google Gemini:
```php
<?php
define('IA_PROVIDER', 'gemini');
define('IA_API_KEY', 'sua_chave_gemini_aqui');
define('IA_MODEL', 'gemini-pro');
define('IA_MAX_TOKENS', 1000);
define('IA_TEMPERATURE', 0.7);
?>
```

#### Para Anthropic Claude:
```php
<?php
define('IA_PROVIDER', 'claude');
define('IA_API_KEY', 'sk-sua_chave_claude_aqui');
define('IA_MODEL', 'claude-3-sonnet-20240229');
define('IA_MAX_TOKENS', 1000);
define('IA_TEMPERATURE', 0.7);
?>
```

### 3.1 Melhorar Texto

```php
<?php
require_once 'classes/IA.php';

$ia = new IA();

try {
    $textoBruto = "Oi, temos um orcamento pra voce. eh de 2500 reais.";
    
    $textoProfissional = $ia->improveText(
        $textoBruto,
        'profissional', // estilo: profissional, casual, formal, técnico
        'pt_BR' // idioma
    );
    
    echo "Texto original: " . $textoBruto . "\n";
    echo "Texto melhorado: " . $textoProfissional;
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}

// Saída esperada:
// "Prezado cliente, segue em anexo nosso orçamento no valor de R$ 2.500,00..."
?>
```

### 3.2 Gerar Checklist

```php
<?php
require_once 'classes/IA.php';

$ia = new IA();

try {
    $descricao = "Implementar novo sistema de gestão de vendas com integração com múltiplos canais de pagamento";
    
    $checklist = $ia->generateChecklist(
        $descricao,
        'projeto', // tipo: projeto, vendas, atendimento, manutencao, testes, implantacao
        'pt_BR'
    );
    
    echo "Checklist: " . $checklist['titulo'] . "\n\n";
    
    foreach ($checklist['items'] as $item) {
        $prioridade = $item['prioridade'] === 'alta' ? '🔴' : ($item['prioridade'] === 'média' ? '🟡' : '🟢');
        echo "$prioridade " . $item['tarefa'] . " (" . $item['tempo'] . ")\n";
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}

// Saída esperada:
// Checklist: Implementação de Sistema de Gestão de Vendas
// 
// 🔴 Análise de requisitos e especificação (2 dias)
// 🔴 Desenho da arquitetura do sistema (1 dia)
// 🔴 Integração com gateways de pagamento (3 dias)
// 🟡 Desenvolvimento do dashboard de vendas (2 dias)
// 🟢 Testes automatizados (2 dias)
?>
```

### 3.3 Usar Assistente Contextual

```php
<?php
require_once 'classes/IA.php';

$ia = new IA();

try {
    $contexto = "Somos uma agência de web design e desenvolvimento. Temos 5 colaboradores.";
    $pergunta = "Como melhorar nossa taxa de conversão de leads para clientes pagos?";
    
    $resposta = $ia->assistente(
        $pergunta,
        $contexto,
        'resposta' // tipo: resposta, explicacao, dica, codigo, lista, analise
    );
    
    echo $resposta;
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}

// Saída esperada: Uma resposta estratégica com dicas práticas para sua agência
?>
```

### 3.4 Gerar Código (Usando tipo 'codigo')

```php
<?php
require_once 'classes/IA.php';

$ia = new IA();

try {
    $contexto = "Estamos usando PHP e MySQL. Precisamos validar formulários.";
    $pergunta = "Crie uma classe PHP para validar email e telefone brasileiro";
    
    $codigo = $ia->assistente(
        $pergunta,
        $contexto,
        'codigo' // Retorna código pronto para usar
    );
    
    echo "<pre>" . htmlspecialchars($codigo) . "</pre>";
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
?>
```

---

## Exemplos Combinados

### Exemplo 1: Gerar Orçamento, Enviar via WhatsApp e PDF

```php
<?php
require_once 'classes/PDF.php';
require_once 'classes/WhatsApp.php';
require_once 'classes/IA.php';

// 1. Melhorar descrição usando IA
$ia = new IA();
$descricaoOriginal = "precisa de um website novo com vendas online";
$descricaoMelhorada = $ia->improveText($descricaoOriginal, 'profissional');

// 2. Preparar dados do orçamento
$dadosOrcamento = [
    'numero' => 'ORÇ-2024-001',
    'data' => date('Y-m-d'),
    'cliente' => [
        'nome' => 'João Silva',
        'email' => 'joao@example.com',
        'telefone' => '(11) 99999-9999'
    ],
    'produtos' => [
        [
            'descricao' => $descricaoMelhorada,
            'quantidade' => 1,
            'preco' => 3000.00,
            'total' => 3000.00
        ]
    ]
];

// 3. Gerar PDF
$pdf = new PDF();
$pdf->generateOrcamento($dadosOrcamento, false);

// 4. Enviar PDF via WhatsApp
$whatsapp = new WhatsApp();
$whatsapp->sendDocument(
    '5511999999999',
    '/caminho/para/orcamento_2024_001.pdf',
    'document',
    'Seu orçamento está pronto!'
);

// 5. Enviar mensagem complementar
$whatsapp->sendMessage(
    '5511999999999',
    'Olá João! Seu orçamento foi enviado. Qualquer dúvida, estou à disposição!'
);

echo "Processo concluído com sucesso!";
?>
```

### Exemplo 2: Gerar Checklist e Enviar via WhatsApp

```php
<?php
require_once 'classes/IA.php';
require_once 'classes/WhatsApp.php';

$ia = new IA();
$whatsapp = new WhatsApp();

// Gerar checklist para novo cliente
$checklist = $ia->generateChecklist(
    'Onboarding de novo cliente SaaS',
    'projeto'
);

// Formatar como mensagem
$mensagem = "✅ *Checklist de Onboarding*\n\n";
foreach ($checklist['items'] as $item) {
    $mensagem .= "☐ " . $item['tarefa'] . " (" . $item['prioridade'] . ")\n";
}

// Enviar via WhatsApp
$whatsapp->sendMessage('5511999999999', $mensagem);

echo "Checklist enviado com sucesso!";
?>
```

---

## Tratamento de Erros

Todos os métodos lançam exceções em caso de erro. Sempre use try-catch:

```php
<?php
try {
    // Seu código aqui
    $resultado = $pdf->generateOrcamento($dados);
} catch (Exception $e) {
    // Log do erro
    error_log("Erro no PDF: " . $e->getMessage());
    
    // Mostrar mensagem amigável ao usuário
    echo "Desculpe, houve um erro ao processar sua solicitação.";
}
?>
```

---

## Notas Importantes

1. **PDF**: Requer TCPDF instalado via Composer
2. **WhatsApp**: Requer conta WhatsApp Business com API aprovada
3. **IA**: Requer conta ativa e API key de um dos provedores (OpenAI, Gemini, Claude)
4. **Logs**: Todos os eventos são registrados em `/logs/`
5. **Segurança**: Mantenha as chaves de API em arquivo de configuração não versionado
6. **Rate Limits**: Respeite os limites de requisições dos provedores
