<?php
/**
 * ARQUIVO: vendas.php
 * 
 * Função: Endpoints de gerenciamento de vendas e relatórios
 * Entrada: Dados de venda, parâmetros de busca, filtros e períodos
 * Processamento: Cria, lê, atualiza, deleta, busca vendas e gera relatórios
 * Saída: Dados de venda(s), lista paginada, gráficos, relatórios, mensagens de sucesso/erro
 * 
 * Endpoints disponíveis:
 * - GET /api/vendas - Lista vendas com paginação
 * - GET /api/vendas/:id - Obtém detalhes de uma venda
 * - POST /api/vendas - Cria nova venda
 * - PUT /api/vendas/:id - Atualiza venda
 * - GET /api/vendas/graficos - Gráficos de vendas (últimos 12 meses)
 * - GET /api/vendas/relatorio - Relatório detalhado de vendas
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

// Roteia para ação apropriada
switch ($subroute) {
    
    /**
     * GET /api/vendas
     * Lista todas as vendas com paginação e filtros
     * Parâmetros: page, limit, order, status, data_inicio, data_fim, cliente_id, vendedor_id
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
        $data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : null;
        $data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : null;
        $cliente_id = isset($_GET['cliente_id']) ? intval($_GET['cliente_id']) : null;
        $vendedor_id = isset($_GET['vendedor_id']) ? intval($_GET['vendedor_id']) : null;
        
        // Sanitiza ORDER BY
        $orderField = 'criado_em';
        $orderDir = 'DESC';
        
        if (preg_match('/^(numero|cliente_nome|valor_total|status|data_venda|criado_em)\s+(ASC|DESC)$/i', $order, $matches)) {
            $orderField = $matches[1];
            $orderDir = strtoupper($matches[2]);
        }
        
        $offset = ($page - 1) * $limit;
        
        // Constrói cláusula WHERE
        $where = '1=1';
        $params = [];
        
        if (!is_null($status) && in_array($status, ['pendente', 'confirmada', 'cancelada', 'entregue'])) {
            $where .= ' AND v.status = ?';
            $params[] = $status;
        }
        
        if (!is_null($data_inicio)) {
            $where .= ' AND v.data_venda >= ?';
            $params[] = $data_inicio . ' 00:00:00';
        }
        
        if (!is_null($data_fim)) {
            $where .= ' AND v.data_venda <= ?';
            $params[] = $data_fim . ' 23:59:59';
        }
        
        if (!is_null($cliente_id) && $cliente_id > 0) {
            $where .= ' AND v.cliente_id = ?';
            $params[] = $cliente_id;
        }
        
        if (!is_null($vendedor_id) && $vendedor_id > 0) {
            $where .= ' AND v.vendedor_id = ?';
            $params[] = $vendedor_id;
        }
        
        // Conta total de vendas
        $total = $db->count('vendas v', $where, $params);
        
        // Busca vendas paginadas
        $vendas = $db->find('vendas v', [
            'select' => 'v.*, c.nome as cliente_nome, u.nome as vendedor_nome, COUNT(vi.id) as total_itens',
            'leftJoin' => 'clientes c ON v.cliente_id = c.id',
            'leftJoin' => 'usuarios u ON v.vendedor_id = u.id',
            'leftJoin' => 'venda_itens vi ON v.id = vi.venda_id',
            'where' => $where,
            'params' => $params,
            'groupBy' => 'v.id',
            'order' => "v.{$orderField} {$orderDir}",
            'limit' => $limit,
            'offset' => $offset
        ]);
        
        sendSuccess([
            'vendas' => $vendas,
            'paginacao' => [
                'pagina_atual' => $page,
                'total_itens' => $total,
                'itens_por_pagina' => $limit,
                'total_paginas' => ceil($total / $limit)
            ]
        ], 'Vendas obtidas com sucesso', 200);
        break;
    
    /**
     * GET /api/vendas/graficos
     * Obtém gráficos de vendas dos últimos 12 meses
     * Parâmetros: tipo (mensal, semanal), ano (opcional)
     */
    case 'graficos':
        if ($method !== 'GET') {
            sendError('Método não permitido', 405);
        }
        
        $tipo = isset($_GET['tipo']) ? $_GET['tipo'] : 'mensal';
        $ano = isset($_GET['ano']) ? intval($_GET['ano']) : intval(date('Y'));
        
        if (!in_array($tipo, ['mensal', 'semanal'])) {
            sendError('Tipo de gráfico inválido', 400);
        }
        
        $graficos = [];
        
        if ($tipo === 'mensal') {
            // Gráfico de vendas mensais
            $query = "SELECT 
                        DATE_TRUNC(v.data_venda, MONTH) as mes,
                        MONTH(v.data_venda) as numero_mes,
                        YEAR(v.data_venda) as ano_mes,
                        COUNT(v.id) as total_vendas,
                        SUM(v.valor_total) as valor_total
                      FROM vendas v
                      WHERE YEAR(v.data_venda) = ?
                      GROUP BY YEAR(v.data_venda), MONTH(v.data_venda)
                      ORDER BY v.data_venda ASC";
            
            $graficos = $db->find('vendas', [
                'select' => 'MONTH(data_venda) as mes, YEAR(data_venda) as ano, COUNT(id) as total_vendas, SUM(valor_total) as valor_total',
                'where' => 'YEAR(data_venda) = ?',
                'params' => [$ano],
                'groupBy' => 'YEAR(data_venda), MONTH(data_venda)',
                'order' => 'MONTH(data_venda) ASC'
            ]);
        } else {
            // Gráfico de vendas semanais (últimas 12 semanas)
            $graficos = $db->find('vendas', [
                'select' => 'WEEK(data_venda) as semana, YEAR(data_venda) as ano, COUNT(id) as total_vendas, SUM(valor_total) as valor_total',
                'where' => 'data_venda >= DATE_SUB(NOW(), INTERVAL 12 WEEK)',
                'params' => [],
                'groupBy' => 'YEAR(data_venda), WEEK(data_venda)',
                'order' => 'data_venda ASC'
            ]);
        }
        
        // Calcula totalizações
        $totalGeral = $db->queryOne(
            "SELECT COUNT(id) as total_vendas, SUM(valor_total) as valor_total FROM vendas WHERE YEAR(data_venda) = ?",
            [$ano]
        );
        
        sendSuccess([
            'tipo' => $tipo,
            'periodo' => $tipo === 'mensal' ? $ano : 'últimas 12 semanas',
            'graficos' => $graficos,
            'totalizacao' => $totalGeral
        ], 'Gráficos obtidos com sucesso', 200);
        break;
    
    /**
     * GET /api/vendas/relatorio
     * Obtém relatório detalhado de vendas
     * Parâmetros: data_inicio, data_fim, cliente_id, formato (json, csv)
     */
    case 'relatorio':
        if ($method !== 'GET') {
            sendError('Método não permitido', 405);
        }
        
        $data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : date('Y-m-01');
        $data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : date('Y-m-d');
        $cliente_id = isset($_GET['cliente_id']) ? intval($_GET['cliente_id']) : null;
        $formato = isset($_GET['formato']) ? $_GET['formato'] : 'json';
        
        $where = 'v.data_venda >= ? AND v.data_venda <= ?';
        $params = [$data_inicio . ' 00:00:00', $data_fim . ' 23:59:59'];
        
        if (!is_null($cliente_id) && $cliente_id > 0) {
            $where .= ' AND v.cliente_id = ?';
            $params[] = $cliente_id;
        }
        
        // Busca vendas para relatório
        $vendas = $db->find('vendas v', [
            'select' => 'v.*, c.nome as cliente_nome, c.email, c.telefone, u.nome as vendedor_nome',
            'leftJoin' => 'clientes c ON v.cliente_id = c.id',
            'leftJoin' => 'usuarios u ON v.vendedor_id = u.id',
            'where' => $where,
            'params' => $params,
            'order' => 'v.data_venda DESC'
        ]);
        
        // Busca itens das vendas
        foreach ($vendas as &$venda) {
            $itens = $db->find('venda_itens vi', [
                'select' => 'vi.*, p.nome as produto_nome',
                'leftJoin' => 'produtos p ON vi.produto_id = p.id',
                'where' => 'vi.venda_id = ?',
                'params' => [$venda['id']]
            ]);
            
            $venda['itens'] = $itens;
        }
        
        // Calcula totalizações
        $totalizado = $db->queryOne(
            "SELECT COUNT(v.id) as total_vendas, 
                    SUM(v.valor_total) as valor_total,
                    AVG(v.valor_total) as ticket_medio
             FROM vendas v
             WHERE $where",
            $params
        );
        
        $relatorio = [
            'periodo' => [
                'data_inicio' => $data_inicio,
                'data_fim' => $data_fim
            ],
            'filtros' => [
                'cliente_id' => $cliente_id
            ],
            'totalizacao' => $totalizado,
            'vendas' => $vendas
        ];
        
        sendSuccess($relatorio, 'Relatório obtido com sucesso', 200);
        break;
    
    /**
     * POST /api/vendas
     * Cria nova venda
     * Requer: cliente_id, items (array)
     * Opcionais: vendedor_id, desconto, observacoes
     */
    default:
        if ($id === null || $id <= 0) {
            if ($method === 'POST') {
                // Valida entrada
                if (empty($input['cliente_id'])) {
                    sendError('ID do cliente é obrigatório', 400);
                }
                
                if (empty($input['items']) || !is_array($input['items'])) {
                    sendError('Items é obrigatório e deve ser um array', 400);
                }
                
                if (count($input['items']) === 0) {
                    sendError('Venda deve conter no mínimo 1 item', 400);
                }
                
                $cliente_id = intval($input['cliente_id']);
                
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
                    if (empty($item['produto_id'])) {
                        sendError('Cada item deve ter produto_id', 400);
                    }
                    
                    if (empty($item['quantidade']) || !is_numeric($item['quantidade']) || $item['quantidade'] <= 0) {
                        sendError('Quantidade deve ser um número positivo', 400);
                    }
                    
                    if (empty($item['valor_unitario']) || !is_numeric($item['valor_unitario']) || $item['valor_unitario'] < 0) {
                        sendError('Valor unitário deve ser um número não-negativo', 400);
                    }
                    
                    $valorTotal += floatval($item['quantidade']) * floatval($item['valor_unitario']);
                }
                
                // Gera número da venda
                $ultimaVenda = $db->queryOne(
                    "SELECT MAX(numero) as numero FROM vendas WHERE YEAR(data_venda) = YEAR(NOW())"
                );
                
                $proximoNumero = (!$ultimaVenda || is_null($ultimaVenda['numero'])) 
                    ? 1 
                    : intval($ultimaVenda['numero']) + 1;
                
                // Prepara dados da venda
                $vendaDados = [
                    'numero' => $proximoNumero,
                    'cliente_id' => $cliente_id,
                    'vendedor_id' => isset($input['vendedor_id']) ? intval($input['vendedor_id']) : $usuario['id'],
                    'valor_total' => $valorTotal,
                    'desconto' => isset($input['desconto']) ? floatval($input['desconto']) : 0,
                    'status' => 'confirmada',
                    'data_venda' => date('Y-m-d H:i:s'),
                    'observacoes' => isset($input['observacoes']) ? Validator::sanitizeString($input['observacoes']) : null,
                    'criado_em' => date('Y-m-d H:i:s'),
                    'criado_por' => $usuario['id']
                ];
                
                // Insere venda
                $vendaId = $db->insert('vendas', $vendaDados);
                
                // Insere itens da venda
                foreach ($input['items'] as $item) {
                    // Verifica se produto existe
                    $produto = $db->queryOne(
                        "SELECT quantidade FROM produtos WHERE id = ?",
                        [$item['produto_id']]
                    );
                    
                    if (!$produto) {
                        sendError('Produto não encontrado', 404);
                    }
                    
                    $itemDados = [
                        'venda_id' => $vendaId,
                        'produto_id' => intval($item['produto_id']),
                        'quantidade' => floatval($item['quantidade']),
                        'valor_unitario' => floatval($item['valor_unitario']),
                        'criado_em' => date('Y-m-d H:i:s')
                    ];
                    
                    $db->insert('venda_itens', $itemDados);
                    
                    // Atualiza estoque do produto
                    $novaQuantidade = $produto['quantidade'] - floatval($item['quantidade']);
                    $db->update('produtos', ['quantidade' => $novaQuantidade], 'id = ?', [$item['produto_id']]);
                }
                
                // Busca venda criada com itens
                $venda = $db->queryOne(
                    "SELECT v.*, c.nome as cliente_nome FROM vendas v 
                     LEFT JOIN clientes c ON v.cliente_id = c.id 
                     WHERE v.id = ?",
                    [$vendaId]
                );
                
                $itens = $db->find('venda_itens', [
                    'where' => 'venda_id = ?',
                    'params' => [$vendaId]
                ]);
                
                $venda['itens'] = $itens;
                
                sendSuccess($venda, 'Venda criada com sucesso', 201);
            } else {
                sendError('Método não permitido', 405);
            }
        } else {
            if ($method === 'GET') {
                /**
                 * GET /api/vendas/:id
                 * Obtém detalhes de uma venda
                 */
                $venda = $db->queryOne(
                    "SELECT v.*, c.nome as cliente_nome, c.email, c.telefone,
                            u.nome as vendedor_nome
                     FROM vendas v 
                     LEFT JOIN clientes c ON v.cliente_id = c.id 
                     LEFT JOIN usuarios u ON v.vendedor_id = u.id 
                     WHERE v.id = ?",
                    [$id]
                );
                
                if (!$venda) {
                    sendError('Venda não encontrada', 404);
                }
                
                // Busca itens
                $itens = $db->find('venda_itens vi', [
                    'select' => 'vi.*, p.nome as produto_nome, p.codigo',
                    'leftJoin' => 'produtos p ON vi.produto_id = p.id',
                    'where' => 'vi.venda_id = ?',
                    'params' => [$id]
                ]);
                
                $venda['itens'] = $itens;
                
                sendSuccess($venda, 'Venda obtida com sucesso', 200);
            }
            
            /**
             * PUT /api/vendas/:id
             * Atualiza venda
             */
            elseif ($method === 'PUT') {
                $venda = $db->queryOne(
                    "SELECT * FROM vendas WHERE id = ?",
                    [$id]
                );
                
                if (!$venda) {
                    sendError('Venda não encontrada', 404);
                }
                
                $dados = [];
                
                if (isset($input['status'])) {
                    if (!in_array($input['status'], ['pendente', 'confirmada', 'cancelada', 'entregue'])) {
                        sendError('Status inválido', 400);
                    }
                    $dados['status'] = $input['status'];
                }
                
                if (isset($input['desconto'])) {
                    if (!is_numeric($input['desconto']) || floatval($input['desconto']) < 0) {
                        sendError('Desconto deve ser um número não-negativo', 400);
                    }
                    $dados['desconto'] = floatval($input['desconto']);
                }
                
                if (isset($input['observacoes'])) {
                    $dados['observacoes'] = empty($input['observacoes']) ? null : Validator::sanitizeString($input['observacoes']);
                }
                
                $dados['atualizado_em'] = date('Y-m-d H:i:s');
                $dados['atualizado_por'] = $usuario['id'];
                
                if (empty($dados)) {
                    sendError('Nenhum dado para atualizar', 400);
                }
                
                $db->update('vendas', $dados, 'id = ?', [$id]);
                
                $vendaAtualizada = $db->queryOne(
                    "SELECT v.*, c.nome as cliente_nome FROM vendas v 
                     LEFT JOIN clientes c ON v.cliente_id = c.id 
                     WHERE v.id = ?",
                    [$id]
                );
                
                $itens = $db->find('venda_itens', [
                    'where' => 'venda_id = ?',
                    'params' => [$id]
                ]);
                
                $vendaAtualizada['itens'] = $itens;
                
                sendSuccess($vendaAtualizada, 'Venda atualizada com sucesso', 200);
            }
            
            else {
                sendError('Método não permitido', 405);
            }
        }
        break;
}
?>
