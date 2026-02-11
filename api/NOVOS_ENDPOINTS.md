# Documentação dos Novos Endpoints da API

## Overview

Este documento detalha os cinco novos endpoints criados para o sistema Novo Site. Todos os endpoints requerem autenticação JWT válida via header `Authorization: Bearer {token}`.

---

## 1. GARANTIAS API (`/api/garantias`)

Gerenciamento de garantias de produtos com suporte a geração de PDF com termos legais e envio via WhatsApp.

### Endpoints

#### GET `/api/garantias`
Lista todas as garantias com paginação e filtros.

**Parâmetros Query:**
- `page` (int, default: 1) - Número da página
- `limit` (int, default: 20, máx: 100) - Itens por página
- `status` (string) - Filtro por status: `ativa`, `expirada`, `cancelada`
- `produto_id` (int) - Filtro por ID do produto
- `cliente_id` (int) - Filtro por ID do cliente

**Resposta Exemplo (200):**
```json
{
  "success": true,
  "message": "Garantias obtidas com sucesso",
  "data": {
    "garantias": [
      {
        "id": 1,
        "produto_id": 5,
        "cliente_id": 10,
        "numero_serie": "SN-12345",
        "tipo": "fabricante",
        "meses_validade": 24,
        "data_inicio": "2024-01-01",
        "data_fim": "2026-01-01",
        "status": "ativa",
        "descricao": "Garantia de fabrica",
        "valor_cobertura": 5000.00,
        "criado_em": "2024-01-01 10:00:00"
      }
    ],
    "paginacao": {
      "pagina_atual": 1,
      "total_itens": 50,
      "itens_por_pagina": 20,
      "total_paginas": 3
    }
  }
}
```

#### GET `/api/garantias/:id`
Obtém dados completos de uma garantia específica.

**Resposta Exemplo (200):**
```json
{
  "success": true,
  "message": "Garantia obtida com sucesso",
  "data": {
    "id": 1,
    "produto_id": 5,
    "cliente_id": 10,
    "numero_serie": "SN-12345",
    "tipo": "fabricante",
    "meses_validade": 24,
    "data_inicio": "2024-01-01",
    "data_fim": "2026-01-01",
    "status": "ativa",
    "descricao": "Garantia de fabrica",
    "valor_cobertura": 5000.00,
    "produto_nome": "Ar Condicionado 18000 BTU",
    "cliente_nome": "João Silva"
  }
}
```

#### POST `/api/garantias`
Cria nova garantia de produto.

**Campos Obrigatórios:**
- `produto_id` (int) - ID do produto
- `cliente_id` (int) - ID do cliente
- `numero_serie` (string) - Número de série do produto
- `tipo` (string) - Tipo: `fabricante`, `estendida`, `terceiros`
- `meses_validade` (int) - Duração em meses (1-120)

**Campos Opcionais:**
- `descricao` (string) - Descrição da garantia
- `valor_cobertura` (float) - Valor de cobertura em reais

**Exemplo Request:**
```json
{
  "produto_id": 5,
  "cliente_id": 10,
  "numero_serie": "SN-12345",
  "tipo": "estendida",
  "meses_validade": 36,
  "descricao": "Garantia estendida com cobertura total",
  "valor_cobertura": 10000.00
}
```

**Resposta (201):** Retorna a garantia criada com todos os dados.

#### PUT `/api/garantias/:id`
Atualiza dados de uma garantia existente.

**Campos Atualizáveis:**
- `tipo` - Tipo de garantia
- `status` - Status da garantia
- `descricao` - Descrição
- `valor_cobertura` - Valor de cobertura
- `numero_serie` - Número de série

**Resposta (200):** Retorna a garantia atualizada.

#### DELETE `/api/garantias/:id`
Deleta uma garantia do sistema.

**Resposta (200):**
```json
{
  "success": true,
  "message": "Garantia deletada com sucesso",
  "data": {}
}
```

#### POST `/api/garantias/:id/pdf`
Gera PDF com termos legais da garantia.

