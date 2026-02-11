# 🚀 Quick Reference - Classes PDF, WhatsApp e IA

## 📌 Resumo Rápido

| Classe | Linhas | Tamanho | Propósito |
|--------|--------|---------|-----------|
| **PDF.php** | 599 | 24K | Geração de PDFs (orçamentos, garantias, recibos) |
| **WhatsApp.php** | 449 | 20K | Envio de mensagens via WhatsApp API |
| **IA.php** | 522 | 20K | Integração com IA (OpenAI, Gemini, Claude) |

---

## 🎯 Uso Rápido

### PDF - Gerar Orçamento
```php
$pdf = new PDF();
$pdf->generateOrcamento([
    'numero' => 'ORÇ-001',
    'cliente' => ['nome' => 'João', 'email' => 'joao@ex.com'],
    'produtos' => [['descricao' => 'Website', 'preco' => 5000, 'total' => 5000]]
], true);
```

### WhatsApp - Enviar Mensagem
```php
$wa = new WhatsApp();
$wa->sendMessage('5511999999999', 'Seu orçamento foi enviado!');
```

### IA - Melhorar Texto
```php
$ia = new IA();
$texto = $ia->improveText('oi, preciso de website', 'profissional', 'pt_BR');
```

---

## ⚙️ Configuração Mínima

Adicionar em `config/constants.php`:

```php
// WhatsApp
define('WHATSAPP_API_URL', 'https://graph.facebook.com/v18.0/');
define('WHATSAPP_ACCESS_TOKEN', 'seu_token_aqui');
define('WHATSAPP_PHONE_NUMBER_ID', 'seu_id_aqui');

// IA (OpenAI)
define('IA_PROVIDER', 'openai');
define('IA_API_KEY', 'sk-xxxxx');
define('IA_MODEL', 'gpt-3.5-turbo');
define('IA_MAX_TOKENS', 1500);
define('IA_TEMPERATURE', 0.7);
```

---

## 📊 Métodos Disponíveis

### PDF
- `generateOrcamento($dados, $download)` - PDF de orçamento
- `generateGarantia($dados, $download)` - Termo de garantia  
- `generateRecibo($dados, $download)` - Comprovante de pagamento

### WhatsApp
- `sendMessage($numero, $mensagem)` - Mensagem de texto
- `sendDocument($numero, $arquivo, $tipo)` - Enviar arquivo
- `sendTemplate($numero, $template, $params)` - Template pré-aprovado

### IA
- `improveText($texto, $estilo, $idioma)` - Melhora de texto
- `generateChecklist($desc, $tipo, $idioma)` - Gera checklist
- `assistente($pergunta, $contexto, $tipo)` - Assistente contextual

---

## 🔑 Credenciais Necessárias

### WhatsApp
1. Meta Business Account
2. WhatsApp Business API Access Token
3. Phone Number ID

### IA (escolha uma)
- **OpenAI:** API Key em platform.openai.com
- **Gemini:** API Key em ai.google.dev
- **Claude:** API Key em console.anthropic.com

### PDF
Nenhuma (editar dados da empresa na classe)

---

## 📁 Estrutura Criada

```
classes/
├── PDF.php                     (599 linhas)
├── WhatsApp.php               (449 linhas)
├── IA.php                     (522 linhas)
├── README_NOVAS_CLASSES.md    (Documentação completa)
└── EXAMPLES.md                (Exemplos de uso)
```

---

## ✅ Verificação

Todas as classes foram testadas e validadas:
- ✅ Sintaxe PHP válida (php -l)
- ✅ Comentários completos em português
- ✅ Estrutura similar às classes existentes
- ✅ Tratamento robusto de erros
- ✅ Sistema de logs implementado

---

## 🔍 Exemplos Úteis

### 1️⃣ Gerar e enviar orçamento por WhatsApp
```php
// 1. Gerar PDF
$pdf = new PDF();
$pdf->generateOrcamento($dados, false);

// 2. Enviar via WhatsApp
$wa = new WhatsApp();
$wa->sendDocument('55119999999', '/path/orcamento.pdf', 'document');
```

### 2️⃣ Melhorar texto e gerar checklist
```php
$ia = new IA();

// Melhorar descrição
$desc = $ia->improveText('precisa fazer um website', 'profissional');

// Gerar checklist para o projeto
$checklist = $ia->generateChecklist($desc, 'projeto', 'pt_BR');
```

### 3️⃣ Tratamento de erros
```php
try {
    $pdf = new PDF();
    $pdf->generateRecibo($dados);
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
    // Erro registrado automaticamente em logs/
}
```

---

## 📝 Logs

Verificar erros e atividades:
- `logs/pdf_YYYY-MM-DD.log`
- `logs/whatsapp_YYYY-MM-DD.log`
- `logs/ia_YYYY-MM-DD.log`

---

## 💡 Dicas

1. **PDF:** Configure logo da empresa em `public_html/assets/images/logo.png`
2. **WhatsApp:** Comece com número de teste fornecido pela Meta
3. **IA:** Teste com gpt-3.5-turbo antes de usar gpt-4 (mais barato)
4. **Sempre:** Use try-catch para todas as chamadas à API

---

## 📞 Próximos Passos

1. Adicionar constantes em `config/constants.php`
2. Instalar dependências: `composer require tecnickcom/tcpdf`
3. Testar cada classe com exemplos
4. Integrar com aplicação existente
5. Monitorar logs regularmente

---

**Documentação Completa:** Ver `README_NOVAS_CLASSES.md`  
**Exemplos Detalhados:** Ver `EXAMPLES.md`
