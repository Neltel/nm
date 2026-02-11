<?php
/**
 * ARQUIVO: pmp.php
 * 
 * Função: Endpoints CRUD de gerenciamento de contratos PMP (Plano de Manutenção Preventiva)
 * Entrada: Dados de contrato, equipamentos, checklists, parâmetros de busca, paginação
 * Processamento: Cria, lê, atualiza, deleta contratos, gerencia equipamentos, checklists com IA
 * Saída: Dados de contrato(s), equipamentos, checklists gerados com IA, mensagens de sucesso/erro
 * 
 * Endpoints disponíveis:
 * - GET /api/pmp/contratos - Lista contratos PMP (com paginação)
 * - GET /api/pmp/contratos/:id - Obtém contrato com seus equipamentos
 * - POST /api/pmp/contratos - Cria novo contrato PMP
 * - PUT /api/pmp/contratos/:id - Atualiza contrato PMP
 * - POST /api/pmp/contratos/:id/equipamentos - Adiciona equipamento ao contrato
 * - POST /api/pmp/contratos/:id/checklists - Cria execução de checklist
 * - POST /api/pmp/checklists/:id/ia - Gera itens do checklist com IA
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
$recurso = isset($parts[2]) ? $parts[2] : '';
$id = isset($parts[3]) ? intval($parts[3]) : null;
$acao = isset($parts[4]) ? $parts[4] : '';

// Roteia para ação apropriada
switch ($subroute) {
    
    /**
     * GET /api/pmp/contratos
     * Lista contratos PMP com paginação
     * Parâmetros: page, limit, status, cliente_id
     */
    case 'contratos':
        if ($recurso === '' || $recurso === null) {
            
            if ($method !== 'GET' && $method !== 'POST') {
                sendError('Método não permitido', 405);
            }
            
            // GET - Lista contratos
            if ($method === 'GET') {
                $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
                $limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : ITEMS_PER_PAGE;
                
                $status = isset($_GET['status']) ? $_GET['status'] : null;
                $cliente_id = isset($_GET['cliente_id']) ? intval($_GET['cliente_id']) : null;
                
                $offset = ($page - 1) * $limit;
                
                $where = '1=1';
                $params = [];
                
                if (!is_null($status) && in_array($status, ['ativo', 'inativo', 'encerrado'])) {
                    $where .= ' AND status = ?';
                    $params[] = $status;
                }
                
                if (!is_null($cliente_id) && $cliente_id > 0) {
                    $where .= ' AND cliente_id = ?';
                    $params[] = $cliente_id;
                }
                
                $total = $db->count('pmp_contratos', $where, $params);
                
                $contratos = $db->find('pmp_contratos', [
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
                ], 'Contratos PMP obtidos com sucesso', 200);
            }
            
            // POST - Cria novo contrato
            else {
                if (empty($input['cliente_id'])) {
                    sendError('ID do cliente é obrigatório', 400);
                }
                
                if (empty($input['numero_contrato'])) {
                    sendError('Número do contrato é obrigatório', 400);
                }
                
                if (empty($input['descricao'])) {
                    sendError('Descrição é obrigatória', 400);
                }
                
                // Verifica se cliente existe
                $cliente = $db->queryOne(
                    "SELECT id FROM clientes WHERE id = ?",
                    [$input['cliente_id']]
                );
                
                if (!$cliente) {
                    sendError('Cliente não encontrado', 404);
                }
                
                $dados = [
                    'cliente_id' => intval($input['cliente_id']),
                    'numero_contrato' => Validator::sanitizeString($input['numero_contrato']),
                    'descricao' => Validator::sanitizeString($input['descricao']),
                    'data_inicio' => isset($input['data_inicio']) ? $input['data_inicio'] : date('Y-m-d'),
                    'data_fim' => isset($input['data_fim']) ? $input['data_fim'] : null,
                    'valor_mensal' => isset($input['valor_mensal']) ? floatval($input['valor_mensal']) : null,
                    'status' => 'ativo',
                    'criado_em' => date('Y-m-d H:i:s'),
                    'criado_por' => $usuario['id']
                ];
                
                $contratoId = $db->insert('pmp_contratos', $dados);
                
                $contrato = $db->queryOne(
                    "SELECT * FROM pmp_contratos WHERE id = ?",
                    [$contratoId]
                );
                
                sendSuccess($contrato, 'Contrato PMP criado com sucesso', 201);
            }
        }
        
        // GET /api/pmp/contratos/:id
        // PUT /api/pmp/contratos/:id
        // POST /api/pmp/contratos/:id/equipamentos
        // POST /api/pmp/contratos/:id/checklists
        elseif (is_numeric($recurso)) {
            $contratoId = intval($recurso);
            
            if (!empty($acao)) {
                
                /**
                 * POST /api/pmp/contratos/:id/equipamentos
                 * Adiciona equipamento ao contrato
                 */
                if ($acao === 'equipamentos' && $method === 'POST') {
                    // Valida entrada
                    if (empty($input['nome'])) {
                        sendError('Nome do equipamento é obrigatório', 400);
                    }
                    
                    if (empty($input['modelo'])) {
                        sendError('Modelo é obrigatório', 400);
                    }
                    
                    // Verifica se contrato existe
                    $contrato = $db->queryOne(
                        "SELECT id FROM pmp_contratos WHERE id = ?",
                        [$contratoId]
                    );
                    
                    if (!$contrato) {
                        sendError('Contrato não encontrado', 404);
                    }
                    
                    $dados = [
                        'contrato_id' => $contratoId,
                        'nome' => Validator::sanitizeString($input['nome']),
                        'modelo' => Validator::sanitizeString($input['modelo']),
                        'numero_serie' => isset($input['numero_serie']) ? Validator::sanitizeString($input['numero_serie']) : null,
                        'localizacao' => isset($input['localizacao']) ? Validator::sanitizeString($input['localizacao']) : null,
                        'criado_em' => date('Y-m-d H:i:s'),
                        'criado_por' => $usuario['id']
                    ];
                    
                    $equipamentoId = $db->insert('pmp_equipamentos', $dados);
                    
                    $equipamento = $db->queryOne(
                        "SELECT * FROM pmp_equipamentos WHERE id = ?",
                        [$equipamentoId]
                    );
                    
                    sendSuccess($equipamento, 'Equipamento adicionado com sucesso', 201);
                }
                
                /**
                 * POST /api/pmp/contratos/:id/checklists
                 * Cria execução de checklist
                 */
                elseif ($acao === 'checklists' && $method === 'POST') {
                    // Valida entrada
                    if (empty($input['equipamento_id'])) {
                        sendError('ID do equipamento é obrigatório', 400);
                    }
                    
                    // Verifica se contrato existe
                    $contrato = $db->queryOne(
                        "SELECT id FROM pmp_contratos WHERE id = ?",
                        [$contratoId]
                    );
                    
                    if (!$contrato) {
                        sendError('Contrato não encontrado', 404);
                    }
                    
                    // Verifica se equipamento existe
                    $equipamento = $db->queryOne(
                        "SELECT id FROM pmp_equipamentos WHERE id = ? AND contrato_id = ?",
                        [$input['equipamento_id'], $contratoId]
                    );
                    
                    if (!$equipamento) {
                        sendError('Equipamento não encontrado no contrato', 404);
                    }
                    
                    $dados = [
                        'contrato_id' => $contratoId,
                        'equipamento_id' => intval($input['equipamento_id']),
                        'data_execucao' => isset($input['data_execucao']) ? $input['data_execucao'] : date('Y-m-d'),
                        'observacoes' => isset($input['observacoes']) ? Validator::sanitizeString($input['observacoes']) : null,
                        'criado_em' => date('Y-m-d H:i:s'),
                        'criado_por' => $usuario['id']
                    ];
                    
                    $checklistId = $db->insert('pmp_checklists_execucao', $dados);
                    
                    $checklist = $db->queryOne(
                        "SELECT * FROM pmp_checklists_execucao WHERE id = ?",
                        [$checklistId]
                    );
                    
                    sendSuccess($checklist, 'Execução de checklist criada com sucesso', 201);
                }
                
                else {
                    sendError('Ação não encontrada', 404);
                }
            } else {
                
                if ($method === 'GET') {
                    // Busca contrato
                    $contrato = $db->queryOne(
                        "SELECT pc.*, c.nome as cliente_nome
                         FROM pmp_contratos pc
                         LEFT JOIN clientes c ON pc.cliente_id = c.id
                         WHERE pc.id = ?",
                        [$contratoId]
                    );
                    
                    if (!$contrato) {
                        sendError('Contrato não encontrado', 404);
                    }
                    
                    // Busca equipamentos
                    $equipamentos = $db->find('pmp_equipamentos', [
                        'where' => 'contrato_id = ?',
                        'params' => [$contratoId],
                        'order' => 'criado_em DESC'
                    ]);
                    
                    $contrato['equipamentos'] = $equipamentos;
                    
                    sendSuccess($contrato, 'Contrato PMP obtido com sucesso', 200);
                }
                
                /**
                 * PUT /api/pmp/contratos/:id
                 * Atualiza contrato PMP
                 */
                elseif ($method === 'PUT') {
                    // Busca contrato
                    $contrato = $db->queryOne(
                        "SELECT * FROM pmp_contratos WHERE id = ?",
                        [$contratoId]
                    );
                    
                    if (!$contrato) {
                        sendError('Contrato não encontrado', 404);
                    }
                    
                    $dados = [];
                    
                    if (isset($input['descricao'])) {
                        $dados['descricao'] = Validator::sanitizeString($input['descricao']);
                    }
                    
                    if (isset($input['status'])) {
                        if (!in_array($input['status'], ['ativo', 'inativo', 'encerrado'])) {
                            sendError('Status inválido', 400);
                        }
                        $dados['status'] = $input['status'];
                    }
                    
                    if (isset($input['valor_mensal'])) {
                        $dados['valor_mensal'] = empty($input['valor_mensal']) ? null : floatval($input['valor_mensal']);
                    }
                    
                    if (isset($input['data_fim'])) {
                        $dados['data_fim'] = empty($input['data_fim']) ? null : $input['data_fim'];
                    }
                    
                    $dados['atualizado_em'] = date('Y-m-d H:i:s');
                    $dados['atualizado_por'] = $usuario['id'];
                    
                    if (empty($dados)) {
                        sendError('Nenhum dado para atualizar', 400);
                    }
                    
                    $db->update('pmp_contratos', $dados, 'id = ?', [$contratoId]);
                    
                    $contratoAtualizado = $db->queryOne(
                        "SELECT * FROM pmp_contratos WHERE id = ?",
                        [$contratoId]
                    );
                    
                    sendSuccess($contratoAtualizado, 'Contrato PMP atualizado com sucesso', 200);
                }
                
                else {
                    sendError('Método não permitido', 405);
                }
            }
        }
        
        break;
    
    /**
     * POST /api/pmp/checklists/:id/ia
     * Gera itens do checklist com IA
     */
    case 'checklists':
        if ($recurso !== '' && is_numeric($recurso)) {
            $checklistId = intval($recurso);
            
            if (!empty($acao)) {
                
                if ($acao === 'ia' && $method === 'POST') {
                    // Busca checklist
                    $checklist = $db->queryOne(
                        "SELECT pce.*, pe.nome as equipamento_nome, pe.modelo as equipamento_modelo
                         FROM pmp_checklists_execucao pce
                         JOIN pmp_equipamentos pe ON pce.equipamento_id = pe.id
                         WHERE pce.id = ?",
                        [$checklistId]
                    );
                    
                    if (!$checklist) {
                        sendError('Checklist não encontrado', 404);
                    }
                    
                    try {
                        // Usa classe de IA se disponível
                        if (class_exists('IA')) {
                            $ia = new IA();
                            
                            // Gera prompt para IA
                            $prompt = "Gere uma lista de verificação de manutenção preventiva para um equipamento {$checklist['equipamento_nome']} modelo {$checklist['equipamento_modelo']}. 
                            Retorne um JSON com array de itens contendo 'titulo' e 'descricao'.";
                            
                            // Obtém resposta da IA
                            $resposta = $ia->gerarChecklist($prompt);
                            
                            // Parseia resposta
                            $itens = json_decode($resposta, true);
                            
                            if (!is_array($itens)) {
                                // Se não for um array válido, cria itens padrão
                                $itens = [
                                    ['titulo' => 'Inspeção Visual', 'descricao' => 'Realizar inspeção visual completa do equipamento'],
                                    ['titulo' => 'Lubrificação', 'descricao' => 'Aplicar lubrificante nos pontos de movimento'],
                                    ['titulo' => 'Limpeza', 'descricao' => 'Limpar componentes e remover sujeira acumulada'],
                                    ['titulo' => 'Testes Funcionais', 'descricao' => 'Testar funcionamento de todas as funções']
                                ];
                            }
                            
                            // Insere itens no banco
                            $itensInseridos = [];
                            foreach ($itens as $item) {
                                $dadosItem = [
                                    'checklist_id' => $checklistId,
                                    'titulo' => isset($item['titulo']) ? Validator::sanitizeString($item['titulo']) : 'Verificação',
                                    'descricao' => isset($item['descricao']) ? Validator::sanitizeString($item['descricao']) : '',
                                    'concluido' => 0,
                                    'criado_em' => date('Y-m-d H:i:s'),
                                    'criado_por' => $usuario['id']
                                ];
                                
                                $itemId = $db->insert('pmp_checklist_itens', $dadosItem);
                                $itensInseridos[] = $db->queryOne("SELECT * FROM pmp_checklist_itens WHERE id = ?", [$itemId]);
                            }
                            
                            sendSuccess([
                                'checklist_id' => $checklistId,
                                'itens_gerados' => count($itensInseridos),
                                'itens' => $itensInseridos
                            ], 'Itens do checklist gerados com sucesso pela IA', 201);
                            
                        } else {
                            sendError('Serviço de IA não disponível', 503);
                        }
                        
                    } catch (Exception $e) {
                        sendError('Erro ao gerar checklist com IA: ' . $e->getMessage(), 500);
                    }
                }
                
                else {
                    sendError('Ação não encontrada', 404);
                }
            }
        }
        
        break;
    
    default:
        sendError('Recurso não encontrado', 404);
        break;
}
?>