**Resposta (201):**
```json
{
  "success": true,
  "message": "PDF gerado com sucesso",
  "data": {
    "pdf": "garantia_1_20240110101030.pdf",
    "url": "/uploads/garantia_1_20240110101030.pdf",
    "criado_em": "2024-01-10 10:10:30"
  }
}
```

#### POST `/api/garantias/:id/whatsapp`
Envia informações da garantia via WhatsApp para o cliente.

**Resposta (200):**
```json
{
  "success": true,
  "message": "Garantia enviada via WhatsApp com sucesso",
  "data": {
    "garantia_id": 1,
    "telefone": "11999999999",
    "status": "enviado",
    "mensagem": "🔒 *Informações da Garantia*\n\n📦 Produto: Ar Condicionado 18000 BTU..."
  }
}
```

---

## 2. PREVENTIVAS API (`/api/preventivas`)

Gerenciamento de contratos de manutenção preventiva com suporte a checklists.

### Endpoints

#### GET `/api/preventivas`
Lista contratos de manutenção preventiva com paginação.

**Parâmetros Query:**
- `page` (int, default: 1) - Número da página
- `limit` (int, default: 20, máx: 100) - Itens por página
- `status` (string) - Filtro: `ativo`, `inativo`, `expirado`
- `cliente_id` (int) - Filtro por cliente
- `equipamento_id` (int) - Filtro por equipamento

#### GET `/api/preventivas/:id`
Obtém contrato com seus checklists associados.

**Resposta Exemplo (200):**
```json
{
  "success": true,
  "message": "Contrato obtido com sucesso",
  "data": {
    "id": 1,
    "cliente_id": 10,
    "equipamento_id": 5,
    "frequencia_dias": 30,
    "descricao": "Manutenção preventiva do ar condicionado",
    "data_inicio": "2024-01-01",
    "valor_mensal": 500.00,
    "status": "ativo",
    "proxima_manutencao": "2024-02-01",
    "cliente_nome": "João Silva",
    "checklists": [
      {
        "id": 1,
        "titulo": "Inspeção Visual",
        "descricao": "Inspecionar componentes visuais",
        "concluido": 0,
        "criado_em": "2024-01-01 10:00:00"
      }
    ]
  }
}
```

#### POST `/api/preventivas`
Cria novo contrato de manutenção preventiva.

**Campos Obrigatórios:**
- `cliente_id` (int) - ID do cliente
- `equipamento_id` (int) - ID do equipamento
- `frequencia_dias` (int) - Frequência em dias (1-365)
- `descricao` (string) - Descrição do contrato

**Campos Opcionais:**
- `data_inicio` (date) - Data de início
- `valor_mensal` (float) - Valor mensal

#### PUT `/api/preventivas/:id`
Atualiza contrato de manutenção.

**Campos Atualizáveis:**
- `frequencia_dias` - Frequência
- `descricao` - Descrição
- `status` - Status
- `valor_mensal` - Valor mensal
- `proxima_manutencao` - Próxima data de manutenção

#### DELETE `/api/preventivas/:id`
Deleta contrato e seus checklists associados.

#### POST `/api/preventivas/:id/checklist`
Adiciona item ao checklist de um contrato.

**Campos Obrigatórios:**
- `titulo` (string) - Título do item
- `descricao` (string) - Descrição do item

**Campos Opcionais:**
- `concluido` (boolean) - Se o item foi concluído

**Resposta (201):** Item de checklist criado.

#### PUT `/api/preventivas/checklist/:id`
Atualiza item de checklist.

**Campos Atualizáveis:**
- `titulo` - Título
- `descricao` - Descrição
- `concluido` - Status de conclusão

---

## 3. RELATORIOS API (`/api/relatorios`)

Gerenciamento de relatórios de visitas/serviços com suporte a fotos, PDF e melhoria com IA.

### Endpoints

#### GET `/api/relatorios`
Lista relatórios com paginação e filtros.

