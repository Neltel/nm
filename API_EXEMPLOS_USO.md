# Exemplos de Uso dos Novos Endpoints da API

Este arquivo contém exemplos práticos de como usar os novos endpoints criados.

---

## 1. ORÇAMENTOS

### Criar um orçamento
```bash
curl -X POST http://localhost/api/orcamentos \
  -H "Authorization: Bearer seu_token" \
  -H "Content-Type: application/json" \
  -d '{
    "cliente_id": 5,
    "items": [
      {
        "descricao": "Desenvolvimento de App Mobile",
        "quantidade": 1,
        "valor_unitario": 5000.00
      },
      {
        "descricao": "Design UI/UX",
        "quantidade": 1,
        "valor_unitario": 2000.00
      }
    ],
    "desconto": 700,
    "validade_dias": 30,
    "condicoes_pagamento": "50% à vista, 50% na conclusão"
  }'
```

### Listar orçamentos com filtros
```bash
curl -X GET "http://localhost/api/orcamentos?status=pendente&page=1&limit=20" \
  -H "Authorization: Bearer seu_token"
```

### Alterar status de orçamento
```bash
curl -X PUT http://localhost/api/orcamentos/1/status \
  -H "Authorization: Bearer seu_token" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "aprovado"
  }'
```

### Enviar orçamento via WhatsApp
```bash
curl -X POST http://localhost/api/orcamentos/1/whatsapp \
  -H "Authorization: Bearer seu_token" \
  -H "Content-Type: application/json" \
  -d '{
    "mensagem": "Seu orçamento está pronto! Confira no link."
  }'
```

---

## 2. AGENDAMENTOS

### Criar agendamento
```bash
curl -X POST http://localhost/api/agendamentos \
  -H "Authorization: Bearer seu_token" \
  -H "Content-Type: application/json" \
  -d '{
    "cliente_id": 5,
    "tecnico_id": 3,
    "data_agendamento": "2024-02-20 14:30:00",
    "descricao": "Manutenção preventiva do servidor",
    "servico_id": 2,
    "observacoes": "Cliente é VIP, confirmar com antecedência"
  }'
```

### Verificar disponibilidade
```bash
curl -X GET "http://localhost/api/agendamentos/disponibilidade?data=2024-02-20&duracao=60&tecnico_id=3" \
  -H "Authorization: Bearer seu_token"
```

### Visualizar calendário
```bash
curl -X GET "http://localhost/api/agendamentos/calendario?mes=2&ano=2024&tecnico_id=3" \
  -H "Authorization: Bearer seu_token"
```

### Confirmar agendamento
```bash
curl -X PUT http://localhost/api/agendamentos/1 \
  -H "Authorization: Bearer seu_token" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "confirmado"
  }'
```

---

## 3. VENDAS

### Criar venda
```bash
curl -X POST http://localhost/api/vendas \
  -H "Authorization: Bearer seu_token" \
  -H "Content-Type: application/json" \
  -d '{
    "cliente_id": 5,
    "items": [
      {
        "produto_id": 10,
        "quantidade": 5,
        "valor_unitario": 150.00
      },
      {
        "produto_id": 15,
        "quantidade": 2,
        "valor_unitario": 300.00
      }
    ],
    "desconto": 50,
    "observacoes": "Venda com frete incluído via Sedex"
  }'
```

### Gráficos de vendas
```bash
curl -X GET "http://localhost/api/vendas/graficos?tipo=mensal&ano=2024" \
  -H "Authorization: Bearer seu_token"
```

### Relatório de vendas
```bash
curl -X GET "http://localhost/api/vendas/relatorio?data_inicio=2024-01-01&data_fim=2024-02-15&formato=json" \
  -H "Authorization: Bearer seu_token"
```

### Atualizar status de venda
```bash
curl -X PUT http://localhost/api/vendas/1 \
  -H "Authorization: Bearer seu_token" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "entregue"
  }'
```

---

## 4. COBRANÇAS

### Criar cobrança
```bash
curl -X POST http://localhost/api/cobrancas \
  -H "Authorization: Bearer seu_token" \
  -H "Content-Type: application/json" \
  -d '{
    "cliente_id": 5,
    "valor": 1500.00,
    "data_vencimento": "2024-03-15",
    "descricao": "Fatura referente à Venda #001",
    "venda_id": 10
  }'
```

### Listar cobranças vencidas
```bash
curl -X GET "http://localhost/api/cobrancas/vencidas?page=1&limit=20" \
  -H "Authorization: Bearer seu_token"
```

### Registrar pagamento
```bash
curl -X PUT http://localhost/api/cobrancas/1/pagar \
  -H "Authorization: Bearer seu_token" \
  -H "Content-Type: application/json" \
  -d '{
    "data_pagamento": "2024-02-15",
    "valor_pago": 1500.00,
    "desconto": 0,
    "observacoes": "Pagamento recebido em dinheiro"
  }'
```

### Listar pendências
```bash
curl -X GET "http://localhost/api/cobrancas/pendentes?cliente_id=5" \
  -H "Authorization: Bearer seu_token"
```

---

## 5. WHATSAPP

