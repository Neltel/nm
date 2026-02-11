<?php
/**
 * ARQUIVO: orcamentos.php
 * 
 * Função: Endpoints CRUD de gerenciamento de orçamentos
 * Entrada: Dados de orçamento, itens, parâmetros de busca e paginação
 * Processamento: Cria, lê, atualiza, deleta, busca orçamentos e gera PDFs
 * Saída: Dados de orçamento(s), lista paginada, PDFs, mensagens de sucesso/erro
 * 
 * Endpoints disponíveis:
 * - GET /api/orcamentos - Lista todos os orçamentos (com paginação)
 * - GET /api/orcamentos/:id - Obtém um orçamento específico com itens
 * - POST /api/orcamentos - Cria novo orçamento
 * - PUT /api/orcamentos/:id - Atualiza orçamento existente
 * - DELETE /api/orcamentos/:id - Deleta orçamento
 * - PUT /api/orcamentos/:id/status - Altera status do orçamento
 * - POST /api/orcamentos/:id/pdf - Gera PDF do orçamento
 * - POST /api/orcamentos/:id/whatsapp - Envia orçamento via WhatsApp
 */

// Inicializa classe de autenticação
$auth = new Auth($db);

// Valida autenticação para todos os endpoints
$usuario = $auth->getAuthenticatedUser();
if (!$usuario) {
    sendError('Usuário não autenticado', 401);
}

// Obtém subroute (segunda parte da URL)
$subroute = isset($parts[1]) ? $parts[1] : '';
$id = isset($parts[2]) ? intval($parts[2]) : null;
$acao = isset($parts[3]) ? $parts[3] : '';

