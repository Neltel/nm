<?php
/**
 * ARQUIVO: pedidos.php
 * 
 * Função: Endpoints de gerenciamento de pedidos
 * Entrada: Dados de pedido, itens do pedido, parâmetros de busca e filtros
 * Processamento: Cria, lê, atualiza, deleta pedidos e gerencia status
 * Saída: Dados de pedido(s), lista paginada, detalhes de pedido, mensagens de sucesso/erro
 * 
 * Endpoints disponíveis:
 * - GET /api/pedidos - Lista todos os pedidos (com paginação)
 * - GET /api/pedidos/:id - Obtém um pedido específico com detalhes
 * - POST /api/pedidos - Cria novo pedido
 * - PUT /api/pedidos/:id - Atualiza pedido existente
 * - DELETE /api/pedidos/:id - Deleta pedido
 * - PUT /api/pedidos/:id/status - Atualiza status do pedido
 * - GET /api/pedidos/cliente/:id - Obtém pedidos de um cliente específico
 */

// Inicializa classe de autenticação
$auth = new Auth($db);

// Valida autenticação para todos os endpoints
$usuario = $auth->getAuthenticatedUser();
if (!$usuario) {
    sendError('Usuário não autenticado', 401);
}

// Obtém subrouta (segunda parte da URL)
$subroute = isset($parts[1]) ? $parts[1] : '';
$id = isset($parts[2]) ? intval($parts[2]) : null;
$acao = isset($parts[3]) ? $parts[3] : '';