**Parâmetros Query:**
- `page` (int, default: 1) - Número da página
- `limit` (int, default: 20, máx: 100) - Itens por página
- `status` (string) - Filtro: `rascunho`, `concluido`, `aprovado`
- `tipo` (string) - Tipo de relatório
- `cliente_id` (int) - Filtro por cliente

#### GET `/api/relatorios/:id`
Obtém relatório com suas fotos associadas.

**Resposta Exemplo (200):**
```json
{
  "success": true,
  "message": "Relatório obtido com sucesso",
  "data": {
    "id": 1,
    "cliente_id": 10,
    "titulo": "Manutenção Preventiva - Ar Condicionado",
    "descricao": "Realizada manutenção preventiva completa...",
    "tipo": "manutenção",
    "agendamento_id": 5,
    "tarefas_realizadas": "Limpeza, lubrificação, testes",
    "status": "concluido",
    "cliente_nome": "João Silva",
    "criado_em": "2024-01-10 14:30:00",
    "fotos": [
      {
        "id": 1,
        "url": "/uploads/foto_rel_1_abc123.jpg",
        "descricao": "Equipamento antes da manutenção",
        "criado_em": "2024-01-10 14:31:00"
      }
    ]
  }
}
```

#### POST `/api/relatorios`
Cria novo relatório.

**Campos Obrigatórios:**
- `cliente_id` (int) - ID do cliente
- `titulo` (string) - Título do relatório
- `descricao` (string) - Descrição
- `tipo` (string) - Tipo de relatório

**Campos Opcionais:**
- `agendamento_id` (int) - ID do agendamento relacionado
- `tarefas_realizadas` (string) - Tarefas que foram realizadas

#### PUT `/api/relatorios/:id`
Atualiza relatório existente.

**Campos Atualizáveis:**
- `titulo` - Título
- `descricao` - Descrição
- `tarefas_realizadas` - Tarefas realizadas
- `status` - Status

#### POST `/api/relatorios/:id/fotos`
Adiciona foto ao relatório (upload de arquivo).

**Parâmetros (multipart/form-data):**
- `arquivo` (file) - Arquivo de imagem (JPG, PNG, GIF, WebP, máx 5MB)
- `descricao` (string, opcional) - Descrição da foto

**Resposta (201):**
```json
{
  "success": true,
  "message": "Foto adicionada com sucesso",
  "data": {
    "foto_id": 1,
    "nome": "foto_rel_1_abc123_20240110.jpg",
    "url": "/uploads/foto_rel_1_abc123_20240110.jpg",
    "criado_em": "2024-01-10 14:32:00"
  }
}
```

#### POST `/api/relatorios/:id/pdf`
Gera PDF completo do relatório com fotos.

**Resposta (201):**
```json
{
  "success": true,
  "message": "PDF gerado com sucesso",
  "data": {
    "pdf": "relatorio_1_20240110143200.pdf",
    "url": "/uploads/relatorio_1_20240110143200.pdf",
    "criado_em": "2024-01-10 14:32:00"
  }
}
```

#### POST `/api/relatorios/:id/ia-improve`
Melhora a descrição do relatório usando IA.

**Resposta (200):**
```json
{
  "success": true,
  "message": "Descrição melhorada com sucesso",
  "data": {
    "descricao_original": "Manutenção feita no ar condicionado",
    "descricao_melhorada": "Foi realizada manutenção preventiva completa no equipamento de ar condicionado, incluindo inspeção visual, limpeza de filtros, lubrificação de componentes móveis e testes de funcionamento."
  }
}
```

---

## 4. FINANCEIRO API (`/api/financeiro`)

Gerenciamento de transações financeiras com suporte a extratos e gráficos.

### Endpoints

#### GET `/api/financeiro`
Lista transações com paginação e filtros.

**Parâmetros Query:**
- `page` (int, default: 1) - Número da página
- `limit` (int, default: 20, máx: 100) - Itens por página
- `tipo` (string) - Filtro: `receita`, `despesa`
- `status` (string) - Filtro: `pendente`, `pago`, `cancelado`
- `categoria` (string) - Filtro por categoria

