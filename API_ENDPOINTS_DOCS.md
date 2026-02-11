# Documentação de Endpoints da API - Novos Módulos

Este documento descreve todos os endpoints implementados para os novos módulos de API do Novo Site.

---

## 1. ORÇAMENTOS - `/api/orcamentos`

Gerenciamento completo de orçamentos com itens, PDFs e integração com WhatsApp.

### GET /api/orcamentos
Lista orçamentos com paginação.

**Parâmetros Query:**
- `page` (int, default: 1) - Número da página
- `limit` (int, default: 15, max: 100) - Itens por página
- `order` (string, default: "criado_em DESC") - Ordenação
- `status` (string) - Filtro de status: pendente, aprovado, rejeitado, convertido
- `cliente_id` (int) - Filtro por cliente

**Resposta Sucesso (200):**
```json
{
  "success": true,
  "message": "Orçamentos obtidos com sucesso",
  "data": {
    "orcamentos": [...],
    "paginacao": {
      "pagina_atual": 1,
      "total_itens": 25,
      "itens_por_pagina": 15,
      "total_paginas": 2
    }
  }
}
```

---

### GET /api/orcamentos/:id
Obtém um orçamento específico com seus itens.

**Parâmetros:**
- `id` (int) - ID do orçamento

**Resposta Sucesso (200):**
```json
{
  "success": true,
  "message": "Orçamento obtido com sucesso",
  "data": {
    "id": 1,
    "numero": 1,
    "cliente_id": 5,
    "cliente_nome": "Empresa XYZ",
    "valor_total": 5000.00,
    "desconto": 500.00,
    "status": "pendente",
    "data_validade": "2024-03-15",
    "itens": [
      {
        "id": 1,
        "produto_id": 10,
        "descricao": "Serviço A",
        "quantidade": 2,
        "valor_unitario": 2250.00
      }
    ]
  }
}
```

---

### POST /api/orcamentos
Cria novo orçamento.

**Body:**
```json
{
  "cliente_id": 5,
  "items": [
    {
      "produto_id": 10,
      "descricao": "Serviço A",
      "quantidade": 2,
      "valor_unitario": 2250.00
    }
  ],
  "desconto": 500.00,
  "validade_dias": 30,
  "condicoes_pagamento": "50% adiantado, 50% na entrega",
  "observacoes": "Cliente preferencial"
}
```

**Resposta Sucesso (201):**
- Retorna o orçamento criado com itens

---

### PUT /api/orcamentos/:id
Atualiza um orçamento existente.

**Body:**
```json
{
  "desconto": 750.00,
  "condicoes_pagamento": "Parcelado em 3x",
  "items": [...]
}
```

**Resposta Sucesso (200):**
- Retorna o orçamento atualizado

---

### DELETE /api/orcamentos/:id
Deleta um orçamento.

**Resposta Sucesso (200):**
```json
{
  "success": true,
  "message": "Orçamento deletado com sucesso",
  "data": {}
}
```

---

### PUT /api/orcamentos/:id/status
Altera status do orçamento.

**Body:**
```json
{
  "status": "aprovado"
}
```

**Status Válidos:** pendente, aprovado, rejeitado, convertido

---

### POST /api/orcamentos/:id/pdf
Gera PDF do orçamento.

**Resposta Sucesso (200):**
```json
{
  "success": true,
  "message": "PDF gerado com sucesso",
  "data": {
    "pdf_url": "/pdf/orcamento_1.pdf"
  }
}
```

---

### POST /api/orcamentos/:id/whatsapp
Envia orçamento via WhatsApp.

**Body:**
```json
{
  "mensagem": "Seu orçamento está pronto!"
}
```

**Resposta Sucesso (200):**
```json
{
  "success": true,
  "message": "Orçamento enviado via WhatsApp com sucesso",
  "data": {
    "orcamento_id": 1,
    "telefone": "5511999999999",
    "enviado_em": "2024-02-15 10:30:00"
  }
}
```

---

## 2. AGENDAMENTOS - `/api/agendamentos`

Gerenciamento de agendamentos, calendários e verificação de disponibilidade.

### GET /api/agendamentos
Lista agendamentos com filtros.

