<?php
/**
 * ARQUIVO: clientes.php
 * 
 * Função: Endpoints CRUD de gerenciamento de clientes
 * Entrada: Dados de cliente, parâmetros de busca e paginação
 * Processamento: Cria, lê, atualiza, deleta e busca clientes
 * Saída: Dados de cliente(s), lista paginada, mensagens de sucesso/erro
 * 
 * Endpoints disponíveis:
 * - GET /api/clientes - Lista todos os clientes (com paginação)
 * - GET /api/clientes/:id - Obtém um cliente específico
 * - POST /api/clientes - Cria novo cliente
 * - PUT /api/clientes/:id - Atualiza cliente existente
 * - DELETE /api/clientes/:id - Deleta cliente
 * - GET /api/clientes/search - Busca clientes por critério
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
     * GET /api/clientes
     * Lista todos os clientes com paginação
     * Parâmetros: page, limit, order
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
        
        // Sanitiza ORDER BY para evitar SQL injection
        $allowedOrders = ['nome', 'email', 'criado_em'];
        $orderField = 'nome';
        $orderDir = 'ASC';
        
        if (preg_match('/^(nome|email|criado_em)\s+(ASC|DESC)$/i', $order, $matches)) {
            $orderField = $matches[1];
            $orderDir = strtoupper($matches[2]);
        }
        
        $offset = ($page - 1) * $limit;
        
        // Conta total de clientes
        $total = $db->count('clientes');
        
        // Busca clientes paginados
        $clientes = $db->find('clientes', [
            'order' => "{$orderField} {$orderDir}",
            'limit' => $limit,
            'offset' => $offset
        ]);
        
        sendSuccess([
            'clientes' => $clientes,
            'paginacao' => [
                'pagina_atual' => $page,
                'total_itens' => $total,
                'itens_por_pagina' => $limit,
                'total_paginas' => ceil($total / $limit)
            ]
        ], 'Clientes obtidos com sucesso', 200);
        break;
    
    /**
     * GET /api/clientes/search
     * Busca clientes por nome, email ou CPF
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
        
        // Busca por nome, email ou CPF
        $searchTerm = "%{$query}%";
        $clientes = $db->find('clientes', [
            'where' => "nome LIKE ? OR email LIKE ? OR cpf LIKE ?",
            'params' => [$searchTerm, $searchTerm, $searchTerm],
            'limit' => $limit,
            'offset' => $offset
        ]);
        
        // Conta resultados
        $total = $db->count(
            'clientes',
            "nome LIKE ? OR email LIKE ? OR cpf LIKE ?",
            [$searchTerm, $searchTerm, $searchTerm]
        );
        
        sendSuccess([
            'clientes' => $clientes,
            'paginacao' => [
                'pagina_atual' => $page,
                'total_itens' => $total,
                'itens_por_pagina' => $limit,
                'total_paginas' => ceil($total / $limit)
            ]
        ], 'Busca realizada com sucesso', 200);
        break;
    
    /**
     * POST /api/clientes
     * Cria novo cliente
     * Requer: nome, email, cpf, telefone
     */
    case null:
        if ($method === 'POST') {
            // Valida entrada
            if (empty($input['nome'])) {
                sendError('Nome é obrigatório', 400);
            }
            
            if (empty($input['email'])) {
                sendError('Email é obrigatório', 400);
            }
            
            // Valida email
            if (!Validator::validateEmail($input['email'])) {
                sendError('Email inválido', 400);
            }
            
            // Verifica se email já existe
            $existente = $db->queryOne(
                "SELECT id FROM clientes WHERE email = ?",
                [$input['email']]
            );
            
            if ($existente) {
                sendError('Email já cadastrado', 400);
            }
            
            // Valida CPF se fornecido
            if (!empty($input['cpf'])) {
                if (!Validator::validateCPF($input['cpf'])) {
                    sendError('CPF inválido', 400);
                }
                
                // Verifica se CPF já existe
                $existente = $db->queryOne(
                    "SELECT id FROM clientes WHERE cpf = ?",
                    [$input['cpf']]
                );
                
                if ($existente) {
                    sendError('CPF já cadastrado', 400);
                }
            }
            
            // Valida telefone se fornecido
            if (!empty($input['telefone'])) {
                if (!Validator::validatePhone($input['telefone'])) {
                    sendError('Telefone inválido', 400);
                }
            }
            
            // Prepara dados
            $dados = [
                'nome' => Validator::sanitizeString($input['nome']),
                'email' => strtolower(trim($input['email'])),
                'cpf' => isset($input['cpf']) ? preg_replace('/[^0-9]/', '', $input['cpf']) : null,
                'telefone' => isset($input['telefone']) ? preg_replace('/[^0-9]/', '', $input['telefone']) : null,
                'endereco' => isset($input['endereco']) ? Validator::sanitizeString($input['endereco']) : null,
                'cidade' => isset($input['cidade']) ? Validator::sanitizeString($input['cidade']) : null,
                'estado' => isset($input['estado']) ? substr($input['estado'], 0, 2) : null,
                'cep' => isset($input['cep']) ? preg_replace('/[^0-9]/', '', $input['cep']) : null,
                'observacoes' => isset($input['observacoes']) ? Validator::sanitizeString($input['observacoes']) : null,
                'criado_em' => date('Y-m-d H:i:s'),
                'criado_por' => $usuario['id']
            ];
            
            // Insere no banco
            $clienteId = $db->insert('clientes', $dados);
            
            // Busca cliente criado
            $cliente = $db->queryOne(
                "SELECT * FROM clientes WHERE id = ?",
                [$clienteId]
            );
            
            sendSuccess($cliente, 'Cliente criado com sucesso', 201);
        }
        break;
    
    /**
     * GET /api/clientes/:id
     * Obtém dados de um cliente específico
     */
    default:
        if ($id === null || $id <= 0) {
            sendError('ID de cliente inválido', 400);
        }
        
        if ($method === 'GET') {
            // Busca cliente
            $cliente = $db->queryOne(
                "SELECT * FROM clientes WHERE id = ?",
                [$id]
            );
            
            if (!$cliente) {
                sendError('Cliente não encontrado', 404);
            }
            
            sendSuccess($cliente, 'Cliente obtido com sucesso', 200);
        }
        
        /**
         * PUT /api/clientes/:id
         * Atualiza dados de um cliente existente
         */
        elseif ($method === 'PUT') {
            // Busca cliente existente
            $cliente = $db->queryOne(
                "SELECT * FROM clientes WHERE id = ?",
                [$id]
            );
            
            if (!$cliente) {
                sendError('Cliente não encontrado', 404);
            }
            
            // Valida email se fornecido
            if (isset($input['email']) && $input['email'] !== $cliente['email']) {
                if (!Validator::validateEmail($input['email'])) {
                    sendError('Email inválido', 400);
                }
                
                $existente = $db->queryOne(
                    "SELECT id FROM clientes WHERE email = ? AND id != ?",
                    [$input['email'], $id]
                );
                
                if ($existente) {
                    sendError('Email já cadastrado', 400);
                }
            }
            
            // Valida CPF se fornecido
            if (isset($input['cpf']) && !empty($input['cpf'])) {
                $cpfLimpo = preg_replace('/[^0-9]/', '', $input['cpf']);
                
                if (!Validator::validateCPF($cpfLimpo)) {
                    sendError('CPF inválido', 400);
                }
                
                if ($cpfLimpo !== $cliente['cpf']) {
                    $existente = $db->queryOne(
                        "SELECT id FROM clientes WHERE cpf = ? AND id != ?",
                        [$cpfLimpo, $id]
                    );
                    
                    if ($existente) {
                        sendError('CPF já cadastrado', 400);
                    }
                }
            }
            
            // Prepara dados para atualização
            $dados = [];
            
            if (isset($input['nome'])) {
                $dados['nome'] = Validator::sanitizeString($input['nome']);
            }
            
            if (isset($input['email'])) {
                $dados['email'] = strtolower(trim($input['email']));
            }
            
            if (isset($input['cpf'])) {
                $dados['cpf'] = empty($input['cpf']) ? null : preg_replace('/[^0-9]/', '', $input['cpf']);
            }
            
            if (isset($input['telefone'])) {
                $dados['telefone'] = empty($input['telefone']) ? null : preg_replace('/[^0-9]/', '', $input['telefone']);
            }
            
            if (isset($input['endereco'])) {
                $dados['endereco'] = empty($input['endereco']) ? null : Validator::sanitizeString($input['endereco']);
            }
            
            if (isset($input['cidade'])) {
                $dados['cidade'] = empty($input['cidade']) ? null : Validator::sanitizeString($input['cidade']);
            }
            
            if (isset($input['estado'])) {
                $dados['estado'] = empty($input['estado']) ? null : substr($input['estado'], 0, 2);
            }
            
            if (isset($input['cep'])) {
                $dados['cep'] = empty($input['cep']) ? null : preg_replace('/[^0-9]/', '', $input['cep']);
            }
            
            if (isset($input['observacoes'])) {
                $dados['observacoes'] = empty($input['observacoes']) ? null : Validator::sanitizeString($input['observacoes']);
            }
            
            $dados['atualizado_em'] = date('Y-m-d H:i:s');
            $dados['atualizado_por'] = $usuario['id'];
            
            if (empty($dados)) {
                sendError('Nenhum dado para atualizar', 400);
            }
            
            // Atualiza no banco
            $db->update('clientes', $dados, 'id = ?', [$id]);
            
            // Busca cliente atualizado
            $clienteAtualizado = $db->queryOne(
                "SELECT * FROM clientes WHERE id = ?",
                [$id]
            );
            
            sendSuccess($clienteAtualizado, 'Cliente atualizado com sucesso', 200);
        }
        
        /**
         * DELETE /api/clientes/:id
         * Deleta um cliente existente
         */
        elseif ($method === 'DELETE') {
            // Busca cliente
            $cliente = $db->queryOne(
                "SELECT * FROM clientes WHERE id = ?",
                [$id]
            );
            
            if (!$cliente) {
                sendError('Cliente não encontrado', 404);
            }
            
            // Verifica se há registros vinculados (opcional)
            // Você pode adicionar verificações aqui se necessário
            
            // Deleta cliente
            $db->delete('clientes', 'id = ?', [$id]);
            
            sendSuccess([], 'Cliente deletado com sucesso', 200);
        }
        
        else {
            sendError('Método não permitido', 405);
        }
        break;
}
?>
