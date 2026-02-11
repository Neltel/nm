# Quick Reference - Novos Endpoints da API

## 📋 Resumo Executivo

5 novos arquivos de endpoint foram criados com 35 endpoints totais e 2,255 linhas de código, seguindo os padrões existentes da API.

## 🔐 Autenticação

Todos os endpoints requerem:
```
Authorization: Bearer {token_jwt}
```

## 📦 Endpoints por Recurso

### 1️⃣ GARANTIAS (`/api/garantias`)
| Método | Endpoint | Descrição | Status |
|--------|----------|-----------|--------|
| GET | `/api/garantias` | Lista garantias (paginado) | ✅ |
| GET | `/api/garantias/:id` | Obtém uma garantia | ✅ |
| POST | `/api/garantias` | Cria nova garantia | ✅ |
| PUT | `/api/garantias/:id` | Atualiza garantia | ✅ |
| DELETE | `/api/garantias/:id` | Deleta garantia | ✅ |
| POST | `/api/garantias/:id/pdf` | Gera PDF com termos legais | ✅ |
| POST | `/api/garantias/:id/whatsapp` | Envia via WhatsApp | ✅ |

**Tipos de Garantia:** `fabricante`, `estendida`, `terceiros`

### 2️⃣ PREVENTIVAS (`/api/preventivas`)
| Método | Endpoint | Descrição | Status |
|--------|----------|-----------|--------|
| GET | `/api/preventivas` | Lista contratos (paginado) | ✅ |
| GET | `/api/preventivas/:id` | Obtém contrato com checklists | ✅ |
| POST | `/api/preventivas` | Cria contrato | ✅ |
| PUT | `/api/preventivas/:id` | Atualiza contrato | ✅ |
| DELETE | `/api/preventivas/:id` | Deleta contrato e checklists | ✅ |
| POST | `/api/preventivas/:id/checklist` | Adiciona item ao checklist | ✅ |
| PUT | `/api/preventivas/checklist/:id` | Atualiza item do checklist | ✅ |

### 3️⃣ RELATÓRIOS (`/api/relatorios`)
| Método | Endpoint | Descrição | Status |
|--------|----------|-----------|--------|
| GET | `/api/relatorios` | Lista relatórios (paginado) | ✅ |
| GET | `/api/relatorios/:id` | Obtém relatório com fotos | ✅ |
| POST | `/api/relatorios` | Cria relatório | ✅ |
| PUT | `/api/relatorios/:id` | Atualiza relatório | ✅ |
| POST | `/api/relatorios/:id/fotos` | Adiciona foto (upload) | ✅ |
| POST | `/api/relatorios/:id/pdf` | Gera PDF do relatório | ✅ |
| POST | `/api/relatorios/:id/ia-improve` | Melhora descrição com IA | ✅ |

**Formatos de imagem suportados:** JPG, PNG, GIF, WebP (máx 5MB)

### 4️⃣ FINANCEIRO (`/api/financeiro`)
| Método | Endpoint | Descrição | Status |
|--------|----------|-----------|--------|
| GET | `/api/financeiro` | Lista transações (paginado) | ✅ |
| GET | `/api/financeiro/:id` | Obtém transação | ✅ |
| POST | `/api/financeiro` | Cria transação (receita/despesa) | ✅ |
| PUT | `/api/financeiro/:id` | Atualiza transação | ✅ |
| DELETE | `/api/financeiro/:id` | Deleta transação | ✅ |
| GET | `/api/financeiro/extrato` | Gera extrato mensal | ✅ |
| GET | `/api/financeiro/graficos` | Dados para gráficos | ✅ |

**Tipos:** `receita`, `despesa`
**Status:** `pendente`, `pago`, `cancelado`

### 5️⃣ PMP (`/api/pmp`)
| Método | Endpoint | Descrição | Status |
|--------|----------|-----------|--------|
| GET | `/api/pmp/contratos` | Lista contratos (paginado) | ✅ |
| GET | `/api/pmp/contratos/:id` | Obtém contrato com equipamentos | ✅ |
| POST | `/api/pmp/contratos` | Cria contrato | ✅ |
| PUT | `/api/pmp/contratos/:id` | Atualiza contrato | ✅ |
| POST | `/api/pmp/contratos/:id/equipamentos` | Adiciona equipamento | ✅ |
| POST | `/api/pmp/contratos/:id/checklists` | Cria execução de checklist | ✅ |
| POST | `/api/pmp/checklists/:id/ia` | Gera checklist com IA | ✅ |

