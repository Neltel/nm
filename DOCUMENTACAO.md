# Documentação - Arquivos de Configuração e Login

## 📋 Visão Geral

Este documento descreve os três arquivos principais que formam o sistema de roteamento e autenticação da aplicação Novo Sistema.

---

## 1. 📁 index.php - Roteador Principal

### O que é?
O `index.php` é o ponto de entrada único (**single point of entry**) para toda a aplicação. Ele funciona como um router inteligente que detecta a rota solicitada e encaminha a requisição para o controlador apropriado.

### Como funciona?

#### Fluxo de Roteamento:
```
Requisição do Usuário
        ↓
   index.php
        ↓
   Normaliza URL
        ↓
   Detecta Segmento
        ↓
   Roteia para:
   - /admin/*    → Painel Administrativo
   - /tecnico/*  → Painel Técnico
   - /cliente/*  → Site Público
   - /api/*      → API RESTful
   - /           → Página Inicial
```

#### Rotas Suportadas:

| Rota | Destino | Descrição |
|------|---------|-----------|
| `/admin/*` | `app/admin/` | Painel administrativo |
| `/tecnico/*` | `app/tecnico/` | Painel técnico |
| `/cliente/*` | `app/cliente/` | Portal do cliente |
| `/api/*` | `app/api/` | Endpoints da API |
| `/` | `app/cliente/index.php` | Página inicial |

#### Estrutura de Diretórios Esperada:
```
seu-projeto/
├── public_html/
│   ├── index.php          ← Roteador principal
│   ├── .htaccess          ← Reescrita de URL
│   ├── login.html         ← Página de login
│   └── assets/            ← Arquivos estáticos (CSS, JS, imagens)
├── app/
│   ├── admin/             ← Controladores do admin
│   │   └── index.php
│   ├── tecnico/           ← Controladores do técnico
│   │   └── index.php
│   ├── cliente/           ← Controladores do cliente
│   │   └── index.php
│   └── api/               ← Endpoints da API
│       ├── auth.php       ← Autenticação
│       └── index.php
├── config/                ← Arquivos de configuração
├── logs/                  ← Arquivos de log
└── vendor/                ← Dependências (Composer)
```

### Recursos de Segurança

1. **Proteção contra XSS**: Headers `X-Content-Type-Options`, `X-XSS-Protection`
2. **CSRF Protection**: Headers apropriados configurados
3. **Session Security**: `session_regenerate_id()` ativado
4. **Error Logging**: Erros registrados em logs, nunca expostos ao usuário
5. **SQL Injection Prevention**: Estrutura pronta para prepared statements

### Funções Principais

#### `normalizarCaminho($path)`
Remove query strings, barras finais desnecessárias e múltiplas barras consecutivas.

```php
normalizarCaminho('/admin/usuarios/') → '/admin/usuarios'
normalizarCaminho('/api/users?page=1') → '/api/users'
```

#### `obterSegmento($uri, $segmento)`
Obtém um segmento específico da URI.

```php
obterSegmento('/admin/usuarios/123', 0) → 'admin'
obterSegmento('/admin/usuarios/123', 1) → 'usuarios'
obterSegmento('/admin/usuarios/123', 2) → '123'
```

#### `erroJson($codigo, $mensagem, $dados)`
Retorna erro em formato JSON com código HTTP apropriado.

```php
erroJson(404, 'Página não encontrada', [
    'uri_solicitada' => '/api/usuarios/999'
]);
```

#### `sucessoJson($dados, $codigo)`
Retorna sucesso em formato JSON.

```php
sucessoJson([
    'usuario_id' => 123,
    'nome' => 'João Silva'
], 200);
```

#### `registrarLog($tipo, $mensagem, $contexto)`
Registra atividades em arquivo de log.

```php
registrarLog('info', 'Usuário fez login', [
    'usuario_id' => 123,
    'ip' => '192.168.1.1'
]);
```

### Configurações Importantes

```php
// Ambiente da aplicação (production ou development)
define('ENV', getenv('APP_ENV') ?: 'production');
define('DEBUG', ENV === 'development');

// Diretórios
define('ROOT_DIR', dirname(__DIR__));
define('PUBLIC_DIR', __DIR__);
define('APP_DIR', ROOT_DIR . '/app');
```

### Tratamento de Erros

O router trata vários tipos de erros:

- **404**: Arquivo/rota não encontrada
- **500**: Erro interno do servidor
- **Exceções**: Capturadas e registradas em log

