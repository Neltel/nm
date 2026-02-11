<?php
/**
 * ARQUIVO: servicos.php
 * 
 * Função: Endpoints CRUD de gerenciamento de serviços
 * Entrada: Dados de serviço, parâmetros de busca e paginação
 * Processamento: Cria, lê, atualiza, deleta e busca serviços
 * Saída: Dados de serviço(s), lista paginada, mensagens de sucesso/erro
 * 
 * Endpoints disponíveis:
 * - GET /api/servicos - Lista todos os serviços (com paginação)
 * - GET /api/servicos/:id - Obtém um serviço específico
 * - POST /api/servicos - Cria novo serviço
 * - PUT /api/servicos/:id - Atualiza serviço existente
 * - DELETE /api/servicos/:id - Deleta serviço
 * - GET /api/servicos/search - Busca serviços por nome ou descrição
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

// Roteia para ação apropriada
switch ($subroute) {
    
    /**
     * GET /api/servicos
     * Lista todos os serviços com paginação
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
        $order = isset($_GET['order']) ? $_GET['order'] : 'nome ASC';
        
        // Obtém filtro de status
        $status = isset($_GET['status']) ? $_GET['status'] : null;
        
        // Sanitiza ORDER BY para evitar SQL injection
        $orderField = 'nome';
        $orderDir = 'ASC';
        
        if (preg_match('/^(nome|preco|criado_em)\s+(ASC|DESC)$/i', $order, $matches)) {
            $orderField = $matches[1];
            $orderDir = strtoupper($matches[2]);
        }
        
        $offset = ($page - 1) * $limit;
        
        // Constrói cláusula WHERE para filtros
        $where = '1=1';
        $params = [];
        
        if (!is_null($status) && in_array($status, ['ativo', 'inativo'])) {
            $ativo = ($status === 'ativo') ? 1 : 0;
            $where .= ' AND ativo = ?';
            $params[] = $ativo;
        }
        
        // Conta total de serviços
        $total = $db->count('servicos', $where, $params);
        
        // Busca serviços paginados
        $servicos = $db->find('servicos', [
            'where' => $where,
            'params' => $params,
            'order' => "{$orderField} {$orderDir}",
            'limit' => $limit,
            'offset' => $offset
        ]);
        
        sendSuccess([
            'servicos' => $servicos,
            'paginacao' => [
                'pagina_atual' => $page,
                'total_itens' => $total,
                'itens_por_pagina' => $limit,
                'total_paginas' => ceil($total / $limit)
            ]
        ], 'Serviços obtidos com sucesso', 200);
        break;
    
    /**
     * GET /api/servicos/search
     * Busca serviços por nome ou descrição
     * Parâmetros: q (query), page, limit
     */
    case 'search':
        if ($method !== 'GET') {
            sendError('Método não permitido', 405);
        }
        
        $query = isset($_GET['q']) ? trim($_GET['q']) : '';
        
        if (strlen($query) < 2) {
            sendError('Termo de busca deve ter no mínimo 2 caracteres', 400);
        }
        
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : ITEMS_PER_PAGE;
        $offset = ($page - 1) * $limit;
        
        // Busca por nome ou descrição
        $searchTerm = "%{$query}%";
        $servicos = $db->find('servicos', [
            'where' => "nome LIKE ? OR descricao LIKE ?",
            'params' => [$searchTerm, $searchTerm],
            'limit' => $limit,
            'offset' => $offset
        ]);
        
        // Conta resultados
        $total = $db->count(
            'servicos',
            "nome LIKE ? OR descricao LIKE ?",
            [$searchTerm, $searchTerm]
        );
        
        sendSuccess([
            'servicos' => $servicos,
            'paginacao' => [
                'pagina_atual' => $page,
                'total_itens' => $total,
                'itens_por_pagina' => $limit,
                'total_paginas' => ceil($total / $limit)
            ]
        ], 'Busca realizada com sucesso', 200);
        break;
    
    /**
     * POST /api/servicos
     * Cria novo serviço
     * Requer: nome, preco
     * Opcionais: descricao, duracao, categoria
     */
    case null:
        if ($method === 'POST') {
            // Valida entrada obrigatória
            if (empty($input['nome'])) {
                sendError('Nome é obrigatório', 400);
            }
            
            if (empty($input['preco'])) {
                sendError('Preço é obrigatório', 400);
            }
            
            // Valida tipo de dados
            if (!is_numeric($input['preco']) || floatval($input['preco']) < 0) {
                sendError('Preço deve ser um número positivo', 400);
            }
            
            // Valida duração se fornecida
            if (isset($input['duracao'])) {
                if (!is_numeric($input['duracao']) || intval($input['duracao']) <= 0) {
                    sendError('Duração deve ser um número positivo (em minutos)', 400);
                }
            }
            
            // Prepara dados
            $dados = [
                'nome' => Validator::sanitizeString($input['nome']),
                'preco' => floatval($input['preco']),
                'descricao' => isset($input['descricao']) ? Validator::sanitizeString($input['descricao']) : null,
                'duracao' => isset($input['duracao']) ? intval($input['duracao']) : null,
                'categoria' => isset($input['categoria']) ? Validator::sanitizeString($input['categoria']) : null,
                'ativo' => 1,
                'criado_em' => date('Y-m-d H:i:s'),
                'criado_por' => $usuario['id']
            ];
            
            // Insere no banco
            $servicoId = $db->insert('servicos', $dados);
            
            // Busca serviço criado
            $servico = $db->queryOne(
                "SELECT * FROM servicos WHERE id = ?",
                [$servicoId]
            );
            
            sendSuccess($servico, 'Serviço criado com sucesso', 201);
        }
        break;
    
    /**
     * GET /api/servicos/:id
     * Obtém dados de um serviço específico
     */
    default:
        if ($id === null || $id <= 0) {
            sendError('ID de serviço inválido', 400);
        }
        
        if ($method === 'GET') {
            // Busca serviço
            $servico = $db->queryOne(
                "SELECT * FROM servicos WHERE id = ?",
                [$id]
            );
            
            if (!$servico) {
                sendError('Serviço não encontrado', 404);
            }
            
            sendSuccess($servico, 'Serviço obtido com sucesso', 200);
        }
        
        /**
         * PUT /api/servicos/:id
         * Atualiza dados de um serviço existente
         */
        elseif ($method === 'PUT') {
            // Busca serviço existente
            $servico = $db->queryOne(
                "SELECT * FROM servicos WHERE id = ?",
                [$id]
            );
            
            if (!$servico) {
                sendError('Serviço não encontrado', 404);
            }
            
            // Valida preço se fornecido
            if (isset($input['preco'])) {
                if (!is_numeric($input['preco']) || floatval($input['preco']) < 0) {
                    sendError('Preço deve ser um número positivo', 400);
                }
            }
            
            // Valida duração se fornecida
            if (isset($input['duracao'])) {
                if (!is_numeric($input['duracao']) || intval($input['duracao']) <= 0) {
                    sendError('Duração deve ser um número positivo (em minutos)', 400);
                }
            }
            
            // Prepara dados para atualização
            $dados = [];
            
            if (isset($input['nome'])) {
                $dados['nome'] = Validator::sanitizeString($input['nome']);
            }
            
            if (isset($input['preco'])) {
                $dados['preco'] = floatval($input['preco']);
            }
            
            if (isset($input['descricao'])) {
                $dados['descricao'] = empty($input['descricao']) ? null : Validator::sanitizeString($input['descricao']);
            }
            
            if (isset($input['duracao'])) {
                $dados['duracao'] = empty($input['duracao']) ? null : intval($input['duracao']);
            }
            
            if (isset($input['categoria'])) {
                $dados['categoria'] = empty($input['categoria']) ? null : Validator::sanitizeString($input['categoria']);
            }
            
            if (isset($input['ativo'])) {
                $dados['ativo'] = intval($input['ativo']) ? 1 : 0;
            }
            
            $dados['atualizado_em'] = date('Y-m-d H:i:s');
            $dados['atualizado_por'] = $usuario['id'];
            
            if (empty($dados)) {
                sendError('Nenhum dado para atualizar', 400);
            }
            
            // Atualiza no banco
            $db->update('servicos', $dados, 'id = ?', [$id]);
            
            // Busca serviço atualizado
            $servicoAtualizado = $db->queryOne(
                "SELECT * FROM servicos WHERE id = ?",
                [$id]
            );
            
            sendSuccess($servicoAtualizado, 'Serviço atualizado com sucesso', 200);
        }
        
        /**
         * DELETE /api/servicos/:id
         * Deleta um serviço existente
         */
        elseif ($method === 'DELETE') {
            // Busca serviço
            $servico = $db->queryOne(
                "SELECT * FROM servicos WHERE id = ?",
                [$id]
            );
            
            if (!$servico) {
                sendError('Serviço não encontrado', 404);
            }
            
            // Deleta serviço
            $db->delete('servicos', 'id = ?', [$id]);
            
            sendSuccess([], 'Serviço deletado com sucesso', 200);
        }
        
        else {
            sendError('Método não permitido', 405);
        }
        break;
}
?>