### Enviar mensagem simples
```bash
curl -X POST http://localhost/api/whatsapp/send \
  -H "Authorization: Bearer seu_token" \
  -H "Content-Type: application/json" \
  -d '{
    "telefone": "11999999999",
    "mensagem": "Olá! Seu pedido #001 foi confirmado.",
    "cliente_id": 5
  }'
```

### Enviar documento
```bash
curl -X POST http://localhost/api/whatsapp/send-document \
  -H "Authorization: Bearer seu_token" \
  -H "Content-Type: application/json" \
  -d '{
    "telefone": "11999999999",
    "documento": "/pdfs/fatura_001.pdf",
    "cliente_id": 5,
    "tipo_documento": "pdf",
    "descricao": "Sua fatura está em anexo"
  }'
```

### Enviar template
```bash
curl -X POST http://localhost/api/whatsapp/send-template \
  -H "Authorization: Bearer seu_token" \
  -H "Content-Type: application/json" \
  -d '{
    "telefone": "11999999999",
    "template_id": 1,
    "cliente_id": 5,
    "variaveis": {
      "nome": "João Silva",
      "data": "15/02/2024",
      "valor": "R$ 1.500,00"
    }
  }'
```

### Verificar status
```bash
curl -X GET http://localhost/api/whatsapp/status \
  -H "Authorization: Bearer seu_token"
```

---

## 6. INTELIGÊNCIA ARTIFICIAL

### Melhorar texto
```bash
curl -X POST http://localhost/api/ia/improve-text \
  -H "Authorization: Bearer seu_token" \
  -H "Content-Type: application/json" \
  -d '{
    "texto": "Oi, recebi seu email mas não entendi direito oq voce quis dizer. Pode explicar melhor?",
    "tipo": "email",
    "tom": "profissional"
  }'
```

### Gerar checklist
```bash
curl -X POST http://localhost/api/ia/generate-checklist \
  -H "Authorization: Bearer seu_token" \
  -H "Content-Type: application/json" \
  -d '{
    "contexto": "Implementação de novo sistema ERP para gestão de vendas",
    "nivel": "intermediario",
    "idioma": "pt-br"
  }'
```

### Usar assistente
```bash
curl -X POST http://localhost/api/ia/assistente \
  -H "Authorization: Bearer seu_token" \
  -H "Content-Type: application/json" \
  -d '{
    "pergunta": "Como melhorar a retenção de clientes na nossa empresa?",
    "contexto": "Empresa de consultoria em TI com 25 colaboradores",
    "historico": []
  }'
```

### Verificar status da IA
```bash
curl -X GET http://localhost/api/ia/status \
  -H "Authorization: Bearer seu_token"
```

---

## Exemplos em JavaScript/Fetch API

### Criar orçamento
```javascript
const criarOrcamento = async () => {
  const response = await fetch('/api/orcamentos', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      cliente_id: 5,
      items: [
        {
          descricao: 'Serviço A',
          quantidade: 1,
          valor_unitario: 1000
        }
      ],
      desconto: 100
    })
  });
  
  const data = await response.json();
  console.log(data);
};
```

### Listar vendas com filtros
```javascript
const listarVendas = async (status, dataInicio, dataFim) => {
  const params = new URLSearchParams({
    status,
    data_inicio: dataInicio,
    data_fim: dataFim,
    page: 1,
    limit: 20
  });
  
  const response = await fetch(`/api/vendas?${params}`, {
    headers: {
      'Authorization': `Bearer ${token}`
    }
  });
  
  const data = await response.json();
  return data.data.vendas;
};
```

### Verificar disponibilidade
```javascript
const verificarDisponibilidade = async (data, duracao) => {
  const params = new URLSearchParams({
    data,
    duracao,
    tecnico_id: 3
  });
  
  const response = await fetch(`/api/agendamentos/disponibilidade?${params}`, {
    headers: {
      'Authorization': `Bearer ${token}`
    }
  });
  
  const data = await response.json();
  return data.data.horarios.filter(h => h.disponivel);
};
```

### Melhorar texto com IA
```javascript
const melhorarTexto = async (texto) => {
  const response = await fetch('/api/ia/improve-text', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      texto,
      tipo: 'email',
      tom: 'profissional'
    })
  });
  
  const data = await response.json();
  return data.data.texto_melhorado;
};
```

---

## Tratamento de Erros

### Exemplo de tratamento de erro
```javascript
const criarVenda = async (vendaData) => {
  try {
    const response = await fetch('/api/vendas', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(vendaData)
    });
    
    const data = await response.json();
    
    if (!data.success) {
      console.error('Erro:', data.message);
      // Exibir mensagem de erro para o usuário
      return null;
    }
    
    console.log('Sucesso:', data.data);
    return data.data;
    
  } catch (error) {
    console.error('Erro na requisição:', error);
  }
};
```

---

## Notas Importantes

1. **Sempre incluir o token de autenticação** no header Authorization
2. **Validar dados do lado do cliente** antes de enviar para economizar requisições
3. **Tratar erros adequadamente** e fornecer feedback ao usuário
4. **Usar paginação** ao listar grandes volumes de dados
5. **Cache de dados** quando apropriado para melhorar performance
6. **Respeitar rate limits** se houver implementados
7. **Testar endpoints** em ambiente de desenvolvimento antes de produção