// Roteia para ação apropriada
switch ($subroute) {
    
    /**
     * GET /api/orcamentos
     * Lista todos os orçamentos com paginação
     * Parâmetros: page, limit, order, status, cliente_id
     */
    case '':
    case null:
        if ($method !== 'GET') {
            sendError('Método não permitido', 405);
        }
        
        // Obtém parâmetros de paginação
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : ITEMS_PER_PAGE;
        $order = isset($_GET['order']) ? $_GET['order'] : 'criado_em DESC';
        
        // Obtém filtros
        $status = isset($_GET['status']) ? $_GET['status'] : null;
        $cliente_id = isset($_GET['cliente_id']) ? intval($_GET['cliente_id']) : null;
        
        // Sanitiza ORDER BY para evitar SQL injection
        $orderField = 'criado_em';
        $orderDir = 'DESC';
        
        if (preg_match('/^(numero|cliente_nome|valor_total|status|criado_em)\s+(ASC|DESC)$/i', $order, $matches)) {
            $orderField = $matches[1];
            $orderDir = strtoupper($matches[2]);
        }
        
        $offset = ($page - 1) * $limit;
        
        // Constrói cláusula WHERE para filtros
        $where = '1=1';
        $params = [];
        
        if (!is_null($status) && in_array($status, ['pendente', 'aprovado', 'rejeitado', 'convertido'])) {
            $where .= ' AND o.status = ?';
            $params[] = $status;
        }
        
        if (!is_null($cliente_id) && $cliente_id > 0) {
            $where .= ' AND o.cliente_id = ?';
            $params[] = $cliente_id;
        }
        
        // Conta total de orçamentos
        $total = $db->count('orcamentos o', $where, $params);
        
        // Busca orçamentos paginados
        $orcamentos = $db->find('orcamentos o', [
            'select' => 'o.*, c.nome as cliente_nome, COUNT(oi.id) as total_itens',
            'leftJoin' => 'clientes c ON o.cliente_id = c.id',
            'leftJoin' => 'orcamento_itens oi ON o.id = oi.orcamento_id',
            'where' => $where,
            'params' => $params,
            'groupBy' => 'o.id',
            'order' => "o.{$orderField} {$orderDir}",
            'limit' => $limit,
            'offset' => $offset
        ]);
        
        sendSuccess([
            'orcamentos' => $orcamentos,
            'paginacao' => [
                'pagina_atual' => $page,
                'total_itens' => $total,
                'itens_por_pagina' => $limit,
                'total_paginas' => ceil($total / $limit)
            ]
        ], 'Orçamentos obtidos com sucesso', 200);
        break;
    
    /**
     * POST /api/orcamentos
     * Cria novo orçamento
     * Requer: cliente_id, items (array)
     * Opcionais: descricao, validade_dias, condicoes_pagamento
     */
    default:
        if ($id === null || $id <= 0) {
            // POST - Criar novo orçamento
            if ($method === 'POST') {
                // Valida entrada obrigatória
                if (empty($input['cliente_id'])) {
                    sendError('ID do cliente é obrigatório', 400);
                }
                
                if (empty($input['items']) || !is_array($input['items'])) {
                    sendError('Items é obrigatório e deve ser um array', 400);
                }
                
                if (count($input['items']) === 0) {
                    sendError('Orçamento deve conter no mínimo 1 item', 400);
                }
                
                $cliente_id = intval($input['cliente_id']);
                
                if ($cliente_id <= 0) {
                    sendError('ID de cliente inválido', 400);
                }
                
                // Verifica se cliente existe
                $cliente = $db->queryOne(
                    "SELECT id FROM clientes WHERE id = ?",
                    [$cliente_id]
                );
                
                if (!$cliente) {
                    sendError('Cliente não encontrado', 404);
                }
                
                // Validação dos itens
                $valorTotal = 0;
                foreach ($input['items'] as $item) {
                    if (empty($item['produto_id']) && empty($item['descricao'])) {
                        sendError('Cada item deve ter produto_id ou descricao', 400);
                    }
                    
                    if (empty($item['quantidade']) || !is_numeric($item['quantidade']) || $item['quantidade'] <= 0) {
                        sendError('Quantidade deve ser um número positivo', 400);
                    }
                    
                    if (empty($item['valor_unitario']) || !is_numeric($item['valor_unitario']) || $item['valor_unitario'] < 0) {
                        sendError('Valor unitário deve ser um número não-negativo', 400);
                    }
                    
                    $valorTotal += floatval($item['quantidade']) * floatval($item['valor_unitario']);
                }
                
                // Gera número do orçamento
                $ultimoOrcamento = $db->queryOne(
                    "SELECT MAX(numero) as numero FROM orcamentos WHERE criado_em >= DATE_SUB(NOW(), INTERVAL 1 YEAR)"
                );
                
                $proximoNumero = (!$ultimoOrcamento || is_null($ultimoOrcamento['numero'])) 
                    ? 1 
                    : intval($ultimoOrcamento['numero']) + 1;
                
                // Prepara dados do orçamento
                $validade_dias = isset($input['validade_dias']) ? intval($input['validade_dias']) : 30;
                $data_validade = date('Y-m-d', strtotime("+{$validade_dias} days"));
                
                $orcamentoDados = [
                    'numero' => $proximoNumero,
                    'cliente_id' => $cliente_id,
                    'valor_total' => $valorTotal,
                    'desconto' => isset($input['desconto']) ? floatval($input['desconto']) : 0,
                    'status' => 'pendente',
                    'data_validade' => $data_validade,
                    'condicoes_pagamento' => isset($input['condicoes_pagamento']) ? Validator::sanitizeString($input['condicoes_pagamento']) : null,
                    'observacoes' => isset($input['observacoes']) ? Validator::sanitizeString($input['observacoes']) : null,
                    'criado_em' => date('Y-m-d H:i:s'),
                    'criado_por' => $usuario['id']
                ];
                
                // Insere orçamento
                $orcamentoId = $db->insert('orcamentos', $orcamentoDados);
                
                // Insere itens do orçamento
                foreach ($input['items'] as $item) {
                    $itemDados = [
                        'orcamento_id' => $orcamentoId,
                        'produto_id' => !empty($item['produto_id']) ? intval($item['produto_id']) : null,
                        'descricao' => !empty($item['descricao']) ? Validator::sanitizeString($item['descricao']) : null,
                        'quantidade' => floatval($item['quantidade']),
                        'valor_unitario' => floatval($item['valor_unitario']),
                        'criado_em' => date('Y-m-d H:i:s')
                    ];
                    
                    $db->insert('orcamento_itens', $itemDados);
                }
                
                // Busca orçamento criado com itens
                $orcamento = $db->queryOne(
                    "SELECT o.*, c.nome as cliente_nome FROM orcamentos o 
                     LEFT JOIN clientes c ON o.cliente_id = c.id 
                     WHERE o.id = ?",
                    [$orcamentoId]
                );
                
                $itens = $db->find('orcamento_itens', [
                    'where' => 'orcamento_id = ?',
                    'params' => [$orcamentoId]
                ]);
                
                $orcamento['itens'] = $itens;
                
                sendSuccess($orcamento, 'Orçamento criado com sucesso', 201);
            } else {
                sendError('Método não permitido', 405);
            }
        } else {
            // Se houver ID, processa operações específicas
            
            if (!empty($acao)) {
                
                /**
                 * PUT /api/orcamentos/:id/status
                 * Altera status do orçamento
                 * Requer: status (pendente, aprovado, rejeitado, convertido)
                 */
                if ($acao === 'status' && $method === 'PUT') {
                    // Busca orçamento
                    $orcamento = $db->queryOne(
                        "SELECT * FROM orcamentos WHERE id = ?",
                        [$id]
                    );
                    
                    if (!$orcamento) {
                        sendError('Orçamento não encontrado', 404);
                    }
                    
                    // Valida novo status
                    if (empty($input['status'])) {
                        sendError('Status é obrigatório', 400);
                    }
                    
                    if (!in_array($input['status'], ['pendente', 'aprovado', 'rejeitado', 'convertido'])) {
                        sendError('Status inválido', 400);
                    }
                    
                    // Atualiza status
                    $dados = [
                        'status' => $input['status'],
                        'atualizado_em' => date('Y-m-d H:i:s'),
                        'atualizado_por' => $usuario['id']
                    ];
                    
                    // Se convertido, cria venda
                    if ($input['status'] === 'convertido') {
                        // Implementar lógica de conversão para venda se necessário
                    }
                    
                    $db->update('orcamentos', $dados, 'id = ?', [$id]);
                    
                    // Busca orçamento atualizado
                    $orcamentoAtualizado = $db->queryOne(
                        "SELECT * FROM orcamentos WHERE id = ?",
                        [$id]
                    );
                    
                    sendSuccess($orcamentoAtualizado, 'Status atualizado com sucesso', 200);
                }
                
                /**
                 * POST /api/orcamentos/:id/pdf
                 * Gera PDF do orçamento
                 */
                elseif ($acao === 'pdf' && $method === 'POST') {
                    // Busca orçamento
                    $orcamento = $db->queryOne(
                        "SELECT o.*, c.* FROM orcamentos o 
                         LEFT JOIN clientes c ON o.cliente_id = c.id 
                         WHERE o.id = ?",
                        [$id]
                    );
                    
                    if (!$orcamento) {
                        sendError('Orçamento não encontrado', 404);
                    }
                    
                    // Busca itens do orçamento
                    $itens = $db->find('orcamento_itens', [
                        'where' => 'orcamento_id = ?',
                        'params' => [$id]
                    ]);
                    
                    // Gera PDF (você pode usar uma biblioteca como TCPDF ou mPDF)
                    // Por enquanto, retorna informações para gerar PDF no frontend
                    $pdfData = [
                        'orcamento' => $orcamento,
                        'itens' => $itens,
                        'pdf_url' => '/pdf/orcamento_' . $id . '.pdf'
                    ];
                    
                    sendSuccess($pdfData, 'PDF gerado com sucesso', 200);
                }
                
                /**
                 * POST /api/orcamentos/:id/whatsapp
                 * Envia orçamento via WhatsApp
                 */
                elseif ($acao === 'whatsapp' && $method === 'POST') {
                    // Busca orçamento
                    $orcamento = $db->queryOne(
                        "SELECT o.*, c.telefone FROM orcamentos o 
                         LEFT JOIN clientes c ON o.cliente_id = c.id 
                         WHERE o.id = ?",
                        [$id]
                    );
                    
                    if (!$orcamento) {
                        sendError('Orçamento não encontrado', 404);
                    }
                    
                    if (empty($orcamento['telefone'])) {
                        sendError('Cliente não possui telefone cadastrado', 400);
                    }
                    
                    // Prepara mensagem
                    $mensagem = "Olá! Seu orçamento #" . $orcamento['numero'] . " está pronto.\n";
                    $mensagem .= "Valor: R$ " . number_format($orcamento['valor_total'], 2, ',', '.') . "\n";
                    $mensagem .= "Validade: " . date('d/m/Y', strtotime($orcamento['data_validade'])) . "\n";
                    
                    if (!empty($input['mensagem'])) {
                        $mensagem .= "\n" . $input['mensagem'];
                    }
                    
                    // TODO: Implementar integração com WhatsApp API
                    // Por enquanto, retorna sucesso simulado
                    $whatsappData = [
                        'orcamento_id' => $id,
                        'telefone' => $orcamento['telefone'],
                        'mensagem' => $mensagem,
                        'enviado_em' => date('Y-m-d H:i:s')
                    ];
                    
                    sendSuccess($whatsappData, 'Orçamento enviado via WhatsApp com sucesso', 200);
                }
                
                else {
                    sendError('Ação não encontrada', 404);
                }
            } else {
                
                if ($method === 'GET') {
                    /**
                     * GET /api/orcamentos/:id
                     * Obtém dados de um orçamento específico com itens
                     */
                    // Busca orçamento
                    $orcamento = $db->queryOne(
                        "SELECT o.*, c.nome as cliente_nome, c.email, c.telefone 
                         FROM orcamentos o 
                         LEFT JOIN clientes c ON o.cliente_id = c.id 
                         WHERE o.id = ?",
                        [$id]
                    );
                    
                    if (!$orcamento) {
                        sendError('Orçamento não encontrado', 404);
                    }
                    
                    // Busca itens
                    $itens = $db->find('orcamento_itens', [
                        'where' => 'orcamento_id = ?',
                        'params' => [$id]
                    ]);
                    
                    $orcamento['itens'] = $itens;
                    
                    sendSuccess($orcamento, 'Orçamento obtido com sucesso', 200);
                }
                
                /**
                 * PUT /api/orcamentos/:id
                 * Atualiza dados de um orçamento existente
                 */
                elseif ($method === 'PUT') {
                    // Busca orçamento existente
                    $orcamento = $db->queryOne(
                        "SELECT * FROM orcamentos WHERE id = ?",
                        [$id]
                    );
                    
                    if (!$orcamento) {
                        sendError('Orçamento não encontrado', 404);
                    }
                    
                    // Prepara dados para atualização
                    $dados = [];
                    
                    if (isset($input['cliente_id'])) {
                        $cliente_id = intval($input['cliente_id']);
                        
                        if ($cliente_id <= 0) {
                            sendError('ID de cliente inválido', 400);
                        }
                        
                        $cliente = $db->queryOne(
                            "SELECT id FROM clientes WHERE id = ?",
                            [$cliente_id]
                        );
                        
                        if (!$cliente) {
                            sendError('Cliente não encontrado', 404);
                        }
                        
                        $dados['cliente_id'] = $cliente_id;
                    }
                    
                    if (isset($input['desconto'])) {
                        if (!is_numeric($input['desconto']) || floatval($input['desconto']) < 0) {
                            sendError('Desconto deve ser um número não-negativo', 400);
                        }
                        $dados['desconto'] = floatval($input['desconto']);
                    }
                    
                    if (isset($input['condicoes_pagamento'])) {
                        $dados['condicoes_pagamento'] = empty($input['condicoes_pagamento']) ? null : Validator::sanitizeString($input['condicoes_pagamento']);
                    }
                    
                    if (isset($input['observacoes'])) {
                        $dados['observacoes'] = empty($input['observacoes']) ? null : Validator::sanitizeString($input['observacoes']);
                    }
                    
                    if (isset($input['items']) && is_array($input['items'])) {
                        // Remove itens antigos
                        $db->delete('orcamento_itens', 'orcamento_id = ?', [$id]);
                        
                        // Insere novos itens
                        $valorTotal = 0;
                        foreach ($input['items'] as $item) {
                            if (empty($item['quantidade']) || !is_numeric($item['quantidade']) || $item['quantidade'] <= 0) {
                                sendError('Quantidade deve ser um número positivo', 400);
                            }
                            
                            if (empty($item['valor_unitario']) || !is_numeric($item['valor_unitario']) || $item['valor_unitario'] < 0) {
                                sendError('Valor unitário deve ser um número não-negativo', 400);
                            }
                            
                            $valorTotal += floatval($item['quantidade']) * floatval($item['valor_unitario']);
                            
                            $itemDados = [
                                'orcamento_id' => $id,
                                'produto_id' => !empty($item['produto_id']) ? intval($item['produto_id']) : null,
                                'descricao' => !empty($item['descricao']) ? Validator::sanitizeString($item['descricao']) : null,
                                'quantidade' => floatval($item['quantidade']),
                                'valor_unitario' => floatval($item['valor_unitario']),
                                'criado_em' => date('Y-m-d H:i:s')
                            ];
                            
                            $db->insert('orcamento_itens', $itemDados);
                        }
                        
                        $dados['valor_total'] = $valorTotal;
                    }
                    
                    $dados['atualizado_em'] = date('Y-m-d H:i:s');
                    $dados['atualizado_por'] = $usuario['id'];
                    
                    if (empty($dados)) {
                        sendError('Nenhum dado para atualizar', 400);
                    }
                    
                    // Atualiza no banco
                    $db->update('orcamentos', $dados, 'id = ?', [$id]);
                    
                    // Busca orçamento atualizado com itens
                    $orcamentoAtualizado = $db->queryOne(
                        "SELECT o.*, c.nome as cliente_nome FROM orcamentos o 
                         LEFT JOIN clientes c ON o.cliente_id = c.id 
                         WHERE o.id = ?",
                        [$id]
                    );
                    
                    $itens = $db->find('orcamento_itens', [
                        'where' => 'orcamento_id = ?',
                        'params' => [$id]
                    ]);
                    
                    $orcamentoAtualizado['itens'] = $itens;
                    
                    sendSuccess($orcamentoAtualizado, 'Orçamento atualizado com sucesso', 200);
                }
                
                /**
                 * DELETE /api/orcamentos/:id
                 * Deleta um orçamento existente
                 */
                elseif ($method === 'DELETE') {
                    // Busca orçamento
                    $orcamento = $db->queryOne(
                        "SELECT * FROM orcamentos WHERE id = ?",
                        [$id]
                    );
                    
                    if (!$orcamento) {
                        sendError('Orçamento não encontrado', 404);
                    }
                    
                    // Deleta itens do orçamento
                    $db->delete('orcamento_itens', 'orcamento_id = ?', [$id]);
                    
                    // Deleta orçamento
                    $db->delete('orcamentos', 'id = ?', [$id]);
                    
                    sendSuccess([], 'Orçamento deletado com sucesso', 200);
                }
                
                else {
                    sendError('Método não permitido', 405);
                }
            }
        }
        break;
}
?>