Todos os erros são retornados em JSON quando apropriado.

---

## 2. 🔒 .htaccess - Reescrita de URL e Segurança

### O que é?
O arquivo `.htaccess` contém regras de reescrita de URL e configurações de segurança do Apache.

### Como funciona?

#### Reescrita de URL (URL Rewriting)

O objetivo principal é redirecionar **todas as requisições para `index.php`**, exceto:
- Arquivos que existem no servidor
- Diretórios que existem no servidor
- Arquivos estáticos (CSS, JS, imagens, etc)

```apache
RewriteCond %{REQUEST_FILENAME} !-f  # Não é arquivo
RewriteCond %{REQUEST_FILENAME} !-d  # Não é diretório
RewriteRule ^(.*)$ index.php?_uri=$1 [QSA,L]
```

#### Exemplo de Reescrita:

| Requisição | Redirecionada para |
|------------|--------------------|
| `/admin/usuarios` | `/index.php?_uri=/admin/usuarios` |
| `/api/auth` | `/index.php?_uri=/api/auth` |
| `/css/style.css` | `/css/style.css` (arquivo real) |
| `/admin/dashboard?page=2` | `/index.php?_uri=/admin/dashboard&page=2` |

### Proteção de Arquivos Sensíveis

O `.htaccess` bloqueia acesso direto a:

```apache
RewriteRule ^(config|logs|vendor|app)/ - [F,L]  # Diretórios sensíveis
RewriteRule ^\.env$ - [F,L]                      # Arquivo .env
RewriteRule ^\.git/ - [F,L]                      # Repositório Git
RewriteRule \.(bak|backup|old|tmp)$ - [F,L]     # Arquivos backup
RewriteRule ^composer\.lock$ - [F,L]             # Composer lock
```

### Headers de Segurança

```apache
X-Content-Type-Options: nosniff          # Impede MIME-sniffing
X-Frame-Options: SAMEORIGIN              # Protege contra clickjacking
X-XSS-Protection: 1; mode=block          # Proteção XSS
Content-Security-Policy: ...             # Política de conteúdo
Access-Control-Allow-Methods: GET, POST  # CORS
```

### Compressão (Gzip)

Ativa compressão automática para:
- HTML
- CSS
- JavaScript
- JSON
- XML

Reduz tamanho de transferência em até 70%.

### Cache do Navegador

Define períodos de cache para diferentes tipos de arquivos:

| Tipo | Duração | Propósito |
|------|---------|----------|
| Imagens | 1 ano | Praticamente nunca mudam |
| Fontes | 1 ano | Praticamente nunca mudam |
| CSS/JS | 1 mês | Mudam com atualizações |
| HTML | 1 semana | Mudam frequentemente |
| JSON | 1 semana | Dados dinâmicos |

### Ativação do .htaccess

Para que o `.htaccess` funcione, o servidor Apache deve:

1. Ter o módulo `mod_rewrite` habilitado
2. Ter `AllowOverride All` configurado no virtual host

#### Verificar se está habilitado:

```bash
# Linux/Mac
a2enmod rewrite

# Reiniciar Apache
sudo systemctl restart apache2
```

#### Configuração do Virtual Host (httpd.conf ou vhost.conf):

```apache
<Directory /var/www/seu-projeto/public_html>
    AllowOverride All
    Require all granted
</Directory>
```

---

## 3. 🎨 login.html - Página de Login Moderna

### O que é?
Uma página de login responsiva, modernas e segura com validação em tempo real.

### Características

#### Design
- **Gradientes modernos**: Cor primária em tons de índigo
- **Glassmorphism**: Efeito de vidro translúcido
- **Responsivo**: Funciona perfeitamente em mobile, tablet e desktop
- **Modo claro/escuro**: Detecta preferência do sistema
- **Animações suaves**: Transições elegantes

#### Validação
- **Email**: Validação de formato em tempo real
- **Senha**: Verificação de campo obrigatório
- **Visual**: Feedback imediato com cores (verde = válido, vermelho = erro)
- **Mensagens**: Descrição clara do problema

#### Segurança
- **HTTPS**: Deve ser usado em produção
- **Proteção contra XSS**: Sem `innerHTML`, usando `textContent`
- **Proteção CSRF**: Implementável com tokens
- **Timeout**: Requisição falha após 10 segundos
- **Senhas**: Nunca armazenadas em localStorage

