<?php
/**
 * ARQUIVO: agendamentos.php
 * 
 * Função: Endpoints de gerenciamento de agendamentos e calendário
 * Entrada: Dados de agendamento, parâmetros de busca, datas
 * Processamento: Cria, lê, atualiza, deleta, busca agendamentos e verifica disponibilidade
 * Saída: Dados de agendamento(s), calendário, disponibilidade, mensagens de sucesso/erro
 * 
 * Endpoints disponíveis:
 * - GET /api/agendamentos - Lista todos os agendamentos
 * - GET /api/agendamentos/:id - Obtém um agendamento específico
 * - POST /api/agendamentos - Cria novo agendamento
 * - PUT /api/agendamentos/:id - Atualiza agendamento
 * - DELETE /api/agendamentos/:id - Deleta agendamento
 * - GET /api/agendamentos/disponibilidade - Verifica disponibilidade de horários
 * - GET /api/agendamentos/calendario - Visualização em calendário
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
     * GET /api/agendamentos
     * Lista todos os agendamentos com filtros
     * Parâmetros: page, limit, order, status, data_inicio, data_fim, cliente_id, tecnico_id
     */
    case '':
    case null:
        if ($method !== 'GET') {
            sendError('Método não permitido', 405);
        }
        
        // Obtém parâmetros de paginação
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : ITEMS_PER_PAGE;
        $order = isset($_GET['order']) ? $_GET['order'] : 'data_agendamento ASC';
        
        // Obtém filtros
        $status = isset($_GET['status']) ? $_GET['status'] : null;
        $data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : null;
        $data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : null;
        $cliente_id = isset($_GET['cliente_id']) ? intval($_GET['cliente_id']) : null;
        $tecnico_id = isset($_GET['tecnico_id']) ? intval($_GET['tecnico_id']) : null;
        
        // Sanitiza ORDER BY
        $orderField = 'data_agendamento';
        $orderDir = 'ASC';
        
        if (preg_match('/^(data_agendamento|status|cliente_nome|tecnico_nome|criado_em)\s+(ASC|DESC)$/i', $order, $matches)) {
            $orderField = $matches[1];
            $orderDir = strtoupper($matches[2]);
        }
        
        $offset = ($page - 1) * $limit;
        
        // Constrói cláusula WHERE
        $where = '1=1';
        $params = [];
        
        if (!is_null($status) && in_array($status, ['agendado', 'confirmado', 'cancelado', 'concluido', 'nao_compareceu'])) {
            $where .= ' AND a.status = ?';
            $params[] = $status;
        }
        
        if (!is_null($data_inicio)) {
            $where .= ' AND a.data_agendamento >= ?';
            $params[] = $data_inicio . ' 00:00:00';
        }
        
        if (!is_null($data_fim)) {
            $where .= ' AND a.data_agendamento <= ?';
            $params[] = $data_fim . ' 23:59:59';
        }
        
        if (!is_null($cliente_id) && $cliente_id > 0) {
            $where .= ' AND a.cliente_id = ?';
            $params[] = $cliente_id;
        }
        
        if (!is_null($tecnico_id) && $tecnico_id > 0) {
            $where .= ' AND a.tecnico_id = ?';
            $params[] = $tecnico_id;
        }
        
        // Conta total de agendamentos
        $total = $db->count('agendamentos a', $where, $params);
        
        // Busca agendamentos paginados
        $agendamentos = $db->find('agendamentos a', [
            'select' => 'a.*, c.nome as cliente_nome, c.telefone as cliente_telefone, u.nome as tecnico_nome',
            'leftJoin' => 'clientes c ON a.cliente_id = c.id',
            'leftJoin' => 'usuarios u ON a.tecnico_id = u.id',
            'where' => $where,
            'params' => $params,
            'order' => "a.{$orderField} {$orderDir}",
            'limit' => $limit,
            'offset' => $offset
        ]);
        
        sendSuccess([
            'agendamentos' => $agendamentos,
            'paginacao' => [
                'pagina_atual' => $page,
                'total_itens' => $total,
                'itens_por_pagina' => $limit,
                'total_paginas' => ceil($total / $limit)
            ]
        ], 'Agendamentos obtidos com sucesso', 200);
        break;
    
    /**
     * GET /api/agendamentos/disponibilidade
     * Verifica disponibilidade de horários
     * Parâmetros: data, duracao (em minutos), tecnico_id (opcional)
     */
    case 'disponibilidade':
        if ($method !== 'GET') {
            sendError('Método não permitido', 405);
        }
        
        $data = isset($_GET['data']) ? $_GET['data'] : null;
        $duracao = isset($_GET['duracao']) ? intval($_GET['duracao']) : 60;
        $tecnico_id = isset($_GET['tecnico_id']) ? intval($_GET['tecnico_id']) : null;
        
        if (empty($data)) {
            sendError('Data é obrigatória', 400);
        }
        
        // Valida formato da data
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
            sendError('Formato de data inválido (use YYYY-MM-DD)', 400);
        }
        
        // Horários disponíveis (customizar conforme necessário)
        $horarios = [
            '08:00', '08:30', '09:00', '09:30', '10:00', '10:30',
            '11:00', '11:30', '14:00', '14:30', '15:00', '15:30',
            '16:00', '16:30', '17:00', '17:30'
        ];
        
        $disponibilidade = [];
        
        foreach ($horarios as $horario) {
            $dataHora = $data . ' ' . $horario;
            $dataHoraFim = date('Y-m-d H:i:s', strtotime($dataHora) + ($duracao * 60));
            
            // Verifica se há conflito
            $query = "SELECT COUNT(*) as total FROM agendamentos 
                      WHERE DATE(data_agendamento) = ? 
                      AND status NOT IN ('cancelado')";
            
            $params = [$data];
            
            if ($tecnico_id > 0) {
                $query .= " AND tecnico_id = ?";
                $params[] = $tecnico_id;
            }
            
            // Verificar colisão de horários
            $query .= " AND (
                (data_agendamento <= ? AND DATE_ADD(data_agendamento, INTERVAL ? MINUTE) > ?)
            )";
            $params[] = $dataHora;
            $params[] = $duracao;
            $params[] = $dataHora;
            
            $conflito = $db->queryOne($query, $params);
            
            $disponibilidade[] = [
                'horario' => $horario,
                'disponivel' => $conflito['total'] == 0
            ];
        }
        
        sendSuccess([
            'data' => $data,
            'duracao_minutos' => $duracao,
            'horarios' => $disponibilidade
        ], 'Disponibilidade consultada com sucesso', 200);
        break;
    
    /**
     * GET /api/agendamentos/calendario
     * Visualização em calendário
     * Parâmetros: mes, ano, tecnico_id (opcional)
     */
    case 'calendario':
        if ($method !== 'GET') {
            sendError('Método não permitido', 405);
        }
        
        $mes = isset($_GET['mes']) ? intval($_GET['mes']) : intval(date('m'));
        $ano = isset($_GET['ano']) ? intval($_GET['ano']) : intval(date('Y'));
        $tecnico_id = isset($_GET['tecnico_id']) ? intval($_GET['tecnico_id']) : null;
        
        if ($mes < 1 || $mes > 12) {
            sendError('Mês deve ser entre 1 e 12', 400);
        }
        
        $dataInicio = $ano . '-' . str_pad($mes, 2, '0', STR_PAD_LEFT) . '-01';
        $dataFim = date('Y-m-d', strtotime('last day of ' . $dataInicio));
        
        // Busca agendamentos do mês
        $where = "DATE(a.data_agendamento) >= ? AND DATE(a.data_agendamento) <= ?";
        $params = [$dataInicio, $dataFim];
        
        if ($tecnico_id > 0) {
            $where .= " AND a.tecnico_id = ?";
            $params[] = $tecnico_id;
        }
        
        $agendamentos = $db->find('agendamentos a', [
            'select' => 'a.*, c.nome as cliente_nome, u.nome as tecnico_nome',
            'leftJoin' => 'clientes c ON a.cliente_id = c.id',
            'leftJoin' => 'usuarios u ON a.tecnico_id = u.id',
            'where' => $where,
            'params' => $params,
            'order' => 'a.data_agendamento ASC'
        ]);
        
        // Agrupa por data
        $calendario = [];
        foreach ($agendamentos as $agendamento) {
            $data = date('Y-m-d', strtotime($agendamento['data_agendamento']));
            $dia = intval(date('d', strtotime($agendamento['data_agendamento'])));
            
            if (!isset($calendario[$dia])) {
                $calendario[$dia] = [];
            }
            
            $calendario[$dia][] = $agendamento;
        }
        
        sendSuccess([
            'mes' => $mes,
            'ano' => $ano,
            'calendario' => $calendario
        ], 'Calendário obtido com sucesso', 200);
        break;
    
    /**
     * POST /api/agendamentos
     * Cria novo agendamento
     * Requer: cliente_id, data_agendamento, tecnico_id
     * Opcionais: descricao, servico_id, observacoes
     */
    default:
        if ($id === null || $id <= 0) {
            if ($method === 'POST') {
                // Valida entrada
                if (empty($input['cliente_id'])) {
                    sendError('ID do cliente é obrigatório', 400);
                }
                
                if (empty($input['data_agendamento'])) {
                    sendError('Data de agendamento é obrigatória', 400);
                }
                
                if (empty($input['tecnico_id'])) {
                    sendError('ID do técnico é obrigatório', 400);
                }
                
                $cliente_id = intval($input['cliente_id']);
                $tecnico_id = intval($input['tecnico_id']);
                
                // Verifica se cliente existe
                $cliente = $db->queryOne(
                    "SELECT id FROM clientes WHERE id = ?",
                    [$cliente_id]
                );
                
                if (!$cliente) {
                    sendError('Cliente não encontrado', 404);
                }
                
                // Verifica se técnico existe
                $tecnico = $db->queryOne(
                    "SELECT id FROM usuarios WHERE id = ? AND papel IN ('tecnico', 'admin')",
                    [$tecnico_id]
                );
                
                if (!$tecnico) {
                    sendError('Técnico não encontrado', 404);
                }
                
                // Valida data
                if (!preg_match('/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}$/', $input['data_agendamento'])) {
                    sendError('Formato de data inválido (use YYYY-MM-DD HH:MM:SS)', 400);
                }
                
                // Verifica se data é no futuro
                if (strtotime($input['data_agendamento']) < time()) {
                    sendError('Data de agendamento deve ser no futuro', 400);
                }
                
                // Verifica conflito de agendamento
                $conflito = $db->queryOne(
                    "SELECT id FROM agendamentos 
                     WHERE data_agendamento = ? AND tecnico_id = ? AND status != 'cancelado'",
                    [$input['data_agendamento'], $tecnico_id]
                );
                
                if ($conflito) {
                    sendError('Técnico já possui agendamento neste horário', 400);
                }
                
                // Prepara dados
                $dados = [
                    'cliente_id' => $cliente_id,
                    'tecnico_id' => $tecnico_id,
                    'data_agendamento' => $input['data_agendamento'],
                    'status' => 'agendado',
                    'descricao' => isset($input['descricao']) ? Validator::sanitizeString($input['descricao']) : null,
                    'servico_id' => isset($input['servico_id']) ? intval($input['servico_id']) : null,
                    'observacoes' => isset($input['observacoes']) ? Validator::sanitizeString($input['observacoes']) : null,
                    'criado_em' => date('Y-m-d H:i:s'),
                    'criado_por' => $usuario['id']
                ];
                
                // Insere agendamento
                $agendamentoId = $db->insert('agendamentos', $dados);
                
                // Busca agendamento criado
                $agendamento = $db->queryOne(
                    "SELECT a.*, c.nome as cliente_nome, u.nome as tecnico_nome 
                     FROM agendamentos a 
                     LEFT JOIN clientes c ON a.cliente_id = c.id 
                     LEFT JOIN usuarios u ON a.tecnico_id = u.id 
                     WHERE a.id = ?",
                    [$agendamentoId]
                );
                
                sendSuccess($agendamento, 'Agendamento criado com sucesso', 201);
            } else {
                sendError('Método não permitido', 405);
            }
        } else {
            if ($method === 'GET') {
                /**
                 * GET /api/agendamentos/:id
                 * Obtém um agendamento específico
                 */
                $agendamento = $db->queryOne(
                    "SELECT a.*, c.nome as cliente_nome, c.telefone, c.email,
                            u.nome as tecnico_nome, s.nome as servico_nome
                     FROM agendamentos a 
                     LEFT JOIN clientes c ON a.cliente_id = c.id 
                     LEFT JOIN usuarios u ON a.tecnico_id = u.id 
                     LEFT JOIN servicos s ON a.servico_id = s.id 
                     WHERE a.id = ?",
                    [$id]
                );
                
                if (!$agendamento) {
                    sendError('Agendamento não encontrado', 404);
                }
                
                sendSuccess($agendamento, 'Agendamento obtido com sucesso', 200);
            }
            
            /**
             * PUT /api/agendamentos/:id
             * Atualiza agendamento
             */
            elseif ($method === 'PUT') {
                $agendamento = $db->queryOne(
                    "SELECT * FROM agendamentos WHERE id = ?",
                    [$id]
                );
                
                if (!$agendamento) {
                    sendError('Agendamento não encontrado', 404);
                }
                
                $dados = [];
                
                if (isset($input['data_agendamento'])) {
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}$/', $input['data_agendamento'])) {
                        sendError('Formato de data inválido (use YYYY-MM-DD HH:MM:SS)', 400);
                    }
                    
                    if (strtotime($input['data_agendamento']) < time()) {
                        sendError('Data de agendamento deve ser no futuro', 400);
                    }
                    
                    $dados['data_agendamento'] = $input['data_agendamento'];
                }
                
                if (isset($input['status'])) {
                    if (!in_array($input['status'], ['agendado', 'confirmado', 'cancelado', 'concluido', 'nao_compareceu'])) {
                        sendError('Status inválido', 400);
                    }
                    $dados['status'] = $input['status'];
                }
                
                if (isset($input['descricao'])) {
                    $dados['descricao'] = empty($input['descricao']) ? null : Validator::sanitizeString($input['descricao']);
                }
                
                if (isset($input['observacoes'])) {
                    $dados['observacoes'] = empty($input['observacoes']) ? null : Validator::sanitizeString($input['observacoes']);
                }
                
                if (isset($input['tecnico_id'])) {
                    $tecnico_id = intval($input['tecnico_id']);
                    $tecnico = $db->queryOne(
                        "SELECT id FROM usuarios WHERE id = ? AND papel IN ('tecnico', 'admin')",
                        [$tecnico_id]
                    );
                    
                    if (!$tecnico) {
                        sendError('Técnico não encontrado', 404);
                    }
                    
                    $dados['tecnico_id'] = $tecnico_id;
                }
                
                $dados['atualizado_em'] = date('Y-m-d H:i:s');
                $dados['atualizado_por'] = $usuario['id'];
                
                if (empty($dados)) {
                    sendError('Nenhum dado para atualizar', 400);
                }
                
                $db->update('agendamentos', $dados, 'id = ?', [$id]);
                
                $agendamentoAtualizado = $db->queryOne(
                    "SELECT a.*, c.nome as cliente_nome, u.nome as tecnico_nome 
                     FROM agendamentos a 
                     LEFT JOIN clientes c ON a.cliente_id = c.id 
                     LEFT JOIN usuarios u ON a.tecnico_id = u.id 
                     WHERE a.id = ?",
                    [$id]
                );
                
                sendSuccess($agendamentoAtualizado, 'Agendamento atualizado com sucesso', 200);
            }
            
            /**
             * DELETE /api/agendamentos/:id
             * Deleta agendamento
             */
            elseif ($method === 'DELETE') {
                $agendamento = $db->queryOne(
                    "SELECT * FROM agendamentos WHERE id = ?",
                    [$id]
                );
                
                if (!$agendamento) {
                    sendError('Agendamento não encontrado', 404);
                }
                
                $db->delete('agendamentos', 'id = ?', [$id]);
                
                sendSuccess([], 'Agendamento deletado com sucesso', 200);
            }
            
            else {
                sendError('Método não permitido', 405);
            }
        }
        break;
}
?>