#### GET `/api/financeiro/:id`
Obtém detalhes de uma transação específica.

**Resposta Exemplo (200):**
```json
{
  "success": true,
  "message": "Transação obtida com sucesso",
  "data": {
    "id": 1,
    "tipo": "receita",
    "descricao": "Serviço de manutenção - Ar Condicionado",
    "valor": 500.00,
    "data_transacao": "2024-01-10",
    "categoria": "Serviços",
    "cliente_id": 10,
    "referencia": "FAT-001-2024",
    "status": "pago",
    "cliente_nome": "João Silva",
    "criado_em": "2024-01-10 10:00:00"
  }
}
```

#### POST `/api/financeiro`
Cria nova transação (receita ou despesa).

**Campos Obrigatórios:**
- `tipo` (string) - `receita` ou `despesa`
- `descricao` (string) - Descrição da transação
- `valor` (float) - Valor (> 0)
- `data_transacao` (date) - Data (YYYY-MM-DD)

**Campos Opcionais:**
- `categoria` (string) - Categoria
- `cliente_id` (int) - ID do cliente (se relacionado)
- `referencia` (string) - Número de referência
- `status` (string) - Status (default: `pendente`)

**Exemplo Request:**
```json
{
  "tipo": "receita",
  "descricao": "Serviço de instalação",
  "valor": 1500.00,
  "data_transacao": "2024-01-10",
  "categoria": "Serviços",
  "cliente_id": 10,
  "referencia": "FAT-001-2024"
}
```

#### PUT `/api/financeiro/:id`
Atualiza transação existente.

**Campos Atualizáveis:**
- `descricao` - Descrição
- `valor` - Valor
- `status` - Status
- `categoria` - Categoria
- `referencia` - Referência
- `data_transacao` - Data

#### DELETE `/api/financeiro/:id`
Deleta transação.

#### GET `/api/financeiro/extrato`
Gera extrato mensal com receitas, despesas e saldo.

**Parâmetros Query:**
- `mes` (int, 1-12, default: mês atual) - Mês
- `ano` (int, default: ano atual) - Ano

**Resposta Exemplo (200):**
```json
{
  "success": true,
  "message": "Extrato obtido com sucesso",
  "data": {
    "mes": "01",
    "ano": 2024,
    "data_inicio": "2024-01-01",
    "data_fim": "2024-01-31",
    "receitas": [
      {
        "id": 1,
        "descricao": "Serviço de manutenção",
        "valor": 500.00,
        "data_transacao": "2024-01-10"
      }
    ],
    "despesas": [
      {
        "id": 2,
        "descricao": "Peças de reposição",
        "valor": 200.00,
        "data_transacao": "2024-01-15"
      }
    ],
    "totais": {
      "receitas": 500.00,
      "despesas": 200.00,
      "saldo": 300.00
    }
  }
}
```

#### GET `/api/financeiro/graficos`
Gera dados para gráficos financeiros (últimos períodos).

**Parâmetros Query:**
- `tipo` (string, default: `mensal`) - Tipo: `mensal` ou `anual`
- `periodo` (int, 1-12, default: 6) - Número de períodos anteriores

**Resposta Exemplo (200):**
```json
{
  "success": true,
  "message": "Dados de gráficos obtidos com sucesso",
  "data": {
    "receitas": [1000, 1500, 1200, 1800, 900, 1100],
    "despesas": [500, 600, 400, 700, 300, 400],
    "labels": ["Aug/2023", "Sep/2023", "Oct/2023", "Nov/2023", "Dec/2023", "Jan/2024"]
  }
}
```

---

## 5. PMP API (`/api/pmp`)

Gerenciamento de Plano de Manutenção Preventiva (PMP) com suporte a equipamentos, execuções de checklist e geração de checklists com IA.

### Endpoints

#### GET `/api/pmp/contratos`
Lista contratos PMP com paginação.

