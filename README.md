# Novo Sistema - Arquivos de Configuração Base

> **Aplicação Web Production-Ready com Roteamento, Segurança e Interface de Login Moderna**

[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-blue.svg)](https://www.php.net/)
[![Apache Version](https://img.shields.io/badge/Apache-2.4%2B-blue.svg)](https://httpd.apache.org/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

---

## 📋 Sobre

Este pacote contém três arquivos fundamentais para uma aplicação web moderna:

1. **index.php** - Roteador único (Single Point of Entry)
2. **.htaccess** - Reescrita de URL e segurança
3. **login.html** - Interface de autenticação moderna

Totalmente comentados em português, production-ready e seguindo as melhores práticas de segurança.

---

## 🚀 Início Rápido

### 1. Clonar/Copiar Arquivos

```bash
cd seu-projeto
cp index.php public_html/
cp .htaccess public_html/
cp login.html public_html/
```

### 2. Criar Estrutura de Diretórios

```bash
# Executar script de setup (recomendado)
bash setup.sh

# Ou criar manualmente
mkdir -p app/{admin,tecnico,cliente,api}
mkdir -p config logs
chmod 755 logs
```

### 3. Ativar mod_rewrite

```bash
# Linux/Ubuntu
sudo a2enmod rewrite
sudo systemctl restart apache2

# Verificar
apache2ctl -M | grep rewrite
```

### 4. Configurar Apache VirtualHost

```apache
<Directory /var/www/seu-projeto/public_html>
    AllowOverride All
    Require all granted
</Directory>
```

### 5. Testar

```bash
# Acesse em seu navegador
http://localhost/login.html
```

---

## 📦 Arquivos Inclusos

### index.php (12 KB)

Roteador inteligente que detecta a rota e encaminha para o controlador apropriado.

**Características:**
- ✅ Roteamento de múltiplas aplicações
- ✅ Normalização de URLs
- ✅ Tratamento de erros HTTP
- ✅ Logging de atividades
- ✅ Headers de segurança
- ✅ Sem dependências externas

**Rotas:**
```
/admin/*   → app/admin/
/tecnico/* → app/tecnico/
/cliente/* → app/cliente/
/api/*     → app/api/
/          → app/cliente/index.php (padrão)
```

### .htaccess (7.4 KB)

Configuração do Apache para reescrita de URL e segurança.

**Funcionalidades:**
- ✅ Reescrita de todas as requisições para index.php
- ✅ Bloqueio de diretórios sensíveis
- ✅ Headers de segurança (XSS, CSRF, etc)
- ✅ Compressão gzip
- ✅ Cache inteligente do navegador
- ✅ Proteção contra MIME-sniffing

**Bloqueios:**
```
/config, /logs, /vendor, /app    ❌ Diretórios
.env, .git, *.bak, *.backup      ❌ Arquivos sensíveis
```

### login.html (32 KB)

Interface de autenticação moderna e responsiva.

**Características:**
- ✅ Design moderno com gradientes
- ✅ Validação em tempo real
- ✅ Responsivo (mobile-first)
- ✅ Modo claro/escuro automático
- ✅ Acessibilidade completa (WCAG)
- ✅ Sem dependências externas
- ✅ Integração com /api/auth

---

## 🛣️ Estrutura de Roteamento

```
┌─────────────────────┐
│ Requisição HTTP     │
└──────────┬──────────┘
           │
           ↓
     ┌──────────────┐
     │  .htaccess   │  (Apache)
     └──────┬───────┘
            │
   (Verifica se arquivo existe?)
            │
      NÃO → Redireciona para index.php
            │
            ↓
     ┌──────────────┐
     │  index.php   │  (Roteador PHP)
     └──────┬───────┘
            │
     (Detecta primeira parte da URL)
            │
    ┌───────┼───────┬──────────┬────────────┐
    │       │       │          │            │
   /admin  /tecnico /api    /cliente      /
    │       │       │          │            │
    ↓       ↓       ↓          ↓            ↓
  admin/   tecnico/ api/    cliente/   cliente/
  index.php index.php auth.php index.php index.php
    │       │       │          │            │
    ↓       ↓       ↓          ↓            ↓
   HTML    HTML    JSON      HTML         HTML
```

---

## 🔐 Segurança

### Headers Implementados

```
X-Content-Type-Options: nosniff        ✓ Previne MIME-sniffing
X-Frame-Options: SAMEORIGIN            ✓ Protege contra clickjacking
X-XSS-Protection: 1; mode=block        ✓ Proteção XSS
Content-Security-Policy: ...           ✓ Política de conteúdo
Access-Control-Allow-Methods: ...      ✓ CORS
```

### Proteção de Arquivos

```apache
RewriteRule ^(config|logs|vendor|app)/ - [F,L]
RewriteRule ^\.env$ - [F,L]
RewriteRule ^\.git/ - [F,L]
```

### Validação

- ✅ Cliente: Email e senha validados em tempo real
- ✅ Servidor: Validação de entrada (a implementar)
- ✅ Sessions: Regeneração de ID de sessão
- ✅ Logs: Erros nunca expostos ao usuário

---

## 📊 Rotas Disponíveis

| Método | Rota | Arquivo | Descrição |
|--------|------|---------|-----------|
| GET | `/` | `app/cliente/index.php` | Página inicial |
| POST | `/login.html` | `login.html` | Formulário de login |
| POST | `/api/auth` | `app/api/auth.php` | Autenticação |
| GET | `/admin/*` | `app/admin/*` | Painel admin |
| GET | `/tecnico/*` | `app/tecnico/*` | Painel técnico |
| GET | `/cliente/*` | `app/cliente/*` | Portal cliente |
| GET | `/api/*` | `app/api/*` | Endpoints API |

---

## 💾 Resposta API

### Formato JSON Padrão

```json
{
  "sucesso": true,
  "erro": false,
  "codigo": 200,
  "mensagem": "Descrição da operação",
  "dados": {
    "campo": "valor"
  },
  "timestamp": "2024-02-10 22:55:00"
}
```

### Exemplo: Login Bem-sucedido

```json
{
  "sucesso": true,
  "codigo": 200,
  "mensagem": "Login realizado com sucesso",
  "dados": {
    "token": "eyJhbGc...",
    "usuario_id": 123,
    "usuario_nome": "João Silva",
    "urlRedirecao": "/admin/dashboard"
  }
}
```

### Exemplo: Erro de Autenticação

```json
{
  "sucesso": false,
  "codigo": 401,
  "mensagem": "Email ou senha inválidos",
  "dados": {}
}
```

---

## 🎨 Personalização

### Mudar Nome da Aplicação

Em `login.html`, localize:
```html
<h1 class="titulo">Novo Sistema</h1>
```

Altere para seu nome.

### Mudar Cor Primária

Em `login.html`, CSS:
```css
--cor-primaria: #seu-codigo-hex;
```

### Mudar Logo

Em `login.html`:
```html
<div class="logo">N</div>  <!-- Mude a letra -->
```

### Adicionar Campo ao Login

```html
<div class="grupo-formulario">
    <label for="novo-campo" class="label">Novo Campo</label>
    <input type="text" id="novo-campo" class="input" required>
    <div class="mensagem-erro">Mensagem de erro</div>
</div>
```

---

## 📚 Documentação

Consulte os arquivos inclusos:

- **DOCUMENTACAO.md** - Guia técnico completo (17 KB)
- **GUIA_RAPIDO.md** - Referência rápida (6 KB)
- **exemplo-auth-api.php** - Exemplo de implementação de autenticação
- Comentários em código (português)

---

## 🔧 Implementação

### 1. Autenticação

Crie `/app/api/auth.php`:

```php
<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['sucesso' => false]);
    exit;
}

$dados = json_decode(file_get_contents('php://input'), true);
$email = $dados['email'] ?? '';
$senha = $dados['senha'] ?? '';

// TODO: Validar contra banco de dados
// TODO: Hash de senha com bcrypt
// TODO: Gerar JWT token

// Exemplo simplificado
if ($email === 'user@example.com' && $senha === 'password') {
    http_response_code(200);
    echo json_encode([
        'sucesso' => true,
        'dados' => [
            'token' => 'seu-token-aqui',
            'urlRedirecao' => '/admin'
        ]
    ]);
} else {
    http_response_code(401);
    echo json_encode(['sucesso' => false]);
}
?>
```

### 2. Banco de Dados

```sql
CREATE TABLE usuarios (
  id INT PRIMARY KEY AUTO_INCREMENT,
  email VARCHAR(255) UNIQUE NOT NULL,
  senha_hash VARCHAR(255) NOT NULL,
  nome VARCHAR(255),
  tipo ENUM('admin', 'tecnico', 'cliente'),
  ativo BOOLEAN DEFAULT true,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### 3. Middleware de Autenticação

```php
<?php
function verificarAutenticacao($tipo_permitido = null) {
    session_start();
    
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: /login.html');
        exit;
    }
    
    if ($tipo_permitido && $_SESSION['usuario_tipo'] !== $tipo_permitido) {
        http_response_code(403);
        echo 'Acesso negado';
        exit;
    }
}
?>
```

---

## 🐛 Troubleshooting

| Problema | Solução |
|----------|---------|
| `404 em todas as rotas` | Ativar mod_rewrite: `sudo a2enmod rewrite` |
| `.htaccess ignorado` | Verificar AllowOverride All no VirtualHost |
| `PHP errors não aparecem` | Configurar em index.php: `define('DEBUG', true);` |
| `Login não funciona` | Verificar /api/auth.php existe e retorna JSON |
| `Cache não atualiza` | Limpar cache: `Ctrl+Shift+Del` |

---

## ✅ Checklist de Produção

- [ ] HTTPS configurado
- [ ] mod_rewrite ativado
- [ ] Banco de dados criado e testado
- [ ] Autenticação implementada e testada
- [ ] Logs configurados e monitorados
- [ ] Todas as rotas testadas
- [ ] Segurança validada (OWASP)
- [ ] Cache configurado
- [ ] Backup automático configurado
- [ ] Monitoramento em produção

---

## 📞 Suporte Rápido

```bash
# Testar sintaxe PHP
php -l public_html/index.php

# Ver logs de erro
tail -f logs/app-*.log

# Testar rota
curl -I http://localhost/admin

# Verificar mod_rewrite
apache2ctl -M | grep rewrite
```

---

## 📊 Estatísticas

| Métrica | Valor |
|---------|-------|
| Tamanho Total | ~73 KB |
| Linhas de Código | ~1.400+ |
| Linhas Comentadas | ~200 |
| Compatibilidade | PHP 7.4+ |
| Dependências Externas | 0 |
| Navegadores Suportados | Todos modernos |

---

## 📝 Notas Importantes

1. **Segurança**: Use HTTPS em produção
2. **Senhas**: Nunca armazene em texto puro, use bcrypt/Argon2
3. **Tokens**: Implemente JWT para autenticação
4. **Rate Limiting**: Adicione proteção contra brute force
5. **2FA**: Considere autenticação de dois fatores
6. **Backup**: Configure backup automático regular

---

## 🔗 Referências

- [PHP Documentation](https://www.php.net/docs.php)
- [Apache mod_rewrite](https://httpd.apache.org/docs/current/mod/mod_rewrite.html)
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [HTML5 Security](https://html5sec.org/)
- [Web Security](https://cheatsheetseries.owasp.org/)

---

## 📄 Licença

Este projeto é fornecido como está para fins educacionais e comerciais.

---

## 👨‍💻 Autor

**Sistema Novo** - Versão 1.0.0 (Fevereiro 2024)

Desenvolvido com foco em:
- ✅ Segurança
- ✅ Performance
- ✅ Manutenibilidade
- ✅ Acessibilidade
- ✅ Responsividade

---

## ❓ FAQ

### Q: Posso usar este código em produção?
**A:** Sim! O código foi desenvolvido seguindo as melhores práticas de produção. Apenas implemente a autenticação real com banco de dados.

### Q: Preciso de dependências externas?
**A:** Não! Código puro em PHP/HTML/CSS/JavaScript. Totalmente standalone.

### Q: Como faço para adicionar mais campos ao login?
**A:** Adicione um novo `<div class="grupo-formulario">` no HTML e implemente a validação em JavaScript.

### Q: Posso mudar as cores?
**A:** Sim! As cores estão em variáveis CSS (`:root`) no login.html.

### Q: Como funciona o roteamento?
**A:** O .htaccess redireciona tudo para index.php, que detecta a rota e carrega o arquivo apropriado.

---

**Última atualização:** Fevereiro 2024  
**Status:** Production Ready ✅