#### Acessibilidade
- **Labels semânticas**: Associadas aos inputs
- **ARIA labels**: Para leitores de tela
- **Validação nativa HTML5**: Funciona sem JavaScript
- **Modo reduzido de movimento**: Respeita preferências do usuário
- **Tipografia acessível**: Contraste adequado

### Estrutura HTML

```html
<form class="formulario" id="formularioLogin">
  <!-- Email -->
  <div class="grupo-formulario">
    <label for="email">Email</label>
    <input type="email" id="email" name="email" required>
    <div class="mensagem-erro">Insira um email válido</div>
  </div>
  
  <!-- Senha -->
  <div class="grupo-formulario">
    <label for="senha">Senha</label>
    <input type="password" id="senha" name="senha" required>
    <div class="mensagem-erro">A senha é obrigatória</div>
  </div>
  
  <!-- Lembrar-me -->
  <div class="grupo-formulario-checkbox">
    <input type="checkbox" id="lembrarme">
    <label for="lembrarme">Lembrar-me neste dispositivo</label>
  </div>
  
  <!-- Botão -->
  <button type="submit">Entrar</button>
</form>
```

### Cores CSS

```css
--cor-primaria: #6366F1           /* Índigo */
--cor-primaria-escuro: #4F46E5    /* Índigo mais escuro */
--cor-primaria-claro: #818CF8     /* Índigo mais claro */
--cor-sucesso: #10B981            /* Verde */
--cor-erro: #EF4444               /* Vermelho */
--cor-aviso: #F59E0B              /* Laranja */
```

### Fluxo de Autenticação

```
Usuário digita email e senha
          ↓
Validação no cliente (JavaScript)
          ↓
Formulário válido? SIM
          ↓
Desabilita botão (previne submissão múltipla)
          ↓
POST para /api/auth.php
          ↓
Aguarda resposta (timeout 10s)
          ↓
Resposta sucesso?
    ├─ SIM: Armazena token, exibe alerta, redireciona
    └─ NÃO: Exibe mensagem de erro, reabilita botão
```

### JavaScript - Funções Principais

#### `validarEmail(email)`
Valida formato de email usando regex.

```javascript
validarEmail('usuario@email.com') → true
validarEmail('email-invalido') → false
```

#### `validarSenha(senha)`
Verifica se senha tem conteúdo.

```javascript
validarSenha('abc123') → true
validarSenha('') → false
```

#### `exibirAlertaErro(mensagem)`
Mostra alerta de erro visual.

```javascript
exibirAlertaErro('Email ou senha inválidos');
```

#### Escuta do Formulário
```javascript
formulario.addEventListener('submit', async function(evento) {
    evento.preventDefault();
    
    // Validação
    // Requisição à API
    // Tratamento de resposta
});
```

### Requisição à API

O login envia uma requisição POST para `/api/auth.php`:

```javascript
fetch('/api/auth.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    },
    body: JSON.stringify({
        email: 'usuario@email.com',
        senha: 'senha123',
        lembrarme: true
    })
})
```

### Resposta Esperada da API

**Sucesso (200):**
```json
{
    "sucesso": true,
    "erro": false,
    "codigo": 200,
    "dados": {
        "token": "eyJhbGc...",
        "usuario_id": 123,
        "urlRedirecao": "/admin/dashboard"
    },
    "timestamp": "2024-02-10 22:55:00"
}
```

**Erro (401):**
```json
{
    "sucesso": false,
    "erro": true,
    "codigo": 401,
    "mensagem": "Email ou senha inválidos",
    "dados": {},
    "timestamp": "2024-02-10 22:55:00"
}
```

### Armazenamento Local

A página pode armazenar o email do usuário (se autorizado):

```javascript
// Se checkbox "Lembrar-me" está marcado
localStorage.setItem('lembrar_email', 'true');
localStorage.setItem('email_usuario', 'usuario@email.com');
```

Nota: **Nunca armazene senhas** em localStorage, sessionStorage ou cookies!

### Modo Responsivo

Testes em diferentes tamanhos:

- **Desktop (1920px)**: Layout completo
- **Tablet (768px)**: Layout ajustado
- **Mobile (375px)**: Otimizado com padding reduzido

### Personalização

#### Mudar Logo
```html
<div class="logo">N</div>  <!-- Mude a letra aqui -->
```

#### Mudar Cores
```css
:root {
    --cor-primaria: #suas-cores;
}
```

#### Mudar Texto
Todos os textos estão em português e podem ser facilmente adaptados.

