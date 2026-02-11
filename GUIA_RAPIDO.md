# Guia Rápido - Novo Sistema

## ⚡ Início Rápido

### 1. Verificar Mod_Rewrite
```bash
# Linux/Ubuntu
sudo a2enmod rewrite
sudo systemctl restart apache2

# Verificar
apache2ctl -M | grep rewrite
```

### 2. Estrutura de Diretórios
```bash
mkdir -p app/admin app/tecnico app/cliente app/api
mkdir -p config logs
chmod 755 logs
```

### 3. Testar
Acesse: `http://localhost/login.html`

---

## 📁 Arquivos Criados

| Arquivo | Tamanho | Descrição |
|---------|---------|-----------|
| `index.php` | 12 KB | Roteador principal - Ponto de entrada única |
| `.htaccess` | 7.4 KB | Reescrita de URL e segurança |
| `login.html` | 32 KB | Página de login responsiva |
| `DOCUMENTACAO.md` | 16 KB | Documentação completa |

---

## 🛣️ Rotas Disponíveis

```
GET  /                      → Página inicial
POST /login.html            → Formulário de login
POST /api/auth              → Endpoint de autenticação

GET  /admin/*               → Painel administrativo
GET  /tecnico/*             → Painel técnico
GET  /cliente/*             → Portal do cliente
GET  /api/*                 → APIs
```

---

## 🔑 Configurações Principais

### Variables PHP (index.php)
```php
ENV = 'production' ou 'development'
DEBUG = false em produção, true em desenvolvimento
ROOT_DIR = /caminho/raiz
PUBLIC_DIR = /caminho/raiz/public_html
```

### Headers de Segurança (.htaccess)
```
X-Content-Type-Options: nosniff
X-Frame-Options: SAMEORIGIN
X-XSS-Protection: 1; mode=block
```

### Cores Login (login.html)
```
Primária: #6366F1 (Índigo)
Sucesso: #10B981 (Verde)
Erro: #EF4444 (Vermelho)
```

---

## 🔐 Padrão de Resposta API

```json
{
  "sucesso": true/false,
  "erro": false/true,
  "codigo": 200,
  "mensagem": "Descrição",
  "dados": {},
  "timestamp": "2024-02-10 22:55:00"
}
```

---

## ⚙️ Próximos Passos

### 1. Implementar Autenticação
```php
// Em /app/api/auth.php
// Validar email/senha contra banco de dados
// Gerar JWT token
// Retornar token e redireção
```

### 2. Criar Base de Dados
```sql
CREATE TABLE usuarios (
  id INT PRIMARY KEY AUTO_INCREMENT,
  email VARCHAR(255) UNIQUE,
  senha_hash VARCHAR(255),
  nome VARCHAR(255),
  tipo ENUM('admin', 'tecnico', 'cliente'),
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### 3. Implementar Middleware de Autenticação
```php
// Verificar token JWT
// Verificar permissões
// Redirecionar se não autenticado
```

### 4. Criar Páginas de Painel
```
/app/admin/index.php
/app/admin/usuarios.php
/app/admin/relatorios.php

/app/tecnico/index.php
/app/tecnico/chamados.php

/app/cliente/index.php
/app/cliente/servicos.php
```

---

## 🐛 Troubleshooting

### Erro: "Página não encontrada"
✓ Verificar se .htaccess está no lugar correto  
✓ Verificar se mod_rewrite está habilitado  
✓ Verificar se AllowOverride All está configurado  

### Erro: "Requisição demorou muito"
✓ Verificar conexão com internet  
✓ Aumentar timeout (padrão: 10s)  
✓ Verificar logs do servidor  

### Login não funciona
✓ Verificar se /api/auth.php existe  
✓ Verificar Console do navegador (F12)  
✓ Verificar headers da requisição  

### Listagem de diretórios ativa
✓ Desabilitar com: `Options -Indexes` no .htaccess  

---

## 📚 Comandos Úteis

```bash
# Verificar sintaxe PHP
php -l public_html/index.php

# Ver logs de erro
tail -f logs/app-*.log

# Limpar logs
rm logs/app-*.log

# Testar acesso
curl -I http://localhost/admin/usuarios

# Ver headers
curl -I http://localhost/login.html
```

---

## 🎯 Casos de Uso

### Acessar Admin
```
http://localhost/admin
→ Carrega app/admin/index.php
```

### Acessar API
```
http://localhost/api/auth
→ Carrega app/api/auth.php
→ Retorna JSON
```

### Arquivo Estático
```
http://localhost/css/style.css
→ Carrega public_html/css/style.css
→ (Não passa por index.php)
```

### Página Dinâmica
```
http://localhost/minha-pagina
→ Carrega app/cliente/minha-pagina.php
→ (Passa por index.php se não existir arquivo)
```

---

## 🎨 Personalização Rápida

### Mudar Nome do Sistema
Edite em `login.html`:
```html
<h1 class="titulo">Seu Novo Nome</h1>
```

### Mudar Logo (Letra)
```html
<div class="logo">S</div>  <!-- Mude de N para S -->
```

### Mudar Cor Primária
Em `login.html` ou CSS:
```css
--cor-primaria: #seu-codigo-hex;
```

### Mudar Mensagem de Erro
Em `login.html`, JavaScript:
```javascript
CONFIG.MENSAGENS.ERRO_AUTENTICACAO = 'Sua mensagem';
```

---

## 📊 Estatísticas

- **Linhas de código**: ~500 (index.php)
- **Linhas de CSS**: ~600 (login.html)
- **Linhas de JavaScript**: ~300 (login.html)
- **Linhas de Apache**: ~150 (.htaccess)

---

## ✅ Checklist de Produção

- [ ] Configurar HTTPS
- [ ] Ativar mod_rewrite
- [ ] Criar banco de dados
- [ ] Implementar autenticação
- [ ] Configurar logs
- [ ] Testar todas as rotas
- [ ] Validar segurança
- [ ] Configurar cache
- [ ] Backup automático
- [ ] Monitoramento ativo

---

## 📞 Suporte Rápido

| Problema | Solução |
|----------|---------|
| Mod_rewrite não funciona | `sudo a2enmod rewrite && sudo systemctl restart apache2` |
| 404 em todas as rotas | Verificar AllowOverride All no VirtualHost |
| API retorna 500 | Ver `logs/php-errors.log` |
| Login lento | Aumentar timeout em login.html |
| Cache não funciona | Limpar cache do navegador (Ctrl+Shift+Del) |

---

## 🔗 Referências

- [PHP 8 Documentation](https://www.php.net/docs.php)
- [Apache mod_rewrite](https://httpd.apache.org/docs/current/mod/mod_rewrite.html)
- [HTML5 Security](https://html5sec.org/)
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)

---

## 📝 Notas

- Todos os comentários estão em **português brasileiro**
- Código segue padrão **PSR-12** (PHP Standard Recommendations)
- Design implementa **Material Design 3** principles
- Totalmente **responsivo** (mobile-first)

---

**Última atualização**: Fevereiro 2024  
**Versão**: 1.0.0

