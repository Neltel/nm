<?php
/**
 * ARQUIVO: financeiro.php
 * 
 * Função: Endpoints CRUD de gerenciamento de transações financeiras
 * Entrada: Dados de transação, parâmetros de busca, paginação, filtros de data
 * Processamento: Cria, lê, atualiza, deleta transações de receita/despesa, gera extratos, gráficos
 * Saída: Dados de transação(s), extrato mensal, dados para gráficos, mensagens de sucesso/erro
 * 
 * Endpoints disponíveis:
 * - GET /api/financeiro - Lista transações (com paginação e filtros)
 * - GET /api/financeiro/:id - Obtém transação específica
 * - POST /api/financeiro - Cria nova transação (receita/despesa)
 * - PUT /api/financeiro/:id - Atualiza transação existente
 * - DELETE /api/financeiro/:id - Deleta transação
 * - GET /api/financeiro/extrato - Gera extrato mensal
 * - GET /api/financeiro/graficos - Gera dados para gráficos financeiros
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
     * GET /api/financeiro/extrato
     * Gera extrato mensal
     * Parâmetros: mes, ano
     */
    case 'extrato':
        if ($method !== 'GET') {
            sendError('Método não permitido', 405);
        }
        
        $mes = isset($_GET['mes']) ? intval($_GET['mes']) : intval(date('m'));
        $ano = isset($_GET['ano']) ? intval($_GET['ano']) : intval(date('Y'));
        
        // Valida mês e ano
        if ($mes < 1 || $mes > 12) {
            sendError('Mês deve estar entre 1 e 12', 400);
        }
        
        if ($ano < 2000 || $ano > 2100) {
            sendError('Ano inválido', 400);
        }
        
        // Define datas
        $dataInicio = "{$ano}-" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "-01";
        $dataFim = date('Y-m-t', strtotime($dataInicio));
        
        // Busca receitas
        $receitas = $db->find('financeiro', [
            'where' => "tipo = 'receita' AND DATE(data_transacao) >= ? AND DATE(data_transacao) <= ?",
            'params' => [$dataInicio, $dataFim],
            'order' => 'data_transacao DESC'
        ]);
        
        // Busca despesas
        $despesas = $db->find('financeiro', [
            'where' => "tipo = 'despesa' AND DATE(data_transacao) >= ? AND DATE(data_transacao) <= ?",
            'params' => [$dataInicio, $dataFim],
            'order' => 'data_transacao DESC'
        ]);
        
        // Calcula totais
        $totalReceitas = array_reduce($receitas, function($carry, $item) {
            return $carry + floatval($item['valor']);
        }, 0);
        
        $totalDespesas = array_reduce($despesas, function($carry, $item) {
            return $carry + floatval($item['valor']);
        }, 0);
        
        $saldo = $totalReceitas - $totalDespesas;
        
        sendSuccess([
            'mes' => str_pad($mes, 2, '0', STR_PAD_LEFT),
            'ano' => $ano,
            'data_inicio' => $dataInicio,
            'data_fim' => $dataFim,
            'receitas' => $receitas,
            'despesas' => $despesas,
            'totais' => [
                'receitas' => round($totalReceitas, 2),
                'despesas' => round($totalDespesas, 2),
                'saldo' => round($saldo, 2)
            ]
        ], 'Extrato obtido com sucesso', 200);
        break;
    
    /**
     * GET /api/financeiro/graficos
     * Gera dados para gráficos financeiros
     * Parâmetros: tipo (mensal/anual), periodo (últimos 6 meses, últimos 12 meses)
     */
    case 'graficos':
        if ($method !== 'GET') {
            sendError('Método não permitido', 405);
        }
        
        $tipo = isset($_GET['tipo']) ? $_GET['tipo'] : 'mensal';
        $periodo = isset($_GET['periodo']) ? intval($_GET['periodo']) : 6;
        
        if (!in_array($tipo, ['mensal', 'anual'])) {
            sendError('Tipo de gráfico inválido', 400);
        }
        
        if ($periodo <= 0 || $periodo > 12) {
            sendError('Período deve estar entre 1 e 12', 400);
        }
        
        $dados = [
            'receitas' => [],
            'despesas' => [],
            'labels' => []
        ];
        
        // Gera dados mensais
        for ($i = $periodo - 1; $i >= 0; $i--) {
            $data = date('Y-m-01', strtotime("-{$i} months"));
            $mes = date('m', strtotime($data));
            $ano = date('Y', strtotime($data));
            
            $dataInicio = $data;
            $dataFim = date('Y-m-t', strtotime($data));
            
            // Busca receitas do período
            $receitasPeriodo = $db->queryOne(
                "SELECT COALESCE(SUM(valor), 0) as total FROM financeiro 
                 WHERE tipo = 'receita' AND DATE(data_transacao) >= ? AND DATE(data_transacao) <= ?",
                [$dataInicio, $dataFim]
            );
            
            // Busca despesas do período
            $despesasPeriodo = $db->queryOne(
                "SELECT COALESCE(SUM(valor), 0) as total FROM financeiro 
                 WHERE tipo = 'despesa' AND DATE(data_transacao) >= ? AND DATE(data_transacao) <= ?",
                [$dataInicio, $dataFim]
            );
            
            $dados['labels'][] = date('M/Y', strtotime($data));
            $dados['receitas'][] = round(floatval($receitasPeriodo['total']), 2);
            $dados['despesas'][] = round(floatval($despesasPeriodo['total']), 2);
        }
        
        sendSuccess($dados, 'Dados de gráficos obtidos com sucesso', 200);
        break;
    
    /**
     * GET /api/financeiro
     * Lista transações com paginação e filtros
     * Parâmetros: page, limit, tipo, status, data_inicio, data_fim, categoria
     */
    case '':
    case null:
        if ($method !== 'GET') {
            sendError('Método não permitido', 405);
        }
        
        // Obtém parâmetros de paginação
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : ITEMS_PER_PAGE;
        
        // Obtém filtros
        $tipo = isset($_GET['tipo']) ? $_GET['tipo'] : null;
        $status = isset($_GET['status']) ? $_GET['status'] : null;
        $categoria = isset($_GET['categoria']) ? $_GET['categoria'] : null;
        
        $offset = ($page - 1) * $limit;
        
        // Constrói cláusula WHERE para filtros
        $where = '1=1';
        $params = [];
        
        if (!is_null($tipo) && in_array($tipo, ['receita', 'despesa'])) {
            $where .= ' AND tipo = ?';
            $params[] = $tipo;
        }
        
        if (!is_null($status) && in_array($status, ['pendente', 'pago', 'cancelado'])) {
            $where .= ' AND status = ?';
            $params[] = $status;
        }
        
        if (!is_null($categoria)) {
            $where .= ' AND categoria = ?';
            $params[] = Validator::sanitizeString($categoria);
        }
        
        // Conta total de transações
        $total = $db->count('financeiro', $where, $params);
        
        // Busca transações paginadas
        $transacoes = $db->find('financeiro', [
            'where' => $where,
            'params' => $params,
            'order' => 'data_transacao DESC',
            'limit' => $limit,
            'offset' => $offset
        ]);
        
        sendSuccess([
            'transacoes' => $transacoes,
            'paginacao' => [
                'pagina_atual' => $page,
                'total_itens' => $total,
                'itens_por_pagina' => $limit,
                'total_paginas' => ceil($total / $limit)
            ]
        ], 'Transações obtidas com sucesso', 200);
        break;
    
    /**
     * POST /api/financeiro
     * Cria nova transação
     * Requer: tipo, descricao, valor, data_transacao
     * Opcionais: categoria, cliente_id, referencia, status
     */
    case null:
        if ($method === 'POST') {
            // Valida entrada obrigatória
            if (empty($input['tipo'])) {
                sendError('Tipo é obrigatório', 400);
            }
            
            if (empty($input['descricao'])) {
                sendError('Descrição é obrigatória', 400);
            }
            
            if (empty($input['valor'])) {
                sendError('Valor é obrigatório', 400);
            }
            
            if (empty($input['data_transacao'])) {
                sendError('Data de transação é obrigatória', 400);
            }
            
            // Valida tipo
            if (!in_array($input['tipo'], ['receita', 'despesa'])) {
                sendError('Tipo deve ser "receita" ou "despesa"', 400);
            }
            
            // Valida valor
            if (!is_numeric($input['valor']) || floatval($input['valor']) <= 0) {
                sendError('Valor deve ser um número positivo', 400);
            }
            
            // Valida data
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $input['data_transacao'])) {
                sendError('Data deve estar no formato YYYY-MM-DD', 400);
            }
            
            $dados = [
                'tipo' => $input['tipo'],
                'descricao' => Validator::sanitizeString($input['descricao']),
                'valor' => floatval($input['valor']),
                'data_transacao' => $input['data_transacao'],
                'categoria' => isset($input['categoria']) ? Validator::sanitizeString($input['categoria']) : null,
                'cliente_id' => isset($input['cliente_id']) ? intval($input['cliente_id']) : null,
                'referencia' => isset($input['referencia']) ? Validator::sanitizeString($input['referencia']) : null,
                'status' => isset($input['status']) ? $input['status'] : 'pendente',
                'criado_em' => date('Y-m-d H:i:s'),
                'criado_por' => $usuario['id']
            ];
            
            // Valida cliente se fornecido
            if (!empty($dados['cliente_id'])) {
                $cliente = $db->queryOne(
                    "SELECT id FROM clientes WHERE id = ?",
                    [$dados['cliente_id']]
                );
                
                if (!$cliente) {
                    sendError('Cliente não encontrado', 404);
                }
            }
            
            // Insere no banco
            $transacaoId = $db->insert('financeiro', $dados);
            
            // Busca transação criada
            $transacao = $db->queryOne(
                "SELECT * FROM financeiro WHERE id = ?",
                [$transacaoId]
            );
            
            sendSuccess($transacao, 'Transação criada com sucesso', 201);
        }
        break;
    
    /**
     * GET /api/financeiro/:id
     * Obtém transação específica
     */
    default:
        if ($id === null || $id <= 0) {
            sendError('ID de transação inválido', 400);
        }
        
        if ($method === 'GET') {
            // Busca transação
            $transacao = $db->queryOne(
                "SELECT f.*, c.nome as cliente_nome
                 FROM financeiro f
                 LEFT JOIN clientes c ON f.cliente_id = c.id
                 WHERE f.id = ?",
                [$id]
            );
            
            if (!$transacao) {
                sendError('Transação não encontrada', 404);
            }
            
            sendSuccess($transacao, 'Transação obtida com sucesso', 200);
        }
        
        /**
         * PUT /api/financeiro/:id
         * Atualiza transação existente
         */
        elseif ($method === 'PUT') {
            // Busca transação existente
            $transacao = $db->queryOne(
                "SELECT * FROM financeiro WHERE id = ?",
                [$id]
            );
            
            if (!$transacao) {
                sendError('Transação não encontrada', 404);
            }
            
            // Prepara dados para atualização
            $dados = [];
            
            if (isset($input['descricao'])) {
                $dados['descricao'] = Validator::sanitizeString($input['descricao']);
            }
            
            if (isset($input['valor'])) {
                if (!is_numeric($input['valor']) || floatval($input['valor']) <= 0) {
                    sendError('Valor deve ser um número positivo', 400);
                }
                $dados['valor'] = floatval($input['valor']);
            }
            
            if (isset($input['status'])) {
                if (!in_array($input['status'], ['pendente', 'pago', 'cancelado'])) {
                    sendError('Status inválido', 400);
                }
                $dados['status'] = $input['status'];
            }
            
            if (isset($input['categoria'])) {
                $dados['categoria'] = empty($input['categoria']) ? null : Validator::sanitizeString($input['categoria']);
            }
            
            if (isset($input['referencia'])) {
                $dados['referencia'] = empty($input['referencia']) ? null : Validator::sanitizeString($input['referencia']);
            }
            
            if (isset($input['data_transacao'])) {
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $input['data_transacao'])) {
                    sendError('Data deve estar no formato YYYY-MM-DD', 400);
                }
                $dados['data_transacao'] = $input['data_transacao'];
            }
            
            $dados['atualizado_em'] = date('Y-m-d H:i:s');
            $dados['atualizado_por'] = $usuario['id'];
            
            if (empty($dados)) {
                sendError('Nenhum dado para atualizar', 400);
            }
            
            // Atualiza no banco
            $db->update('financeiro', $dados, 'id = ?', [$id]);
            
            // Busca transação atualizada
            $transacaoAtualizada = $db->queryOne(
                "SELECT * FROM financeiro WHERE id = ?",
                [$id]
            );
            
            sendSuccess($transacaoAtualizada, 'Transação atualizada com sucesso', 200);
        }
        
        /**
         * DELETE /api/financeiro/:id
         * Deleta transação
         */
        elseif ($method === 'DELETE') {
            // Busca transação
            $transacao = $db->queryOne(
                "SELECT * FROM financeiro WHERE id = ?",
                [$id]
            );
            
            if (!$transacao) {
                sendError('Transação não encontrada', 404);
            }
            
            // Deleta transação
            $db->delete('financeiro', 'id = ?', [$id]);
            
            sendSuccess([], 'Transação deletada com sucesso', 200);
        }
        
        else {
            sendError('Método não permitido', 405);
        }
        break;
}
?>
