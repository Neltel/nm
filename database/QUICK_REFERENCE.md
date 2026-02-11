# Referência Rápida - Schema NM Refrigeração

## 📋 Lista de Tabelas (30 tabelas)

### Grupo 1: Autenticação e Usuários (1 tabela)
| # | Tabela | Descrição |
|---|--------|-----------|
| 1 | `usuarios` | Usuários do sistema com autenticação |

### Grupo 2: Gestão de Clientes (1 tabela)
| # | Tabela | Descrição |
|---|--------|-----------|
| 2 | `clientes` | Dados de clientes (PF e PJ) |

### Grupo 3: Produtos e Categorias (2 tabelas)
| # | Tabela | Descrição |
|---|--------|-----------|
| 3 | `categorias_produtos` | Categorias de produtos |
| 4 | `produtos` | Catálogo de produtos |

### Grupo 4: Serviços (1 tabela)
| # | Tabela | Descrição |
|---|--------|-----------|
| 5 | `servicos` | Serviços oferecidos |

### Grupo 5: Pedidos e Itens (3 tabelas)
| # | Tabela | Descrição |
|---|--------|-----------|
| 6 | `pedidos` | Pedidos de clientes |
| 7 | `pedidos_produtos` | Produtos em pedidos (relação N-N) |
| 8 | `pedidos_servicos` | Serviços em pedidos (relação N-N) |

### Grupo 6: Orçamentos (2 tabelas)
| # | Tabela | Descrição |
|---|--------|-----------|
| 9 | `orcamentos` | Orçamentos dos clientes |
| 10 | `orcamentos_itens` | Itens de orçamentos |

### Grupo 7: Agendamentos (1 tabela)
| # | Tabela | Descrição |
|---|--------|-----------|
| 11 | `agendamentos` | Agendamentos de serviços |

### Grupo 8: Vendas (1 tabela)
| # | Tabela | Descrição |
|---|--------|-----------|
| 12 | `vendas` | Vendas finalizadas |

### Grupo 9: Cobranças (1 tabela)
| # | Tabela | Descrição |
|---|--------|-----------|
| 13 | `cobrancas` | Cobranças e pagamentos |

### Grupo 10: Garantias (1 tabela)
| # | Tabela | Descrição |
|---|--------|-----------|
| 14 | `garantias` | Garantias de serviços |

### Grupo 11: Manutenção Preventiva (2 tabelas)
| # | Tabela | Descrição |
|---|--------|-----------|
| 15 | `preventivas` | Manutenção preventiva |
| 16 | `preventivas_checklists` | Itens de checklist preventivo |

### Grupo 12: Histórico (1 tabela)
| # | Tabela | Descrição |
|---|--------|-----------|
| 17 | `historico` | Histórico de atividades |

### Grupo 13: Relatórios (2 tabelas)
| # | Tabela | Descrição |
|---|--------|-----------|
| 18 | `relatorios` | Relatórios de serviços |
| 19 | `relatorios_fotos` | Fotos dos relatórios |

### Grupo 14: Financeiro Geral (1 tabela)
| # | Tabela | Descrição |
|---|--------|-----------|
| 20 | `financeiro` | Transações financeiras |

### Grupo 15: Programa Manutenção Preventiva (4 tabelas)
| # | Tabela | Descrição |
|---|--------|-----------|
| 21 | `pmp_contratos` | Contratos PMP |
| 22 | `pmp_equipamentos` | Equipamentos no PMP |
| 23 | `pmp_checklists` | Checklists PMP executados |
| 24 | `pmp_checklist_itens` | Itens dos checklists PMP |

### Grupo 16: Configurações (2 tabelas)
| # | Tabela | Descrição |
|---|--------|-----------|
| 25 | `configuracoes` | Configurações do sistema |
| 26 | `tabelas_precos` | Tabelas de preços por serviço |

### Grupo 17: Anexos (1 tabela)
| # | Tabela | Descrição |
|---|--------|-----------|
| 27 | `anexos` | Gerenciamento de arquivos |

### Grupo 18: Auditoria (1 tabela)
| # | Tabela | Descrição |
|---|--------|-----------|
| 28 | `logs_sistema` | Logs de auditoria |

### Grupo 19: Notificações (1 tabela)
| # | Tabela | Descrição |
|---|--------|-----------|
| 29 | `notificacoes` | Notificações do sistema |

### Grupo 20: Comunicação (1 tabela)
| # | Tabela | Descrição |
|---|--------|-----------|
| 30 | `mensagens_whatsapp` | Mensagens WhatsApp |

---

## 🔑 Tipos de Dados Comuns

