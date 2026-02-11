<?php
/**
 * ARQUIVO: cobrancas.php
 * 
 * Função: Endpoints de gerenciamento de cobranças e pagamentos
 * Entrada: Dados de cobrança, parâmetros de busca, filtros e status de pagamento
 * Processamento: Cria, lê, atualiza, deleta, busca cobranças e gerencia pagamentos
 * Saída: Dados de cobrança(s), lista paginada, pendências, vencidas, mensagens de sucesso/erro
 * 
 * Endpoints disponíveis:
 * - GET /api/cobrancas - Lista cobranças
 * - GET /api/cobrancas/:id - Obtém cobrança específica
 * - POST /api/cobrancas - Cria cobrança
 * - PUT /api/cobrancas/:id - Atualiza cobrança
 * - PUT /api/cobrancas/:id/pagar - Marca como paga
 * - GET /api/cobrancas/pendentes - Lista pendentes
 * - GET /api/cobrancas/vencidas - Lista vencidas
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
     * GET /api/cobrancas
     * Lista todas as cobranças com paginação e filtros
     * Parâmetros: page, limit, order, status, data_inicio, data_fim, cliente_id
     */
    case '':
    case null:
        if ($method !== 'GET') {
            sendError('Método não permitido', 405);
        }
        
        // Obtém parâmetros de paginação
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : ITEMS_PER_PAGE;
        $order = isset($_GET['order']) ? $_GET['order'] : 'data_vencimento ASC';
        
        // Obtém filtros
        $status = isset($_GET['status']) ? $_GET['status'] : null;
        $data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : null;
        $data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : null;
        $cliente_id = isset($_GET['cliente_id']) ? intval($_GET['cliente_id']) : null;
        
        // Sanitiza ORDER BY
        $orderField = 'data_vencimento';
        $orderDir = 'ASC';
        
        if (preg_match('/^(numero|cliente_nome|valor|status|data_vencimento|data_pagamento|criado_em)\s+(ASC|DESC)$/i', $order, $matches)) {
            $orderField = $matches[1];
            $orderDir = strtoupper($matches[2]);
        }
        
        $offset = ($page - 1) * $limit;
        
        // Constrói cláusula WHERE
        $where = '1=1';
        $params = [];
        
        if (!is_null($status) && in_array($status, ['aberta', 'paga', 'cancelada'])) {
            $where .= ' AND c.status = ?';
            $params[] = $status;
        }
        
        if (!is_null($data_inicio)) {
            $where .= ' AND c.data_vencimento >= ?';
            $params[] = $data_inicio;
        }
        
        if (!is_null($data_fim)) {
            $where .= ' AND c.data_vencimento <= ?';
            $params[] = $data_fim;
        }
        
        if (!is_null($cliente_id) && $cliente_id > 0) {
            $where .= ' AND c.cliente_id = ?';
            $params[] = $cliente_id;
        }
        
        // Conta total de cobranças
        $total = $db->count('cobrancas c', $where, $params);
        
        // Busca cobranças paginadas
        $cobrancas = $db->find('cobrancas c', [
            'select' => 'c.*, cl.nome as cliente_nome',
            'leftJoin' => 'clientes cl ON c.cliente_id = cl.id',
            'where' => $where,
            'params' => $params,
            'order' => "c.{$orderField} {$orderDir}",
            'limit' => $limit,
            'offset' => $offset
        ]);
        
        // Adiciona informação de dias em atraso se aplicável
        foreach ($cobrancas as &$cobranca) {
            if ($cobranca['status'] === 'aberta' && strtotime($cobranca['data_vencimento']) < time()) {
                $dias_atraso = floor((time() - strtotime($cobranca['data_vencimento'])) / 86400);
                $cobranca['dias_atraso'] = $dias_atraso;
                $cobranca['vencida'] = true;
            } else {
                $cobranca['dias_atraso'] = 0;
                $cobranca['vencida'] = false;
            }
        }
        
        sendSuccess([
            'cobrancas' => $cobrancas,
            'paginacao' => [
                'pagina_atual' => $page,
                'total_itens' => $total,
                'itens_por_pagina' => $limit,
                'total_paginas' => ceil($total / $limit)
            ]
        ], 'Cobranças obtidas com sucesso', 200);
        break;
    
    /**
     * GET /api/cobrancas/pendentes
     * Lista cobranças pendentes
     * Parâmetros: page, limit, cliente_id
     */
    case 'pendentes':
        if ($method !== 'GET') {
            sendError('Método não permitido', 405);
        }
        
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : ITEMS_PER_PAGE;
        $cliente_id = isset($_GET['cliente_id']) ? intval($_GET['cliente_id']) : null;
        
        $offset = ($page - 1) * $limit;
        
        // Constrói cláusula WHERE
        $where = "c.status = 'aberta'";
        $params = [];
        
        if (!is_null($cliente_id) && $cliente_id > 0) {
            $where .= ' AND c.cliente_id = ?';
            $params[] = $cliente_id;
        }
        
        // Conta total
        $total = $db->count('cobrancas c', $where, $params);
        
        // Busca cobranças pendentes
        $cobrancas = $db->find('cobrancas c', [
            'select' => 'c.*, cl.nome as cliente_nome',
            'leftJoin' => 'clientes cl ON c.cliente_id = cl.id',
            'where' => $where,
            'params' => $params,
            'order' => 'c.data_vencimento ASC',
            'limit' => $limit,
            'offset' => $offset
        ]);
        
        // Calcula totalizações
        $totalizado = $db->queryOne(
            "SELECT COUNT(id) as total, SUM(valor) as valor_total FROM cobrancas WHERE $where",
            $params
        );
        
        sendSuccess([
            'cobrancas' => $cobrancas,
            'totalizacao' => $totalizado,
            'paginacao' => [
                'pagina_atual' => $page,
                'total_itens' => $total,
                'itens_por_pagina' => $limit,
                'total_paginas' => ceil($total / $limit)
            ]
        ], 'Cobranças pendentes obtidas com sucesso', 200);
        break;
    
    /**
     * GET /api/cobrancas/vencidas
     * Lista cobranças vencidas
     * Parâmetros: page, limit, cliente_id, dias_atraso
     */
    case 'vencidas':
        if ($method !== 'GET') {
            sendError('Método não permitido', 405);
        }
        
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : ITEMS_PER_PAGE;
        $cliente_id = isset($_GET['cliente_id']) ? intval($_GET['cliente_id']) : null;
        $dias_atraso = isset($_GET['dias_atraso']) ? intval($_GET['dias_atraso']) : null;
        
        $offset = ($page - 1) * $limit;
        
        // Constrói cláusula WHERE
        $where = "c.status = 'aberta' AND c.data_vencimento < CURDATE()";
        $params = [];
        
        if (!is_null($cliente_id) && $cliente_id > 0) {
            $where .= ' AND c.cliente_id = ?';
            $params[] = $cliente_id;
        }
        
        if (!is_null($dias_atraso) && $dias_atraso > 0) {
            $where .= ' AND DATEDIFF(CURDATE(), c.data_vencimento) >= ?';
            $params[] = $dias_atraso;
        }
        
        // Conta total
        $total = $db->count('cobrancas c', $where, $params);
        
        // Busca cobranças vencidas
        $cobrancas = $db->find('cobrancas c', [
            'select' => 'c.*, cl.nome as cliente_nome, DATEDIFF(CURDATE(), c.data_vencimento) as dias_atraso',
            'leftJoin' => 'clientes cl ON c.cliente_id = cl.id',
            'where' => $where,
            'params' => $params,
            'order' => 'c.data_vencimento ASC',
            'limit' => $limit,
            'offset' => $offset
        ]);
        
        // Calcula totalizações
        $totalizado = $db->queryOne(
            "SELECT COUNT(id) as total, SUM(valor) as valor_total FROM cobrancas WHERE $where",
            $params
        );
        
        sendSuccess([
            'cobrancas' => $cobrancas,
            'totalizacao' => $totalizado,
            'paginacao' => [
                'pagina_atual' => $page,
                'total_itens' => $total,
                'itens_por_pagina' => $limit,
                'total_paginas' => ceil($total / $limit)
            ]
        ], 'Cobranças vencidas obtidas com sucesso', 200);
        break;
    
    /**
     * POST /api/cobrancas
     * Cria nova cobrança
     * Requer: cliente_id, valor, data_vencimento
     * Opcionais: numero, descricao, venda_id, orcamento_id
     */
    default:
        if ($id === null || $id <= 0) {
            if ($method === 'POST') {
                // Valida entrada
                if (empty($input['cliente_id'])) {
                    sendError('ID do cliente é obrigatório', 400);
                }
                
                if (empty($input['valor']) || !is_numeric($input['valor']) || floatval($input['valor']) <= 0) {
                    sendError('Valor deve ser um número positivo', 400);
                }
                
                if (empty($input['data_vencimento'])) {
                    sendError('Data de vencimento é obrigatória', 400);
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
                
                // Valida data
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $input['data_vencimento'])) {
                    sendError('Formato de data inválido (use YYYY-MM-DD)', 400);
                }
                
                // Gera número da cobrança
                $ultimaCobranca = $db->queryOne(
                    "SELECT MAX(numero) as numero FROM cobrancas WHERE YEAR(criado_em) = YEAR(NOW())"
                );
                
                $proximoNumero = (!$ultimaCobranca || is_null($ultimaCobranca['numero'])) 
                    ? 1 
                    : intval($ultimaCobranca['numero']) + 1;
                
                // Prepara dados
                $dados = [
                    'numero' => $proximoNumero,
                    'cliente_id' => $cliente_id,
                    'valor' => floatval($input['valor']),
                    'data_vencimento' => $input['data_vencimento'],
                    'status' => 'aberta',
                    'descricao' => isset($input['descricao']) ? Validator::sanitizeString($input['descricao']) : null,
                    'venda_id' => isset($input['venda_id']) ? intval($input['venda_id']) : null,
                    'orcamento_id' => isset($input['orcamento_id']) ? intval($input['orcamento_id']) : null,
                    'criado_em' => date('Y-m-d H:i:s'),
                    'criado_por' => $usuario['id']
                ];
                
                // Insere cobrança
                $cobrancaId = $db->insert('cobrancas', $dados);
                
                // Busca cobrança criada
                $cobranca = $db->queryOne(
                    "SELECT c.*, cl.nome as cliente_nome FROM cobrancas c 
                     LEFT JOIN clientes cl ON c.cliente_id = cl.id 
                     WHERE c.id = ?",
                    [$cobrancaId]
                );
                
                sendSuccess($cobranca, 'Cobrança criada com sucesso', 201);
            } else {
                sendError('Método não permitido', 405);
            }
        } else {
            // Se houver ID, processa operações específicas
            
            if (!empty($acao)) {
                
                /**
                 * PUT /api/cobrancas/:id/pagar
                 * Marca cobrança como paga
                 * Opcionais: data_pagamento, desconto, observacoes
                 */
                if ($acao === 'pagar' && $method === 'PUT') {
                    // Busca cobrança
                    $cobranca = $db->queryOne(
                        "SELECT * FROM cobrancas WHERE id = ?",
                        [$id]
                    );
                    
                    if (!$cobranca) {
                        sendError('Cobrança não encontrada', 404);
                    }
                    
                    if ($cobranca['status'] === 'paga') {
                        sendError('Cobrança já foi paga', 400);
                    }
                    
                    // Prepara dados de pagamento
                    $dados = [
                        'status' => 'paga',
                        'data_pagamento' => isset($input['data_pagamento']) ? $input['data_pagamento'] : date('Y-m-d'),
                        'valor_pago' => isset($input['valor_pago']) ? floatval($input['valor_pago']) : $cobranca['valor'],
                        'desconto' => isset($input['desconto']) ? floatval($input['desconto']) : 0,
                        'observacoes_pagamento' => isset($input['observacoes']) ? Validator::sanitizeString($input['observacoes']) : null,
                        'atualizado_em' => date('Y-m-d H:i:s'),
                        'atualizado_por' => $usuario['id']
                    ];
                    
                    $db->update('cobrancas', $dados, 'id = ?', [$id]);
                    
                    // Busca cobrança atualizada
                    $cobrancaAtualizada = $db->queryOne(
                        "SELECT c.*, cl.nome as cliente_nome FROM cobrancas c 
                         LEFT JOIN clientes cl ON c.cliente_id = cl.id 
                         WHERE c.id = ?",
                        [$id]
                    );
                    
                    sendSuccess($cobrancaAtualizada, 'Cobrança marcada como paga com sucesso', 200);
                }
                
                else {
                    sendError('Ação não encontrada', 404);
                }
            } else {
                
                if ($method === 'GET') {
                    /**
                     * GET /api/cobrancas/:id
                     * Obtém cobrança específica
                     */
                    $cobranca = $db->queryOne(
                        "SELECT c.*, cl.nome as cliente_nome, cl.email, cl.telefone
                         FROM cobrancas c 
                         LEFT JOIN clientes cl ON c.cliente_id = cl.id 
                         WHERE c.id = ?",
                        [$id]
                    );
                    
                    if (!$cobranca) {
                        sendError('Cobrança não encontrada', 404);
                    }
                    
                    // Adiciona informação de dias em atraso
                    if ($cobranca['status'] === 'aberta' && strtotime($cobranca['data_vencimento']) < time()) {
                        $dias_atraso = floor((time() - strtotime($cobranca['data_vencimento'])) / 86400);
                        $cobranca['dias_atraso'] = $dias_atraso;
                        $cobranca['vencida'] = true;
                    } else {
                        $cobranca['dias_atraso'] = 0;
                        $cobranca['vencida'] = false;
                    }
                    
                    sendSuccess($cobranca, 'Cobrança obtida com sucesso', 200);
                }
                
                /**
                 * PUT /api/cobrancas/:id
                 * Atualiza cobrança
                 */
                elseif ($method === 'PUT') {
                    $cobranca = $db->queryOne(
                        "SELECT * FROM cobrancas WHERE id = ?",
                        [$id]
                    );
                    
                    if (!$cobranca) {
                        sendError('Cobrança não encontrada', 404);
                    }
                    
                    $dados = [];
                    
                    if (isset($input['valor'])) {
                        if (!is_numeric($input['valor']) || floatval($input['valor']) <= 0) {
                            sendError('Valor deve ser um número positivo', 400);
                        }
                        $dados['valor'] = floatval($input['valor']);
                    }
                    
                    if (isset($input['data_vencimento'])) {
                        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $input['data_vencimento'])) {
                            sendError('Formato de data inválido (use YYYY-MM-DD)', 400);
                        }
                        $dados['data_vencimento'] = $input['data_vencimento'];
                    }
                    
                    if (isset($input['status'])) {
                        if (!in_array($input['status'], ['aberta', 'paga', 'cancelada'])) {
                            sendError('Status inválido', 400);
                        }
                        $dados['status'] = $input['status'];
                    }
                    
                    if (isset($input['descricao'])) {
                        $dados['descricao'] = empty($input['descricao']) ? null : Validator::sanitizeString($input['descricao']);
                    }
                    
                    $dados['atualizado_em'] = date('Y-m-d H:i:s');
                    $dados['atualizado_por'] = $usuario['id'];
                    
                    if (empty($dados)) {
                        sendError('Nenhum dado para atualizar', 400);
                    }
                    
                    $db->update('cobrancas', $dados, 'id = ?', [$id]);
                    
                    $cobrancaAtualizada = $db->queryOne(
                        "SELECT c.*, cl.nome as cliente_nome FROM cobrancas c 
                         LEFT JOIN clientes cl ON c.cliente_id = cl.id 
                         WHERE c.id = ?",
                        [$id]
                    );
                    
                    sendSuccess($cobrancaAtualizada, 'Cobrança atualizada com sucesso', 200);
                }
                
                else {
                    sendError('Método não permitido', 405);
                }
            }
        }
        break;
}
?>