// Roteia para ação apropriada
switch ($subroute) {
    
    /**
     * GET /api/pedidos
     * Lista todos os pedidos com paginação
     * Parâmetros: page, limit, order, status
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
        
        // Obtém filtro de status
        $status = isset($_GET['status']) ? $_GET['status'] : null;
        
        // Sanitiza ORDER BY para evitar SQL injection
        $orderField = 'criado_em';
        $orderDir = 'DESC';
        
        if (preg_match('/^(numero|status|criado_em|total)\s+(ASC|DESC)$/i', $order, $matches)) {
            $orderField = $matches[1];
            $orderDir = strtoupper($matches[2]);
        }
        
        $offset = ($page - 1) * $limit;
        
        // Constrói cláusula WHERE para filtros
        $where = '1=1';
        $params = [];
        
        if (!is_null($status)) {
            $statusValidos = ['pendente', 'processando', 'enviado', 'entregue', 'cancelado'];
            if (in_array($status, $statusValidos)) {
                $where .= ' AND status = ?';
                $params[] = $status;
            }
        }
        
        // Conta total de pedidos
        $total = $db->count('pedidos', $where, $params);
        
        // Busca pedidos paginados
        $pedidos = $db->find('pedidos', [
            'where' => $where,
            'params' => $params,
            'order' => "{$orderField} {$orderDir}",
            'limit' => $limit,
            'offset' => $offset
        ]);
        
        sendSuccess([
            'pedidos' => $pedidos,
            'paginacao' => [
                'pagina_atual' => $page,
                'total_itens' => $total,
                'itens_por_pagina' => $limit,
                'total_paginas' => ceil($total / $limit)
            ]
        ], 'Pedidos obtidos com sucesso', 200);
        break;
    
    /**
     * GET /api/pedidos/cliente/:id
     * Obtém pedidos de um cliente específico
     * Parâmetros: page, limit
     */
    case 'cliente':
        if ($method !== 'GET') {
            sendError('Método não permitido', 405);
        }
        
        $cliente_id = isset($parts[2]) ? intval($parts[2]) : null;
        
        if ($cliente_id === null || $cliente_id <= 0) {
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
        
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : ITEMS_PER_PAGE;
        $offset = ($page - 1) * $limit;
        
        // Busca pedidos do cliente
        $total = $db->count(
            'pedidos',
            'cliente_id = ?',
            [$cliente_id]
        );
        
        $pedidos = $db->find('pedidos', [
            'where' => 'cliente_id = ?',
            'params' => [$cliente_id],
            'order' => 'criado_em DESC',
            'limit' => $limit,
            'offset' => $offset
        ]);
        
        sendSuccess([
            'pedidos' => $pedidos,
            'cliente_id' => $cliente_id,
            'paginacao' => [
                'pagina_atual' => $page,
                'total_itens' => $total,
                'itens_por_pagina' => $limit,
                'total_paginas' => ceil($total / $limit)
            ]
        ], 'Pedidos do cliente obtidos com sucesso', 200);
        break;
    
    /**
     * POST /api/pedidos
     * Cria novo pedido
     * Requer: cliente_id, itens (array com id_produto, quantidade, preco_unitario)
     * Opcionais: observacoes
     */
    case null:
        if ($method === 'POST') {
            // Valida entrada obrigatória
            if (empty($input['cliente_id'])) {
                sendError('ID do cliente é obrigatório', 400);
            }
            
            if (empty($input['itens']) || !is_array($input['itens']) || count($input['itens']) === 0) {
                sendError('Itens do pedido são obrigatórios (mínimo 1 item)', 400);
            }
            
            $cliente_id = intval($input['cliente_id']);
            
            if ($cliente_id <= 0) {
                sendError('ID do cliente inválido', 400);
            }
            
            // Verifica se cliente existe
            $cliente = $db->queryOne(
                "SELECT id FROM clientes WHERE id = ?",
                [$cliente_id]
            );
            
            if (!$cliente) {
                sendError('Cliente não encontrado', 404);
            }
            
            // Valida e processa itens
            $totalPedido = 0;
            $itensProcessados = [];
            
            foreach ($input['itens'] as $item) {
                // Valida campos obrigatórios do item
                if (empty($item['id_produto']) || empty($item['quantidade']) || empty($item['preco_unitario'])) {
                    sendError('Item inválido: id_produto, quantidade e preco_unitario são obrigatórios', 400);
                }
                
                $produto_id = intval($item['id_produto']);
                $quantidade = intval($item['quantidade']);
                $preco_unitario = floatval($item['preco_unitario']);
                
                if ($produto_id <= 0 || $quantidade <= 0 || $preco_unitario < 0) {
                    sendError('Valores de item inválidos (ID positivo, quantidade positiva, preço não-negativo)', 400);
                }
                
                // Verifica se produto existe
                $produto = $db->queryOne(
                    "SELECT id, quantidade FROM produtos WHERE id = ?",
                    [$produto_id]
                );
                
                if (!$produto) {
                    sendError("Produto ID {$produto_id} não encontrado", 404);
                }
                
                // Valida estoque
                if ($produto['quantidade'] < $quantidade) {
                    sendError("Estoque insuficiente para produto ID {$produto_id}", 400);
                }
                
                $subtotal = $quantidade * $preco_unitario;
                $totalPedido += $subtotal;
                
                $itensProcessados[] = [
                    'produto_id' => $produto_id,
                    'quantidade' => $quantidade,
                    'preco_unitario' => $preco_unitario,
                    'subtotal' => $subtotal
                ];
            }
            
            // Gera número de pedido único
            $numeroPedido = 'PED' . date('Ymd') . uniqid();
            
            // Prepara dados do pedido
            $dados = [
                'numero' => $numeroPedido,
                'cliente_id' => $cliente_id,
                'status' => 'pendente',
                'total' => $totalPedido,
                'observacoes' => isset($input['observacoes']) ? Validator::sanitizeString($input['observacoes']) : null,
                'criado_em' => date('Y-m-d H:i:s'),
                'criado_por' => $usuario['id']
            ];
            
            // Insere pedido no banco
            $pedidoId = $db->insert('pedidos', $dados);
            
            // Insere itens do pedido
            foreach ($itensProcessados as $item) {
                $dadosItem = [
                    'pedido_id' => $pedidoId,
                    'produto_id' => $item['produto_id'],
                    'quantidade' => $item['quantidade'],
                    'preco_unitario' => $item['preco_unitario'],
                    'subtotal' => $item['subtotal']
                ];
                
                $db->insert('pedidos_itens', $dadosItem);
            }
            
            // Busca pedido criado com seus itens
            $pedido = $db->queryOne(
                "SELECT * FROM pedidos WHERE id = ?",
                [$pedidoId]
            );
            
            $itens = $db->find('pedidos_itens', [
                'where' => 'pedido_id = ?',
                'params' => [$pedidoId]
            ]);
            
            $pedido['itens'] = $itens;
            
            sendSuccess($pedido, 'Pedido criado com sucesso', 201);
        }
        break;
    
    /**
     * GET /api/pedidos/:id
     * Obtém dados de um pedido específico com seus itens
     */
    default:
        if ($id === null || $id <= 0) {
            sendError('ID de pedido inválido', 400);
        }
        
        // Se houver ação adicional (ex: /api/pedidos/:id/status)
        if (!empty($acao)) {
            
            /**
             * PUT /api/pedidos/:id/status
             * Atualiza status do pedido
             * Requer: status (pendente, processando, enviado, entregue, cancelado)
             */
            if ($acao === 'status' && $method === 'PUT') {
                // Busca pedido
                $pedido = $db->queryOne(
                    "SELECT * FROM pedidos WHERE id = ?",
                    [$id]
                );
                
                if (!$pedido) {
                    sendError('Pedido não encontrado', 404);
                }
                
                // Valida entrada
                if (empty($input['status'])) {
                    sendError('Status é obrigatório', 400);
                }
                
                $statusValidos = ['pendente', 'processando', 'enviado', 'entregue', 'cancelado'];
                if (!in_array($input['status'], $statusValidos)) {
                    sendError('Status deve ser um dos: ' . implode(', ', $statusValidos), 400);
                }
                
                // Prepara dados
                $dados = [
                    'status' => $input['status'],
                    'atualizado_em' => date('Y-m-d H:i:s'),
                    'atualizado_por' => $usuario['id']
                ];
                
                // Se entregue, adiciona data de entrega
                if ($input['status'] === 'entregue' && !isset($pedido['data_entrega'])) {
                    $dados['data_entrega'] = date('Y-m-d H:i:s');
                }
                
                // Atualiza no banco
                $db->update('pedidos', $dados, 'id = ?', [$id]);
                
                // Busca pedido atualizado
                $pedidoAtualizado = $db->queryOne(
                    "SELECT * FROM pedidos WHERE id = ?",
                    [$id]
                );
                
                sendSuccess($pedidoAtualizado, 'Status do pedido atualizado com sucesso', 200);
            } else {
                sendError('Ação não encontrada', 404);
            }
        } else {
            
            if ($method === 'GET') {
                // Busca pedido com informações do cliente
                $pedido = $db->queryOne(
                    "SELECT p.*, c.nome as cliente_nome, c.email as cliente_email
                     FROM pedidos p
                     LEFT JOIN clientes c ON p.cliente_id = c.id
                     WHERE p.id = ?",
                    [$id]
                );
                
                if (!$pedido) {
                    sendError('Pedido não encontrado', 404);
                }
                
                // Busca itens do pedido
                $itens = $db->find('pedidos_itens', [
                    'where' => 'pedido_id = ?',
                    'params' => [$id]
                ]);
                
                $pedido['itens'] = $itens;
                
                sendSuccess($pedido, 'Pedido obtido com sucesso', 200);
            }
            
            /**
             * PUT /api/pedidos/:id
             * Atualiza dados de um pedido existente
             * Nota: Recomenda-se não alterar itens após criação, usar DELETE e POST para refazer
             */
            elseif ($method === 'PUT') {
                // Busca pedido existente
                $pedido = $db->queryOne(
                    "SELECT * FROM pedidos WHERE id = ?",
                    [$id]
                );
                
                if (!$pedido) {
                    sendError('Pedido não encontrado', 404);
                }
                
                // Valida cliente se fornecido
                if (isset($input['cliente_id'])) {
                    $cliente_id = intval($input['cliente_id']);
                    
                    if ($cliente_id <= 0) {
                        sendError('ID do cliente inválido', 400);
                    }
                    
                    $cliente = $db->queryOne(
                        "SELECT id FROM clientes WHERE id = ?",
                        [$cliente_id]
                    );
                    
                    if (!$cliente) {
                        sendError('Cliente não encontrado', 404);
                    }
                }
                
                // Prepara dados para atualização
                $dados = [];
                
                if (isset($input['cliente_id'])) {
                    $dados['cliente_id'] = intval($input['cliente_id']);
                }
                
                if (isset($input['status'])) {
                    $statusValidos = ['pendente', 'processando', 'enviado', 'entregue', 'cancelado'];
                    if (!in_array($input['status'], $statusValidos)) {
                        sendError('Status inválido', 400);
                    }
                    $dados['status'] = $input['status'];
                }
                
                if (isset($input['observacoes'])) {
                    $dados['observacoes'] = empty($input['observacoes']) ? null : Validator::sanitizeString($input['observacoes']);
                }
                
                if (isset($input['total'])) {
                    if (!is_numeric($input['total']) || floatval($input['total']) < 0) {
                        sendError('Total deve ser um número não-negativo', 400);
                    }
                    $dados['total'] = floatval($input['total']);
                }
                
                $dados['atualizado_em'] = date('Y-m-d H:i:s');
                $dados['atualizado_por'] = $usuario['id'];
                
                if (empty($dados)) {
                    sendError('Nenhum dado para atualizar', 400);
                }
                
                // Atualiza no banco
                $db->update('pedidos', $dados, 'id = ?', [$id]);
                
                // Busca pedido atualizado com itens
                $pedidoAtualizado = $db->queryOne(
                    "SELECT * FROM pedidos WHERE id = ?",
                    [$id]
                );
                
                $itens = $db->find('pedidos_itens', [
                    'where' => 'pedido_id = ?',
                    'params' => [$id]
                ]);
                
                $pedidoAtualizado['itens'] = $itens;
                
                sendSuccess($pedidoAtualizado, 'Pedido atualizado com sucesso', 200);
            }
            
            /**
             * DELETE /api/pedidos/:id
             * Deleta um pedido existente
             * Nota: Deve estar em status 'pendente' para ser deletado
             */
            elseif ($method === 'DELETE') {
                // Busca pedido
                $pedido = $db->queryOne(
                    "SELECT * FROM pedidos WHERE id = ?",
                    [$id]
                );
                
                if (!$pedido) {
                    sendError('Pedido não encontrado', 404);
                }
                
                // Valida se pode ser deletado
                if ($pedido['status'] !== 'pendente') {
                    sendError('Apenas pedidos em status "pendente" podem ser deletados', 400);
                }
                
                // Deleta itens do pedido
                $db->delete('pedidos_itens', 'pedido_id = ?', [$id]);
                
                // Deleta pedido
                $db->delete('pedidos', 'id = ?', [$id]);
                
                sendSuccess([], 'Pedido deletado com sucesso', 200);
            }
            
            else {
                sendError('Método não permitido', 405);
            }
        }
        break;
}
?>