```sql
-- IDs (chave primária)
id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY

-- Valores monetários
DECIMAL(10, 2)

-- Percentuais
DECIMAL(5, 2)

-- Status (enumerados)
ENUM('value1', 'value2', 'value3')

-- Descrições longas
LONGTEXT

-- Textos curtos
VARCHAR(200)

-- Datas
DATE
TIMESTAMP
TIME

-- Booleanos
BOOLEAN (0 ou 1)
```

---

## 📊 Principais Campos

### Usuário (para filtrar)
```sql
SELECT * FROM usuarios WHERE tipo = 'tecnico' AND ativo = TRUE;
```

### Cliente (buscar por CPF/CNPJ)
```sql
SELECT * FROM clientes WHERE cpf_cnpj = '12345678901234';
```

### Pedido (com status)
```sql
SELECT * FROM pedidos WHERE cliente_id = 1 AND status = 'confirmado';
```

### Venda (com data)
```sql
SELECT * FROM vendas 
WHERE DATE(data_venda) = CURDATE() 
AND status_pagamento IN ('pendente', 'atrasado');
```

### Cobrança (vencidas)
```sql
SELECT * FROM cobrancas 
WHERE data_vencimento < CURDATE() 
AND status = 'aberta';
```

### Agendamento (próximos dias)
```sql
SELECT * FROM agendamentos 
WHERE data_agendamento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY);
```

---

## 🔗 Relacionamentos Principais

### 1. Cliente → Pedidos
```
clientes (1) ──→ (N) pedidos
```

### 2. Pedido → Produtos e Serviços
```
pedidos (1) ──→ (N) pedidos_produtos ──→ (N) produtos
pedidos (1) ──→ (N) pedidos_servicos ──→ (N) servicos
```

### 3. Pedido → Venda
```
pedidos (1) ──→ (1) vendas ──→ (N) cobrancas
```

### 4. Cliente → Orçamento
```
clientes (1) ──→ (N) orcamentos
orcamentos (1) ──→ (N) orcamentos_itens
```

### 5. Cliente → Agendamento
```
clientes (1) ──→ (N) agendamentos ──→ (1) servicos
```

### 6. Cliente → PMP
```
clientes (1) ──→ (N) pmp_contratos
pmp_contratos (1) ──→ (N) pmp_equipamentos
pmp_contratos (1) ──→ (N) pmp_checklists
```

---

## 📈 Principais Agregações

### Total de Vendas do Dia
```sql
SELECT DATE(data_venda) as data,
       SUM(valor_bruto) as total,
       COUNT(*) as qtd_vendas,
       AVG(valor_lucro) as lucro_medio
FROM vendas
WHERE DATE(data_venda) = CURDATE()
GROUP BY DATE(data_venda);
```

### Clientes com Inadimplência
```sql
SELECT c.id, c.nome, COUNT(cb.id) as pendencias, SUM(cb.valor) as total_devido
FROM clientes c
JOIN cobrancas cb ON c.id = cb.cliente_id
WHERE cb.status IN ('aberta', 'atrasada')
GROUP BY c.id, c.nome
HAVING pendencias > 0
ORDER BY total_devido DESC;
```

### Produtos com Baixo Estoque
```sql
SELECT id, nome, estoque_atual, estoque_minimo
FROM produtos
WHERE estoque_atual <= estoque_minimo
AND ativo = TRUE;
```

### Receita por Cliente
```sql
SELECT c.id, c.nome, COUNT(v.id) as vendas, SUM(v.valor_bruto) as total_vendido
FROM clientes c
LEFT JOIN vendas v ON c.id = v.cliente_id
GROUP BY c.id, c.nome
ORDER BY total_vendido DESC
LIMIT 10;
```

### Próximos Agendamentos
```sql
SELECT a.*, c.nome as cliente_nome, s.nome as servico_nome
FROM agendamentos a
JOIN clientes c ON a.cliente_id = c.id
LEFT JOIN servicos s ON a.servico_id = s.id
WHERE a.data_agendamento >= CURDATE()
AND a.status IN ('agendado', 'em_progresso')
ORDER BY a.data_agendamento, a.hora_inicio;
```

---

## 🛡️ Campos de Segurança

### Auditoria
- `logs_sistema`: Rastreia CREATE, UPDATE, DELETE
- Armazena: usuario_id, acao, dados_anteriores, dados_novos, ip, user_agent

### Integridade
- Foreign Keys: Previnem orfãos de dados
- Cascata: Deleta dependentes automaticamente
- Unique: CPF, email, CNPJ não duplicados

### Autenticação
- `usuarios.senha`: Deve ser hash bcrypt
- `usuarios.ativo`: Controla acesso
- `usuarios.tipo`: Define permissões

---

## 📅 Campos Temporal

Todas as tabelas possuem `criado_em`:
```sql
`criado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
```

Tabelas críticas também possuem `atualizado_em`:
```sql
`atualizado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
```

---

## 💰 Campos Financeiros