**Parâmetros Query:**
- `page` (int, default: 1)
- `limit` (int, default: 15)
- `status` (string) - agendado, confirmado, cancelado, concluido, nao_compareceu
- `data_inicio` (string, YYYY-MM-DD)
- `data_fim` (string, YYYY-MM-DD)
- `cliente_id` (int)
- `tecnico_id` (int)

---

### GET /api/agendamentos/:id
Obtém detalhes de um agendamento.

---

### POST /api/agendamentos
Cria novo agendamento.

**Body:**
```json
{
  "cliente_id": 5,
  "tecnico_id": 3,
  "data_agendamento": "2024-02-20 14:30:00",
  "descricao": "Manutenção preventiva",
  "servico_id": 2,
  "observacoes": "Cliente prefere à tarde"
}
```

---

### PUT /api/agendamentos/:id
Atualiza agendamento.

**Body:**
```json
{
  "status": "confirmado",
  "data_agendamento": "2024-02-20 15:00:00"
}
```

---

### DELETE /api/agendamentos/:id
Deleta agendamento.

---

### GET /api/agendamentos/disponibilidade
Verifica horários disponíveis.

**Parâmetros Query:**
- `data` (string, YYYY-MM-DD, obrigatório)
- `duracao` (int, default: 60) - Duração em minutos
- `tecnico_id` (int, opcional)

**Resposta Sucesso (200):**
```json
{
  "success": true,
  "message": "Disponibilidade consultada com sucesso",
  "data": {
    "data": "2024-02-20",
    "duracao_minutos": 60,
    "horarios": [
      {"horario": "08:00", "disponivel": true},
      {"horario": "08:30", "disponivel": false},
      {"horario": "09:00", "disponivel": true}
    ]
  }
}
```

---

### GET /api/agendamentos/calendario
Visualização em calendário.

**Parâmetros Query:**
- `mes` (int, 1-12, default: mês atual)
- `ano` (int, default: ano atual)
- `tecnico_id` (int, opcional)

**Resposta Sucesso (200):**
```json
{
  "success": true,
  "message": "Calendário obtido com sucesso",
  "data": {
    "mes": 2,
    "ano": 2024,
    "calendario": {
      "15": [
        {
          "id": 1,
          "cliente_nome": "Empresa XYZ",
          "data_agendamento": "2024-02-15 14:30:00",
          "status": "confirmado"
        }
      ],
      "20": [...]
    }
  }
}
```

---

## 3. VENDAS - `/api/vendas`

Gerenciamento de vendas com gráficos e relatórios.

### GET /api/vendas
Lista vendas com paginação.

**Parâmetros Query:**
- `page` (int, default: 1)
- `limit` (int, default: 15)
- `status` (string) - pendente, confirmada, cancelada, entregue
- `data_inicio` (string, YYYY-MM-DD)
- `data_fim` (string, YYYY-MM-DD)
- `cliente_id` (int)
- `vendedor_id` (int)

---

### GET /api/vendas/:id
Obtém detalhes completos de uma venda com itens.

---

### POST /api/vendas
Cria nova venda.

**Body:**
```json
{
  "cliente_id": 5,
  "vendedor_id": 3,
  "items": [
    {
      "produto_id": 10,
      "quantidade": 5,
      "valor_unitario": 150.00
    }
  ],
  "desconto": 50.00,
  "observacoes": "Venda com frete incluído"
}
```

---

### PUT /api/vendas/:id
Atualiza venda.

**Body:**
```json
{
  "status": "entregue",
  "desconto": 75.00
}
```

---

### GET /api/vendas/graficos
Gráficos de vendas dos últimos 12 meses.

**Parâmetros Query:**
- `tipo` (string) - "mensal" ou "semanal" (default: mensal)
- `ano` (int, default: ano atual)

**Resposta Sucesso (200):**
```json
{
  "success": true,
  "message": "Gráficos obtidos com sucesso",
  "data": {
    "tipo": "mensal",
    "periodo": 2024,
    "graficos": [
      {
        "mes": 1,
        "ano": 2024,
        "total_vendas": 15,
        "valor_total": 45000.00
      }
    ],
    "totalizacao": {
      "total_vendas": 180,
      "valor_total": 540000.00,
      "ticket_medio": 3000.00
    }
  }
}
```

---

### GET /api/vendas/relatorio
Relatório detalhado de vendas.