**Parâmetros Query:**
- `page` (int, default: 1) - Número da página
- `limit` (int, default: 20, máx: 100) - Itens por página
- `status` (string) - Filtro: `ativo`, `inativo`, `encerrado`
- `cliente_id` (int) - Filtro por cliente

**Resposta Exemplo (200):**
```json
{
  "success": true,
  "message": "Contratos PMP obtidos com sucesso",
  "data": {
    "contratos": [
      {
        "id": 1,
        "numero_contrato": "PMP-001-2024",
        "cliente_id": 10,
        "descricao": "Plano de manutenção preventiva 2024",
        "data_inicio": "2024-01-01",
        "data_fim": "2024-12-31",
        "valor_mensal": 1500.00,
        "status": "ativo",
        "criado_em": "2024-01-01 10:00:00"
      }
    ],
    "paginacao": {
      "pagina_atual": 1,
      "total_itens": 10,
      "itens_por_pagina": 20,
      "total_paginas": 1
    }
  }
}
```

#### GET `/api/pmp/contratos/:id`
Obtém contrato com seus equipamentos associados.

**Resposta Exemplo (200):**
```json
{
  "success": true,
  "message": "Contrato PMP obtido com sucesso",
  "data": {
    "id": 1,
    "numero_contrato": "PMP-001-2024",
    "cliente_id": 10,
    "descricao": "Plano de manutenção preventiva 2024",
    "data_inicio": "2024-01-01",
    "data_fim": "2024-12-31",
    "valor_mensal": 1500.00,
    "status": "ativo",
    "cliente_nome": "João Silva",
    "equipamentos": [
      {
        "id": 1,
        "nome": "Ar Condicionado",
        "modelo": "ACV-18000",
        "numero_serie": "SN-ABC123",
        "localizacao": "Sala de operações"
      }
    ]
  }
}
```

#### POST `/api/pmp/contratos`
Cria novo contrato PMP.

**Campos Obrigatórios:**
- `cliente_id` (int) - ID do cliente
- `numero_contrato` (string) - Número único do contrato
- `descricao` (string) - Descrição do plano

**Campos Opcionais:**
- `data_inicio` (date) - Data de início
- `data_fim` (date) - Data de término
- `valor_mensal` (float) - Valor mensal

#### PUT `/api/pmp/contratos/:id`
Atualiza contrato PMP.

**Campos Atualizáveis:**
- `descricao` - Descrição
- `status` - Status
- `valor_mensal` - Valor mensal
- `data_fim` - Data de término

#### POST `/api/pmp/contratos/:id/equipamentos`
Adiciona equipamento ao contrato.

**Campos Obrigatórios:**
- `nome` (string) - Nome do equipamento
- `modelo` (string) - Modelo

**Campos Opcionais:**
- `numero_serie` (string) - Número de série
- `localizacao` (string) - Localização do equipamento

**Resposta (201):** Equipamento adicionado.

#### POST `/api/pmp/contratos/:id/checklists`
Cria execução de checklist para um equipamento do contrato.

**Campos Obrigatórios:**
- `equipamento_id` (int) - ID do equipamento

**Campos Opcionais:**
- `data_execucao` (date) - Data da execução
- `observacoes` (string) - Observações

**Resposta (201):** Execução de checklist criada.

#### POST `/api/pmp/checklists/:id/ia`
Gera itens do checklist automaticamente usando IA.

**Resposta Exemplo (201):**
```json
{
  "success": true,
  "message": "Itens do checklist gerados com sucesso pela IA",
  "data": {
    "checklist_id": 1,
    "itens_gerados": 4,
    "itens": [
      {
        "id": 1,
        "titulo": "Inspeção Visual",
        "descricao": "Realizar inspeção visual completa do equipamento",
        "concluido": 0
      },
      {
        "id": 2,
        "titulo": "Lubrificação",
        "descricao": "Aplicar lubrificante nos pontos de movimento",
        "concluido": 0
      },
      {
        "id": 3,
        "titulo": "Limpeza",
        "descricao": "Limpar componentes e remover sujeira acumulada",
        "concluido": 0
      },
      {
        "id": 4,
        "titulo": "Testes Funcionais",
        "descricao": "Testar funcionamento de todas as funções",
        "concluido": 0
      }
    ]
  }
}
```

