<?php
/**
 * ARQUIVO: preventivas.php
 * 
 * Função: Endpoints CRUD de gerenciamento de contratos de manutenção preventiva
 * Entrada: Dados de contrato, checklists, parâmetros de busca, paginação
 * Processamento: Cria, lê, atualiza, deleta contratos, gerencia checklists de manutenção
 * Saída: Dados de contrato(s), checklists, lista paginada, mensagens de sucesso/erro
 * 
 * Endpoints disponíveis:
 * - GET /api/preventivas - Lista contratos de manutenção preventiva (com paginação)
 * - GET /api/preventivas/:id - Obtém contrato com seus checklists
 * - POST /api/preventivas - Cria novo contrato de manutenção
 * - PUT /api/preventivas/:id - Atualiza contrato existente
 * - DELETE /api/preventivas/:id - Deleta contrato
 * - POST /api/preventivas/:id/checklist - Adiciona item ao checklist do contrato
 * - PUT /api/preventivas/checklist/:id - Atualiza item do checklist
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
     * GET /api/preventivas
     * Lista contratos de manutenção preventiva com paginação
     * Parâmetros: page, limit, status, cliente_id, equipamento_id
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
        $status = isset($_GET['status']) ? $_GET['status'] : null;
        $cliente_id = isset($_GET['cliente_id']) ? intval($_GET['cliente_id']) : null;
        $equipamento_id = isset($_GET['equipamento_id']) ? intval($_GET['equipamento_id']) : null;
        
        $offset = ($page - 1) * $limit;
        
        // Constrói cláusula WHERE para filtros
        $where = '1=1';
        $params = [];
        
        if (!is_null($status) && in_array($status, ['ativo', 'inativo', 'expirado'])) {
            $where .= ' AND status = ?';
            $params[] = $status;
        }
        
        if (!is_null($cliente_id) && $cliente_id > 0) {
            $where .= ' AND cliente_id = ?';
            $params[] = $cliente_id;
        }
        
        if (!is_null($equipamento_id) && $equipamento_id > 0) {
            $where .= ' AND equipamento_id = ?';
            $params[] = $equipamento_id;
        }
        
        // Conta total de contratos
        $total = $db->count('preventivas_contratos', $where, $params);
        
        // Busca contratos paginados
        $contratos = $db->find('preventivas_contratos', [
            'where' => $where,
            'params' => $params,
            'order' => 'criado_em DESC',
            'limit' => $limit,
            'offset' => $offset
        ]);
        
        sendSuccess([
            'contratos' => $contratos,
            'paginacao' => [
                'pagina_atual' => $page,
                'total_itens' => $total,
                'itens_por_pagina' => $limit,
                'total_paginas' => ceil($total / $limit)
            ]
        ], 'Contratos de manutenção obtidos com sucesso', 200);
        break;
    
    /**
     * POST /api/preventivas
     * Cria novo contrato de manutenção preventiva
     * Requer: cliente_id, equipamento_id, frequencia_dias, descricao
     * Opcionais: data_inicio, valor_mensal
     */
    case null:
        if ($method === 'POST') {
            // Valida entrada obrigatória
            if (empty($input['cliente_id'])) {
                sendError('ID do cliente é obrigatório', 400);
            }
            
            if (empty($input['equipamento_id'])) {
                sendError('ID do equipamento é obrigatório', 400);
            }
            
            if (empty($input['frequencia_dias'])) {
                sendError('Frequência em dias é obrigatória', 400);
            }
            
            if (empty($input['descricao'])) {
                sendError('Descrição é obrigatória', 400);
            }
            
            // Valida frequência
            $frequencia_dias = intval($input['frequencia_dias']);
            if ($frequencia_dias <= 0 || $frequencia_dias > 365) {
                sendError('Frequência deve estar entre 1 e 365 dias', 400);
            }
            
            // Verifica se cliente existe
            $cliente = $db->queryOne(
                "SELECT id FROM clientes WHERE id = ?",
                [$input['cliente_id']]
            );
            
            if (!$cliente) {
                sendError('Cliente não encontrado', 404);
            }
            
            // Verifica se equipamento existe (se tabela existir)
            // Assumindo que equipamentos podem estar em outra tabela
            
            $dados = [
                'cliente_id' => intval($input['cliente_id']),
                'equipamento_id' => intval($input['equipamento_id']),
                'frequencia_dias' => $frequencia_dias,
                'descricao' => Validator::sanitizeString($input['descricao']),
                'data_inicio' => isset($input['data_inicio']) ? $input['data_inicio'] : date('Y-m-d'),
                'valor_mensal' => isset($input['valor_mensal']) ? floatval($input['valor_mensal']) : null,
                'status' => 'ativo',
                'proxima_manutencao' => date('Y-m-d', strtotime("+{$frequencia_dias} days")),
                'criado_em' => date('Y-m-d H:i:s'),
                'criado_por' => $usuario['id']
            ];
            
            // Insere no banco
            $contratoId = $db->insert('preventivas_contratos', $dados);
            
            // Busca contrato criado
            $contrato = $db->queryOne(
                "SELECT * FROM preventivas_contratos WHERE id = ?",
                [$contratoId]
            );
            
            sendSuccess($contrato, 'Contrato de manutenção criado com sucesso', 201);
        }
        break;
    
    /**
     * GET /api/preventivas/:id
     * Obtém contrato com seus checklists
     */
    default:
        if ($id === null || $id <= 0) {
            sendError('ID de contrato inválido', 400);
        }
        
        if (!empty($acao)) {
            
            /**
             * POST /api/preventivas/:id/checklist
             * Adiciona item ao checklist do contrato
             * Requer: titulo, descricao
             * Opcionais: concluido
             */
            if ($acao === 'checklist' && $method === 'POST') {
                // Valida entrada
                if (empty($input['titulo'])) {
                    sendError('Título do item é obrigatório', 400);
                }
                
                if (empty($input['descricao'])) {
                    sendError('Descrição é obrigatória', 400);
                }
                
                // Verifica se contrato existe
                $contrato = $db->queryOne(
                    "SELECT id FROM preventivas_contratos WHERE id = ?",
                    [$id]
                );
                
                if (!$contrato) {
                    sendError('Contrato não encontrado', 404);
                }
                
                $dados = [
                    'contrato_id' => $id,
                    'titulo' => Validator::sanitizeString($input['titulo']),
                    'descricao' => Validator::sanitizeString($input['descricao']),
                    'concluido' => isset($input['concluido']) ? intval($input['concluido']) ? 1 : 0 : 0,
                    'criado_em' => date('Y-m-d H:i:s'),
                    'criado_por' => $usuario['id']
                ];
                
                // Insere item
                $itemId = $db->insert('preventivas_checklists', $dados);
                
                // Busca item criado
                $item = $db->queryOne(
                    "SELECT * FROM preventivas_checklists WHERE id = ?",
                    [$itemId]
                );
                
                sendSuccess($item, 'Item de checklist adicionado com sucesso', 201);
            }
            
            else {
                sendError('Ação não encontrada', 404);
            }
        } else {
            
            if ($method === 'GET') {
                // Busca contrato
                $contrato = $db->queryOne(
                    "SELECT pc.*, c.nome as cliente_nome
                     FROM preventivas_contratos pc
                     LEFT JOIN clientes c ON pc.cliente_id = c.id
                     WHERE pc.id = ?",
                    [$id]
                );
                
                if (!$contrato) {
                    sendError('Contrato não encontrado', 404);
                }
                
                // Busca checklists associados
                $checklists = $db->find('preventivas_checklists', [
                    'where' => 'contrato_id = ?',
                    'params' => [$id],
                    'order' => 'criado_em DESC'
                ]);
                
                $contrato['checklists'] = $checklists;
                
                sendSuccess($contrato, 'Contrato obtido com sucesso', 200);
            }
            
            /**
             * PUT /api/preventivas/:id
             * Atualiza contrato de manutenção
             */
            elseif ($method === 'PUT') {
                // Busca contrato existente
                $contrato = $db->queryOne(
                    "SELECT * FROM preventivas_contratos WHERE id = ?",
                    [$id]
                );
                
                if (!$contrato) {
                    sendError('Contrato não encontrado', 404);
                }
                
                // Prepara dados para atualização
                $dados = [];
                
                if (isset($input['frequencia_dias'])) {
                    $frequencia_dias = intval($input['frequencia_dias']);
                    if ($frequencia_dias <= 0 || $frequencia_dias > 365) {
                        sendError('Frequência deve estar entre 1 e 365 dias', 400);
                    }
                    $dados['frequencia_dias'] = $frequencia_dias;
                }
                
                if (isset($input['descricao'])) {
                    $dados['descricao'] = Validator::sanitizeString($input['descricao']);
                }
                
                if (isset($input['status'])) {
                    if (!in_array($input['status'], ['ativo', 'inativo', 'expirado'])) {
                        sendError('Status inválido', 400);
                    }
                    $dados['status'] = $input['status'];
                }
                
                if (isset($input['valor_mensal'])) {
                    $dados['valor_mensal'] = empty($input['valor_mensal']) ? null : floatval($input['valor_mensal']);
                }
                
                if (isset($input['proxima_manutencao'])) {
                    $dados['proxima_manutencao'] = $input['proxima_manutencao'];
                }
                
                $dados['atualizado_em'] = date('Y-m-d H:i:s');
                $dados['atualizado_por'] = $usuario['id'];
                
                if (empty($dados)) {
                    sendError('Nenhum dado para atualizar', 400);
                }
                
                // Atualiza no banco
                $db->update('preventivas_contratos', $dados, 'id = ?', [$id]);
                
                // Busca contrato atualizado
                $contratoAtualizado = $db->queryOne(
                    "SELECT * FROM preventivas_contratos WHERE id = ?",
                    [$id]
                );
                
                sendSuccess($contratoAtualizado, 'Contrato atualizado com sucesso', 200);
            }
            
            /**
             * DELETE /api/preventivas/:id
             * Deleta contrato de manutenção
             */
            elseif ($method === 'DELETE') {
                // Busca contrato
                $contrato = $db->queryOne(
                    "SELECT * FROM preventivas_contratos WHERE id = ?",
                    [$id]
                );
                
                if (!$contrato) {
                    sendError('Contrato não encontrado', 404);
                }
                
                // Deleta checklists associados
                $db->delete('preventivas_checklists', 'contrato_id = ?', [$id]);
                
                // Deleta contrato
                $db->delete('preventivas_contratos', 'id = ?', [$id]);
                
                sendSuccess([], 'Contrato deletado com sucesso', 200);
            }
            
            else {
                sendError('Método não permitido', 405);
            }
        }
        break;
}