**Parâmetros Query:**
- `data_inicio` (string, YYYY-MM-DD, default: primeiro dia do mês)
- `data_fim` (string, YYYY-MM-DD, default: data atual)
- `cliente_id` (int)
- `formato` (string) - "json" ou "csv" (default: json)

**Resposta Sucesso (200):**
```json
{
  "success": true,
  "message": "Relatório obtido com sucesso",
  "data": {
    "periodo": {
      "data_inicio": "2024-02-01",
      "data_fim": "2024-02-29"
    },
    "totalizacao": {
      "total_vendas": 45,
      "valor_total": 135000.00,
      "ticket_medio": 3000.00
    },
    "vendas": [...]
  }
}
```

---

## 4. COBRANÇAS - `/api/cobrancas`

Gerenciamento de cobranças, pagamentos e atrasos.

### GET /api/cobrancas
Lista cobranças.

**Parâmetros Query:**
- `page` (int, default: 1)
- `limit` (int, default: 15)
- `status` (string) - aberta, paga, cancelada
- `data_inicio` (string, YYYY-MM-DD)
- `data_fim` (string, YYYY-MM-DD)
- `cliente_id` (int)

**Resposta inclui `dias_atraso` e `vencida` se aplicável**

---

### GET /api/cobrancas/:id
Obtém cobrança específica.

**Resposta inclui:**
- `dias_atraso` - Dias em atraso (se vencida)
- `vencida` - Boolean indicando se está vencida

---

### POST /api/cobrancas
Cria nova cobrança.

**Body:**
```json
{
  "cliente_id": 5,
  "valor": 1500.00,
  "data_vencimento": "2024-03-15",
  "descricao": "Fatura #001",
  "venda_id": 10,
  "orcamento_id": 5
}
```

---

### PUT /api/cobrancas/:id
Atualiza cobrança.

---

### PUT /api/cobrancas/:id/pagar
Marca cobrança como paga.

**Body:**
```json
{
  "data_pagamento": "2024-02-15",
  "valor_pago": 1500.00,
  "desconto": 0.00,
  "observacoes": "Pagamento em dinheiro"
}
```

---

### GET /api/cobrancas/pendentes
Lista cobranças abertas pendentes.

**Parâmetros Query:**
- `page` (int)
- `limit` (int)
- `cliente_id` (int)

---

### GET /api/cobrancas/vencidas
Lista cobranças vencidas.

**Parâmetros Query:**
- `page` (int)
- `limit` (int)
- `cliente_id` (int)
- `dias_atraso` (int) - Filtrar por mínimo de dias em atraso

---

## 5. WHATSAPP - `/api/whatsapp`

Integração com WhatsApp para envio de mensagens, documentos e templates.

### GET /api/whatsapp/status
Verifica status da conexão com WhatsApp.

**Resposta Sucesso (200):**
```json
{
  "success": true,
  "message": "Status da conexão obtido com sucesso",
  "data": {
    "conectado": true,
    "numero_telefone": "5511999999999",
    "api_ativa": true,
    "ultima_sincronizacao": "2024-02-15 10:30:00",
    "mensagens_fila": 0
  }
}
```

---

### POST /api/whatsapp/send
Envia mensagem simples.

**Body:**
```json
{
  "telefone": "11999999999",
  "mensagem": "Olá! Sua mensagem aqui.",
  "cliente_id": 5,
  "tipo_mensagem": "texto"
}
```

**Resposta Sucesso (201):**
```json
{
  "success": true,
  "message": "Mensagem enviada com sucesso",
  "data": {
    "id_envio": 1,
    "telefone": "5511999999999",
    "status": "enviado",
    "mensagem_id": "msg_12345",
    "data_envio": "2024-02-15 10:30:00"
  }
}
```

---

### POST /api/whatsapp/send-document
Envia documento (PDF, imagem, etc).

**Body:**
```json
{
  "telefone": "11999999999",
  "documento": "/documentos/orcamento_1.pdf",
  "cliente_id": 5,
  "tipo_documento": "pdf",
  "descricao": "Seu orçamento em anexo"
}
```

---

### POST /api/whatsapp/send-template
Envia template pré-configurado.

**Body:**
```json
{
  "telefone": "11999999999",
  "template_id": 1,
  "cliente_id": 5,
  "variaveis": {
    "nome": "João Silva",
    "data": "15/02/2024",
    "valor": "R$ 1.500,00"
  }
}
```