#### Adicionar Campo Adicional
```html
<div class="grupo-formulario">
    <label for="novo-campo" class="label">Novo Campo</label>
    <input type="text" id="novo-campo" class="input" required>
</div>
```

---

## 🚀 Implementação Passo a Passo

### 1. Estrutura de Diretórios
```bash
mkdir -p app/admin app/tecnico app/cliente app/api
mkdir -p config logs
```

### 2. Criar Arquivos de API
Crie `/app/api/auth.php`:

```php
<?php
header('Content-Type: application/json');

$metodo = $_SERVER['REQUEST_METHOD'];
$dados = json_decode(file_get_contents('php://input'), true);

if ($metodo !== 'POST') {
    http_response_code(405);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método não permitido']);
    exit;
}

$email = $dados['email'] ?? '';
$senha = $dados['senha'] ?? '';
$lembrarme = $dados['lembrarme'] ?? false;

// Validação
if (empty($email) || empty($senha)) {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Email e senha são obrigatórios']);
    exit;
}

// TODO: Implementar autenticação com banco de dados
// Por enquanto, teste simples
if ($email === 'test@example.com' && $senha === 'password123') {
    http_response_code(200);
    echo json_encode([
        'sucesso' => true,
        'codigo' => 200,
        'dados' => [
            'token' => 'token_jwt_aqui',
            'usuario_id' => 1,
            'urlRedirecao' => '/admin/dashboard'
        ]
    ]);
} else {
    http_response_code(401);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Email ou senha inválidos']);
}
?>
```

### 3. Criar Arquivos de Controladores
Crie `/app/admin/index.php`:

```php
<?php
// Verificar autenticação
// echo 'Painel Administrativo';
?>
```

### 4. Configurar Apache
Certifique-se que o módulo mod_rewrite está ativo e o virtual host permite override.

### 5. Testar
Acesse `http://localhost/login.html` para testar o formulário de login.

---

## 📊 Diagrama de Fluxo Completo

```
┌─────────────────────────────────────────┐
│      Usuário Acessa URL                 │
│      (ex: /admin/usuarios)              │
└─────────────────┬───────────────────────┘
                  │
                  ↓
         ┌─────────────────┐
         │   .htaccess     │
         │  (Apache)       │
         └────────┬────────┘
                  │
        (Verifica se arquivo existe)
                  │
          Não existe? → ↓
         ┌─────────────────────────┐
         │  Redireciona para       │
         │  index.php?_uri=/admin/ │
         │  usuarios               │
         └────────┬────────────────┘
                  │
                  ↓
         ┌─────────────────┐
         │  index.php      │
         │  (Roteador)     │
         └────────┬────────┘
                  │
     (Normaliza caminho, detecta rota)
                  │
          ┌───────┴────────┬────────────┬────────────┐
          │                │            │            │
        /admin/         /tecnico/    /api/        /cliente/
          │                │            │            │
          ↓                ↓            ↓            ↓
    app/admin/      app/tecnico/  app/api/    app/cliente/
    dashboard.php   index.php    auth.php     index.php
          │                │            │            │
          ↓                ↓            ↓            ↓
       HTML            HTML          JSON         HTML
```

---

## 🔐 Checklist de Segurança

- [x] Headers de segurança definidos
- [x] Proteção XSS
- [x] Proteção contra MIME-sniffing
- [x] Proteção contra clickjacking
- [x] Session security
- [x] Validação no cliente
- [x] Validação no servidor (a implementar)
- [x] Proteção de arquivos sensíveis
- [x] Logs de erro seguros
- [ ] HTTPS em produção
- [ ] Proteção CSRF com tokens
- [ ] Rate limiting
- [ ] 2FA (dois fatores)

---

## 📝 Notas Importantes

1. **PHP 7.4+**: O código usa features modernas do PHP
2. **Apache 2.4+**: Requer mod_rewrite habilitado
3. **Servidor**: Use HTTPS em produção
4. **Banco de Dados**: Ainda não implementado (use PDO com prepared statements)
5. **JWT**: Considere usar JWT para tokens de autenticação
6. **Rate Limiting**: Implemente para prevenir brute force
7. **Logs**: Revise logs regularmente

---

## 📞 Suporte

Para dúvidas ou problemas:
1. Verifique se o mod_rewrite está habilitado
2. Certifique-se da estrutura de diretórios
3. Verifique os logs em `logs/`
4. Valide o PHP com `php -l arquivo.php`

---

**Versão**: 1.0.0  
**Data**: Fevereiro de 2024  
**Autor**: Sistema Novo