## 📊 Exemplos de Uso

### Criar Garantia
```bash
curl -X POST http://localhost/api/garantias \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "produto_id": 5,
    "cliente_id": 10,
    "numero_serie": "SN-12345",
    "tipo": "estendida",
    "meses_validade": 36,
    "descricao": "Garantia estendida com cobertura total",
    "valor_cobertura": 10000.00
  }'
```

### Listar Garantias
```bash
curl http://localhost/api/garantias?page=1&limit=20&status=ativa \
  -H "Authorization: Bearer {token}"
```

### Criar Transação Financeira
```bash
curl -X POST http://localhost/api/financeiro \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "tipo": "receita",
    "descricao": "Serviço de instalação",
    "valor": 1500.00,
    "data_transacao": "2024-01-10",
    "categoria": "Serviços"
  }'
```

### Obter Extrato Mensal
```bash
curl "http://localhost/api/financeiro/extrato?mes=1&ano=2024" \
  -H "Authorization: Bearer {token}"
```

### Adicionar Foto ao Relatório
```bash
curl -X POST http://localhost/api/relatorios/1/fotos \
  -H "Authorization: Bearer {token}" \
  -F "arquivo=@/caminho/foto.jpg" \
  -F "descricao=Foto antes da manutenção"
```

## 🔗 Arquivo de Rotas

Localização: `/public_html/api/routes.php`

Todos os 5 endpoints foram registrados na estrutura switch:
```php
case 'garantias':
case 'preventivas':
case 'relatorios':
case 'financeiro':
case 'pmp':
```

## ✅ Recursos Implementados

- ✅ CRUD completo (Create, Read, Update, Delete)
- ✅ Paginação com limite configurável
- ✅ Filtros por status, tipo, categoria, cliente
- ✅ Autenticação JWT obrigatória
- ✅ Validação de entrada em todos os campos
- ✅ Sanitização de dados (XSS protection)
- ✅ Prepared statements (SQL injection protection)
- ✅ Tratamento de erros com status HTTP apropriados
- ✅ Geração de PDF
- ✅ Upload de fotos
- ✅ Integração com WhatsApp
- ✅ Integração com IA
- ✅ Comentários em português
- ✅ Timestamps de criação/atualização
- ✅ Rastreamento de usuário (criado_por/atualizado_por)

## 📁 Estrutura de Arquivos

```
/public_html/api/
├── garantias.php           (490 linhas)
├── preventivas.php         (410 linhas)
├── relatorios.php          (504 linhas)
├── financeiro.php          (424 linhas)
├── pmp.php                 (427 linhas)
├── routes.php              (ATUALIZADO)
├── NOVOS_ENDPOINTS.md      (Documentação completa)
└── QUICK_REFERENCE.md      (Este arquivo)
```

## 🚨 Status HTTP

| Código | Significado |
|--------|------------|
| 200 | OK - Sucesso |
| 201 | Created - Recurso criado |
| 400 | Bad Request - Entrada inválida |
| 401 | Unauthorized - Não autenticado |
| 404 | Not Found - Recurso não encontrado |
| 405 | Method Not Allowed - Método não permitido |
| 500 | Internal Server Error - Erro do servidor |
| 503 | Service Unavailable - Serviço indisponível (IA) |

## 🔍 Validações Principais

### Garantias
- Tipo: `fabricante`, `estendida`, `terceiros`
- Status: `ativa`, `expirada`, `cancelada`
- Meses de validade: 1-120
- Valor de cobertura: positivo

### Manutenção Preventiva
- Frequência: 1-365 dias
- Status: `ativo`, `inativo`, `expirado`

### Relatórios
- Status: `rascunho`, `concluido`, `aprovado`
- Fotos: JPG, PNG, GIF, WebP, máx 5MB

### Financeiro
- Tipo: `receita`, `despesa`
- Status: `pendente`, `pago`, `cancelado`
- Valor: > 0
- Data: YYYY-MM-DD

### PMP
- Status: `ativo`, `inativo`, `encerrado`

## 📞 Suporte

Para mais detalhes, consulte `NOVOS_ENDPOINTS.md`

---

**Versão:** 1.0
**Data de Criação:** 10 de Janeiro de 2024
**Total de Endpoints:** 35
**Total de Linhas:** 2,255
**Padrão:** REST API com JWT Authentication