---

## 6. INTELIGÊNCIA ARTIFICIAL - `/api/ia`

Integração com IA para assistência, melhoria de textos e geração de checklists.

### GET /api/ia/status
Verifica status da conexão com IA.

**Resposta Sucesso (200):**
```json
{
  "success": true,
  "message": "Status da IA obtido com sucesso",
  "data": {
    "conectado": true,
    "servico": "openai",
    "modelo": "gpt-3.5-turbo",
    "ativo": true,
    "uso_mes": {
      "requisicoes": 45,
      "tokens_usados": 128945,
      "limite_tokens": 1000000
    },
    "ultima_sincronizacao": "2024-02-15 10:30:00"
  }
}
```

---

### POST /api/ia/improve-text
Melhora e refina texto.

**Body:**
```json
{
  "texto": "Seu texto aqui para ser melhorado",
  "tipo": "email",
  "tom": "profissional"
}
```

**Tipos Válidos:** email, descricao, titulo, proposta, geral, relatorio

**Tons Válidos:** formal, informal, profissional, amigavel, tecnico

**Resposta Sucesso (200):**
```json
{
  "success": true,
  "message": "Texto melhorado com sucesso",
  "data": {
    "texto_original": "...",
    "texto_melhorado": "...",
    "tipo": "email",
    "tom": "profissional",
    "melhorias_aplicadas": {
      "clareza": true,
      "concisao": true,
      "impacto": true
    }
  }
}
```

---

### POST /api/ia/generate-checklist
Gera checklist baseado em contexto.

**Body:**
```json
{
  "contexto": "Implementação de novo sistema de vendas",
  "nivel": "intermediario",
  "idioma": "pt-br"
}
```

**Níveis Válidos:** basico, intermediario, avancado

**Resposta Sucesso (200):**
```json
{
  "success": true,
  "message": "Checklist gerado com sucesso",
  "data": {
    "contexto": "Implementação de novo sistema de vendas",
    "nivel": "intermediario",
    "total_itens": 8,
    "checklist": [
      {
        "item": "Item 1",
        "descricao": "Descrição detalhada"
      }
    ]
  }
}
```

---

### POST /api/ia/assistente
Assistente de IA geral para perguntas.

**Body:**
```json
{
  "pergunta": "Como melhorar a retenção de clientes?",
  "contexto": "Empresa de serviços de TI com 50 funcionários",
  "historico": [
    {"pergunta": "...", "resposta": "..."}
  ]
}
```

**Resposta Sucesso (200):**
```json
{
  "success": true,
  "message": "Resposta gerada com sucesso",
  "data": {
    "pergunta": "Como melhorar a retenção de clientes?",
    "contexto": "Empresa de serviços de TI com 50 funcionários",
    "resposta": "Resposta detalhada da IA...",
    "confianca": 0.92,
    "tempo_resposta_ms": 1230,
    "fontes": []
  }
}
```

---

## Códigos de Erro Comuns

- **400 Bad Request** - Dados inválidos ou obrigatórios faltando
- **401 Unauthorized** - Usuário não autenticado
- **404 Not Found** - Recurso não encontrado
- **405 Method Not Allowed** - Método HTTP não permitido
- **500 Internal Server Error** - Erro interno do servidor

---

## Autenticação

Todos os endpoints requerem autenticação via token Bearer. Incluir no header:

```
Authorization: Bearer seu_token_aqui
```

---

## Paginação

Para endpoints que retornam listas:
- `page` - Número da página (padrão: 1)
- `limit` - Itens por página (padrão: 15, máximo: 100)

Resposta inclui objeto `paginacao` com:
- `pagina_atual`
- `total_itens`
- `itens_por_pagina`
- `total_paginas`

---

## Notas Importantes

1. **Datas**: Use formato `YYYY-MM-DD` ou `YYYY-MM-DD HH:MM:SS`
2. **Telefones**: Use apenas números ou com código do país (ex: 5511999999999)
3. **Sanitização**: Todos os textos são sanitizados automaticamente
4. **Campos Monetários**: Use formato de número com ponto (ex: 1500.00)
5. **Limites de Requisição**: Máximo 100 itens por página nas listagens