// Rota adicional para atualizar item de checklist
if ($subroute === 'checklist' && $id !== null) {
    /**
     * PUT /api/preventivas/checklist/:id
     * Atualiza item do checklist
     */
    if ($method === 'PUT') {
        $checklistId = $id;
        
        // Busca item
        $item = $db->queryOne(
            "SELECT * FROM preventivas_checklists WHERE id = ?",
            [$checklistId]
        );
        
        if (!$item) {
            sendError('Item de checklist não encontrado', 404);
        }
        
        // Prepara dados para atualização
        $dados = [];
        
        if (isset($input['titulo'])) {
            $dados['titulo'] = Validator::sanitizeString($input['titulo']);
        }
        
        if (isset($input['descricao'])) {
            $dados['descricao'] = Validator::sanitizeString($input['descricao']);
        }
        
        if (isset($input['concluido'])) {
            $dados['concluido'] = intval($input['concluido']) ? 1 : 0;
        }
        
        $dados['atualizado_em'] = date('Y-m-d H:i:s');
        $dados['atualizado_por'] = $usuario['id'];
        
        if (empty($dados)) {
            sendError('Nenhum dado para atualizar', 400);
        }
        
        // Atualiza no banco
        $db->update('preventivas_checklists', $dados, 'id = ?', [$checklistId]);
        
        // Busca item atualizado
        $itemAtualizado = $db->queryOne(
            "SELECT * FROM preventivas_checklists WHERE id = ?",
            [$checklistId]
        );
        
        sendSuccess($itemAtualizado, 'Item de checklist atualizado com sucesso', 200);
    } else {
        sendError('Método não permitido', 405);
    }
}
?>