### Valores Armazenados
- `preco_custo`: Custo do produto/serviço
- `preco_venda`: Preço cobrado
- `valor_bruto`: Total da venda
- `valor_custo`: Custo total
- `valor_lucro`: Diferença (GERADO AUTOMATICAMENTE)

### Percentuais
- `margem_lucro`: % de lucro esperado
- Formato: DECIMAL(5, 2) = até 999.99%

---

## 📞 Campos de Comunicação

### Contato
- `usuarios.email`, `usuarios.telefone`
- `clientes.email`, `clientes.celular`, `clientes.telefone`
- `mensagens_whatsapp.destinatario`

### WhatsApp
- `tipo`: texto/imagem/documento/link
- `status`: pendente/enviado/entregue/lido/erro
- `erro`: Mensagem de erro se houver

---

## 🗂️ Estrutura de Pastas

```
/public_html/database/
├── schema.sql                    # Arquivo principal com todas as tabelas
└── SCHEMA_DOCUMENTATION.md      # Documentação completa (este arquivo)
└── QUICK_REFERENCE.md          # Referência rápida (você está aqui)
```

---

## ✅ Checklist de Implementação

### Criar Banco de Dados
- [ ] Executar `schema.sql` em um servidor MySQL 5.7+
- [ ] Verificar charset utf8mb4 ativo
- [ ] Confirmar todas as 30 tabelas criadas

### Adicionar Dados Iniciais
- [ ] Inserir usuário admin
- [ ] Adicionar categorias de produtos
- [ ] Configurar tabelas de preço
- [ ] Adicionar serviços iniciais

### Conectar Application
- [ ] Criar arquivo de configuração do BD
- [ ] Testar conexão
- [ ] Implementar pooling de conexões
- [ ] Configurar backup automático

### Segurança
- [ ] Hash de senhas com bcrypt
- [ ] Parametrização de queries (prepared statements)
- [ ] Validação de entrada
- [ ] Auditoria habilitada

---

## 🔄 Fluxo de Dados Típico

```
1. Cliente Novo
   ├─ INSERT INTO clientes
   └─ INSERT INTO historico (tipo: 'contato')

2. Orçamento
   ├─ INSERT INTO orcamentos
   ├─ INSERT INTO orcamentos_itens (múltiplos)
   └─ INSERT INTO historico (tipo: 'orcamento')

3. Pedido Confirmado
   ├─ INSERT INTO pedidos
   ├─ INSERT INTO pedidos_produtos (múltiplos)
   ├─ INSERT INTO pedidos_servicos (múltiplos)
   └─ INSERT INTO historico (tipo: 'venda')

4. Venda Realizada
   ├─ INSERT INTO vendas
   ├─ INSERT INTO cobrancas
   ├─ UPDATE produtos (estoque)
   └─ INSERT INTO financeiro (receita)

5. Agendamento
   ├─ INSERT INTO agendamentos
   └─ INSERT INTO historico (tipo: 'visita')

6. Serviço Executado
   ├─ INSERT INTO relatorios
   ├─ INSERT INTO relatorios_fotos
   ├─ INSERT INTO historico
   └─ UPDATE agendamentos (status: concluido)
```

---

## 📊 Estatísticas do Schema

| Métrica | Valor |
|---------|-------|
| Total de Tabelas | 30 |
| Total de Colunas | 400+ |
| Total de Índices | 40+ |
| Foreign Keys | 25+ |
| Enumerações | 15+ |
| Campos Calculados | 3 (GENERATED) |
| Timestamps | 70+ |
| Campos Únicos | 15+ |

---

## 🚀 Performance Tips

### 1. Índices Mais Usados
- `usuarios.email`
- `clientes.cpf_cnpj`
- `pedidos.cliente_id`, `pedidos.status`, `pedidos.data_pedido`
- `vendas.data_venda`, `vendas.status_pagamento`

### 2. Queries Comuns
- Listar pedidos: INDEX (cliente_id, data_pedido DESC)
- Filtrar por status: INDEX (status, criado_em DESC)
- Relatórios de data: INDEX (tipo, data_transacao)

### 3. Manutenção
```sql
-- Analisar performance
ANALYZE TABLE usuarios, clientes, pedidos;

-- Otimizar tabelas
OPTIMIZE TABLE usuarios, clientes, pedidos;

-- Verificar fragmentação
CHECK TABLE usuarios;
```

---

## 📞 Suporte

Para dúvidas sobre:
- **Estrutura**: Consulte SCHEMA_DOCUMENTATION.md
- **Queries**: Consulte exemplos de agregações acima
- **Performance**: Verifique índices em cada tabela
- **Relacionamentos**: Veja seção de Foreign Keys

---

**Última Atualização:** 2024
**Versão:** 1.0
**Charset:** utf8mb4
**Engine:** InnoDB
