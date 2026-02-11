<?php
/**
 * ARQUIVO: produtos.php
 * 
 * Função: Endpoints CRUD de gerenciamento de produtos
 * Entrada: Dados de produto, parâmetros de busca, paginação e filtros
 * Processamento: Cria, lê, atualiza, deleta, busca produtos e gerencia estoque
 * Saída: Dados de produto(s), lista paginada, mensagens de sucesso/erro
 * 
 * Endpoints disponíveis:
 * - GET /api/produtos - Lista todos os produtos (com paginação e filtros)
 * - GET /api/produtos/:id - Obtém um produto específico
 * - POST /api/produtos - Cria novo produto
 * - PUT /api/produtos/:id - Atualiza produto existente
 * - DELETE /api/produtos/:id - Deleta produto
 * - GET /api/produtos/search - Busca produtos por nome, código ou descrição
 * - GET /api/produtos/categoria/:id - Obtém produtos por categoria
 * - PUT /api/produtos/:id/estoque - Atualiza estoque do produto
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
     * GET /api/produtos
     * Lista todos os produtos com paginação e filtros
     * Parâmetros: page, limit, order, categoria_id, status
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
        
        // Obtém filtros
        $categoria_id = isset($_GET['categoria_id']) ? intval($_GET['categoria_id']) : null;
        $status = isset($_GET['status']) ? $_GET['status'] : null;
        
        // Sanitiza ORDER BY para evitar SQL injection
        $allowedOrders = ['nome', 'preco', 'criado_em'];
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
        
        if (!is_null($categoria_id) && $categoria_id > 0) {
            $where .= ' AND categoria_id = ?';
            $params[] = $categoria_id;
        }
        
        if (!is_null($status) && in_array($status, ['ativo', 'inativo'])) {
            $ativo = ($status === 'ativo') ? 1 : 0;
            $where .= ' AND ativo = ?';
            $params[] = $ativo;
        }
        
        // Conta total de produtos
        $total = $db->count('produtos', $where, $params);
        
        // Busca produtos paginados
        $produtos = $db->find('produtos', [
            'where' => $where,
            'params' => $params,
            'order' => "{$orderField} {$orderDir}",
            'limit' => $limit,
            'offset' => $offset
        ]);
        
        sendSuccess([
            'produtos' => $produtos,
            'paginacao' => [
                'pagina_atual' => $page,
                'total_itens' => $total,
                'itens_por_pagina' => $limit,
                'total_paginas' => ceil($total / $limit)
            ]
        ], 'Produtos obtidos com sucesso', 200);
        break;
    
    /**
     * GET /api/produtos/search
     * Busca produtos por nome, código ou descrição
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
        
        // Busca por nome, código ou descrição
        $searchTerm = "%{$query}%";
        $produtos = $db->find('produtos', [
            'where' => "nome LIKE ? OR codigo LIKE ? OR descricao LIKE ?",
            'params' => [$searchTerm, $searchTerm, $searchTerm],
            'limit' => $limit,
            'offset' => $offset
        ]);
        
        // Conta resultados
        $total = $db->count(
            'produtos',
            "nome LIKE ? OR codigo LIKE ? OR descricao LIKE ?",
            [$searchTerm, $searchTerm, $searchTerm]
        );
        
        sendSuccess([
            'produtos' => $produtos,
            'paginacao' => [
                'pagina_atual' => $page,
                'total_itens' => $total,
                'itens_por_pagina' => $limit,
                'total_paginas' => ceil($total / $limit)
            ]
        ], 'Busca realizada com sucesso', 200);
        break;
    
    /**
     * GET /api/produtos/categoria/:id
     * Obtém produtos de uma categoria específica
     * Parâmetros: page, limit
     */
    case 'categoria':
        if ($method !== 'GET') {
            sendError('Método não permitido', 405);
        }
        
        $categoria_id = isset($parts[2]) ? intval($parts[2]) : null;
        
        if ($categoria_id === null || $categoria_id <= 0) {
            sendError('ID de categoria inválido', 400);
        }
        
        // Verifica se categoria existe
        $categoria = $db->queryOne(
            "SELECT id FROM categorias WHERE id = ?",
            [$categoria_id]
        );
        
        if (!$categoria) {
            sendError('Categoria não encontrada', 404);
        }
        
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : ITEMS_PER_PAGE;
        $offset = ($page - 1) * $limit;
        
        // Busca produtos da categoria
        $total = $db->count(
            'produtos',
            'categoria_id = ? AND ativo = 1',
            [$categoria_id]
        );
        
        $produtos = $db->find('produtos', [
            'where' => 'categoria_id = ? AND ativo = 1',
            'params' => [$categoria_id],
            'order' => 'nome ASC',
            'limit' => $limit,
            'offset' => $offset
        ]);
        
        sendSuccess([
            'produtos' => $produtos,
            'categoria_id' => $categoria_id,
            'paginacao' => [
                'pagina_atual' => $page,
                'total_itens' => $total,
                'itens_por_pagina' => $limit,
                'total_paginas' => ceil($total / $limit)
            ]
        ], 'Produtos da categoria obtidos com sucesso', 200);
        break;
    
    /**
     * POST /api/produtos
     * Cria novo produto
     * Requer: nome, codigo, preco, categoria_id
     * Opcionais: descricao, quantidade, imagem_url, sku, peso, dimensoes
     */
    case null:
        if ($method === 'POST') {
            // Valida entrada obrigatória
            if (empty($input['nome'])) {
                sendError('Nome é obrigatório', 400);
            }
            
            if (empty($input['codigo'])) {
                sendError('Código é obrigatório', 400);
            }
            
            if (empty($input['preco'])) {
                sendError('Preço é obrigatório', 400);
            }
            
            if (empty($input['categoria_id'])) {
                sendError('Categoria é obrigatória', 400);
            }
            
            // Valida tipo de dados
            if (!is_numeric($input['preco']) || floatval($input['preco']) < 0) {
                sendError('Preço deve ser um número positivo', 400);
            }
            
            $categoria_id = intval($input['categoria_id']);
            
            if ($categoria_id <= 0) {
                sendError('ID de categoria inválido', 400);
            }
            
            // Verifica se categoria existe
            $categoria = $db->queryOne(
                "SELECT id FROM categorias WHERE id = ?",
                [$categoria_id]
            );
            
            if (!$categoria) {
                sendError('Categoria não encontrada', 404);
            }
            
            // Verifica se código já existe
            $existente = $db->queryOne(
                "SELECT id FROM produtos WHERE codigo = ?",
                [$input['codigo']]
            );
            
            if ($existente) {
                sendError('Código de produto já cadastrado', 400);
            }
            
            // Valida quantidade se fornecida
            if (isset($input['quantidade'])) {
                if (!is_numeric($input['quantidade']) || intval($input['quantidade']) < 0) {
                    sendError('Quantidade deve ser um número não-negativo', 400);
                }
            }
            
            // Prepara dados
            $dados = [
                'nome' => Validator::sanitizeString($input['nome']),
                'codigo' => Validator::sanitizeString($input['codigo']),
                'preco' => floatval($input['preco']),
                'categoria_id' => $categoria_id,
                'descricao' => isset($input['descricao']) ? Validator::sanitizeString($input['descricao']) : null,
                'quantidade' => isset($input['quantidade']) ? intval($input['quantidade']) : 0,
                'imagem_url' => isset($input['imagem_url']) ? filter_var($input['imagem_url'], FILTER_VALIDATE_URL) : null,
                'sku' => isset($input['sku']) ? Validator::sanitizeString($input['sku']) : null,
                'peso' => isset($input['peso']) ? floatval($input['peso']) : null,
                'dimensoes' => isset($input['dimensoes']) ? Validator::sanitizeString($input['dimensoes']) : null,
                'ativo' => 1,
                'criado_em' => date('Y-m-d H:i:s'),
                'criado_por' => $usuario['id']
            ];
            
            // Insere no banco
            $produtoId = $db->insert('produtos', $dados);
            
            // Busca produto criado
            $produto = $db->queryOne(
                "SELECT * FROM produtos WHERE id = ?",
                [$produtoId]
            );
            
            sendSuccess($produto, 'Produto criado com sucesso', 201);
        }
        break;
    
    /**
     * GET /api/produtos/:id
     * Obtém dados de um produto específico
     */
    default:
        if ($id === null || $id <= 0) {
            sendError('ID de produto inválido', 400);
        }
        
        // Se houver ação adicional (ex: /api/produtos/:id/estoque)
        if (!empty($acao)) {
            
            /**
             * PUT /api/produtos/:id/estoque
             * Atualiza estoque do produto
             * Requer: quantidade, tipo (entrada/saída)
             */
            if ($acao === 'estoque' && $method === 'PUT') {
                // Busca produto
                $produto = $db->queryOne(
                    "SELECT * FROM produtos WHERE id = ?",
                    [$id]
                );
                
                if (!$produto) {
                    sendError('Produto não encontrado', 404);
                }
                
                // Valida entrada
                if (empty($input['quantidade'])) {
                    sendError('Quantidade é obrigatória', 400);
                }
                
                if (!is_numeric($input['quantidade']) || intval($input['quantidade']) < 0) {
                    sendError('Quantidade deve ser um número não-negativo', 400);
                }
                
                $tipo = isset($input['tipo']) ? $input['tipo'] : 'entrada';
                
                if (!in_array($tipo, ['entrada', 'saida'])) {
                    sendError('Tipo deve ser "entrada" ou "saida"', 400);
                }
                
                $quantidade = intval($input['quantidade']);
                $novaQuantidade = $produto['quantidade'];
                
                if ($tipo === 'entrada') {
                    $novaQuantidade += $quantidade;
                } else {
                    if ($novaQuantidade < $quantidade) {
                        sendError('Quantidade insuficiente em estoque', 400);
                    }
                    $novaQuantidade -= $quantidade;
                }
                
                // Atualiza quantidade
                $dados = [
                    'quantidade' => $novaQuantidade,
                    'atualizado_em' => date('Y-m-d H:i:s'),
                    'atualizado_por' => $usuario['id']
                ];
                
                $db->update('produtos', $dados, 'id = ?', [$id]);
                
                // Busca produto atualizado
                $produtoAtualizado = $db->queryOne(
                    "SELECT * FROM produtos WHERE id = ?",
                    [$id]
                );
                
                sendSuccess($produtoAtualizado, 'Estoque atualizado com sucesso', 200);
            } else {
                sendError('Ação não encontrada', 404);
            }
        } else {
            
            if ($method === 'GET') {
                // Busca produto
                $produto = $db->queryOne(
                    "SELECT p.*, c.nome as categoria_nome 
                     FROM produtos p 
                     LEFT JOIN categorias c ON p.categoria_id = c.id 
                     WHERE p.id = ?",
                    [$id]
                );
                
                if (!$produto) {
                    sendError('Produto não encontrado', 404);
                }
                
                sendSuccess($produto, 'Produto obtido com sucesso', 200);
            }
            
            /**
             * PUT /api/produtos/:id
             * Atualiza dados de um produto existente
             */
            elseif ($method === 'PUT') {
                // Busca produto existente
                $produto = $db->queryOne(
                    "SELECT * FROM produtos WHERE id = ?",
                    [$id]
                );
                
                if (!$produto) {
                    sendError('Produto não encontrado', 404);
                }
                
                // Valida código se fornecido
                if (isset($input['codigo']) && $input['codigo'] !== $produto['codigo']) {
                    $existente = $db->queryOne(
                        "SELECT id FROM produtos WHERE codigo = ? AND id != ?",
                        [$input['codigo'], $id]
                    );
                    
                    if ($existente) {
                        sendError('Código de produto já cadastrado', 400);
                    }
                }
                
                // Valida categoria se fornecida
                if (isset($input['categoria_id'])) {
                    $categoria_id = intval($input['categoria_id']);
                    
                    if ($categoria_id <= 0) {
                        sendError('ID de categoria inválido', 400);
                    }
                    
                    $categoria = $db->queryOne(
                        "SELECT id FROM categorias WHERE id = ?",
                        [$categoria_id]
                    );
                    
                    if (!$categoria) {
                        sendError('Categoria não encontrada', 404);
                    }
                }
                
                // Valida preço se fornecido
                if (isset($input['preco'])) {
                    if (!is_numeric($input['preco']) || floatval($input['preco']) < 0) {
                        sendError('Preço deve ser um número positivo', 400);
                    }
                }
                
                // Prepara dados para atualização
                $dados = [];
                
                if (isset($input['nome'])) {
                    $dados['nome'] = Validator::sanitizeString($input['nome']);
                }
                
                if (isset($input['codigo'])) {
                    $dados['codigo'] = Validator::sanitizeString($input['codigo']);
                }
                
                if (isset($input['preco'])) {
                    $dados['preco'] = floatval($input['preco']);
                }
                
                if (isset($input['categoria_id'])) {
                    $dados['categoria_id'] = intval($input['categoria_id']);
                }
                
                if (isset($input['descricao'])) {
                    $dados['descricao'] = empty($input['descricao']) ? null : Validator::sanitizeString($input['descricao']);
                }
                
                if (isset($input['quantidade'])) {
                    if (!is_numeric($input['quantidade']) || intval($input['quantidade']) < 0) {
                        sendError('Quantidade deve ser um número não-negativo', 400);
                    }
                    $dados['quantidade'] = intval($input['quantidade']);
                }
                
                if (isset($input['imagem_url'])) {
                    $dados['imagem_url'] = empty($input['imagem_url']) ? null : filter_var($input['imagem_url'], FILTER_VALIDATE_URL);
                }
                
                if (isset($input['sku'])) {
                    $dados['sku'] = empty($input['sku']) ? null : Validator::sanitizeString($input['sku']);
                }
                
                if (isset($input['peso'])) {
                    $dados['peso'] = empty($input['peso']) ? null : floatval($input['peso']);
                }
                
                if (isset($input['dimensoes'])) {
                    $dados['dimensoes'] = empty($input['dimensoes']) ? null : Validator::sanitizeString($input['dimensoes']);
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
                $db->update('produtos', $dados, 'id = ?', [$id]);
                
                // Busca produto atualizado
                $produtoAtualizado = $db->queryOne(
                    "SELECT * FROM produtos WHERE id = ?",
                    [$id]
                );
                
                sendSuccess($produtoAtualizado, 'Produto atualizado com sucesso', 200);
            }
            
            /**
             * DELETE /api/produtos/:id
             * Deleta um produto existente
             */
            elseif ($method === 'DELETE') {
                // Busca produto
                $produto = $db->queryOne(
                    "SELECT * FROM produtos WHERE id = ?",
                    [$id]
                );
                
                if (!$produto) {
                    sendError('Produto não encontrado', 404);
                }
                
                // Deleta produto
                $db->delete('produtos', 'id = ?', [$id]);
                
                sendSuccess([], 'Produto deletado com sucesso', 200);
            }
            
            else {
                sendError('Método não permitido', 405);
            }
        }
        break;
}
?>