---

## Erros Comuns

### 401 - Não Autenticado
```json
{
  "success": false,
  "message": "Usuário não autenticado",
  "error": true
}
```

**Solução:** Envie um token JWT válido no header `Authorization: Bearer {token}`

### 400 - Requisição Inválida
```json
{
  "success": false,
  "message": "Campo obrigatório é obrigatório",
  "error": true
}
```

**Solução:** Verifique se todos os campos obrigatórios foram enviados com valores válidos.

### 404 - Recurso Não Encontrado
```json
{
  "success": false,
  "message": "Garantia não encontrada",
  "error": true
}
```

**Solução:** Verifique se o ID do recurso existe e está correto.

### 405 - Método Não Permitido
```json
{
  "success": false,
  "message": "Método não permitido",
  "error": true
}
```

**Solução:** Verifique se está usando o método HTTP correto (GET, POST, PUT, DELETE).

---

## Padrões de Resposta

### Sucesso
```json
{
  "success": true,
  "message": "Mensagem de sucesso",
  "data": {
    "id": 1,
    "campo": "valor"
  }
}
```

### Erro
```json
{
  "success": false,
  "message": "Mensagem de erro",
  "error": true,
  "data": {}
}
```

---

## Autenticação

Todos os endpoints requerem um token JWT válido. Inclua o token no header:

```
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

Para obter um token, use o endpoint de login:
```
POST /api/auth/login
```

---

## Validações Comuns

### Tipos de Garantia
- `fabricante` - Garantia do fabricante
- `estendida` - Garantia estendida
- `terceiros` - Garantia de terceiros

### Status de Garantia
- `ativa` - Garantia ativa
- `expirada` - Garantia expirada
- `cancelada` - Garantia cancelada

### Tipos de Transação
- `receita` - Receita (entrada de dinheiro)
- `despesa` - Despesa (saída de dinheiro)

### Status de Transação
- `pendente` - Aguardando pagamento
- `pago` - Já foi pago
- `cancelado` - Foi cancelado

### Status de Relatório
- `rascunho` - Em edição
- `concluido` - Pronto para entrega
- `aprovado` - Aprovado pelo cliente

### Status de Contrato
- `ativo` - Contrato ativo
- `inativo` - Contrato inativo
- `encerrado` - Contrato encerrado

---

## Limites e Paginação

- Máximo de itens por página: **100**
- Máximo de tamanho de arquivo para upload: **5 MB**
- Meses de validade de garantia: **1 a 120 meses**
- Frequência de manutenção: **1 a 365 dias**

---

## Estrutura de Diretórios

Todos os arquivos foram criados no diretório `/public_html/api/`:

```
/public_html/api/
├── garantias.php        (490 linhas)
├── preventivas.php      (410 linhas)
├── relatorios.php       (504 linhas)
├── financeiro.php       (424 linhas)
├── pmp.php              (427 linhas)
└── routes.php           (Atualizado com 5 novos endpoints)
```

**Total de linhas criadas: 2,255**

---

## Recursos da IA Integrados

Alguns endpoints utilizam recursos de IA:

1. **POST /api/relatorios/:id/ia-improve** - Melhora descrições de relatórios
2. **POST /api/pmp/checklists/:id/ia** - Gera checklists de manutenção automaticamente

Estes recursos requerem a classe `IA` implementada no projeto.

---

## Integração com WhatsApp

O endpoint `POST /api/garantias/:id/whatsapp` pode enviar informações de garantia via WhatsApp, requerendo:

- Cliente com telefone registrado
- Integração com serviço de WhatsApp API configurado

---

**Versão:** 1.0
**Data:** Janeiro 2024
**Autenticação:** JWT Bearer Token
**Base URL:** `/api/`
