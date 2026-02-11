<?php
/**
 * ARQUIVO: relatorios.php
 * 
 * Função: Endpoints CRUD de gerenciamento de relatórios de visitas/serviços
 * Entrada: Dados de relatório, fotos, parâmetros de busca, paginação
 * Processamento: Cria, lê, atualiza relatórios, gerencia fotos, gera PDF, melhora descrição com IA
 * Saída: Dados de relatório(s), fotos, PDF gerado, descrição melhorada, mensagens de sucesso/erro
 * 
 * Endpoints disponíveis:
 * - GET /api/relatorios - Lista relatórios (com paginação e filtros)
 * - GET /api/relatorios/:id - Obtém relatório com suas fotos
 * - POST /api/relatorios - Cria novo relatório
 * - PUT /api/relatorios/:id - Atualiza relatório existente
 * - POST /api/relatorios/:id/fotos - Adiciona fotos ao relatório
 * - POST /api/relatorios/:id/pdf - Gera PDF do relatório
 * - POST /api/relatorios/:id/ia-improve - Melhora descrição com IA
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
     * GET /api/relatorios
     * Lista relatórios com paginação e filtros
     * Parâmetros: page, limit, status, tipo, cliente_id, data_inicio, data_fim
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
        $tipo = isset($_GET['tipo']) ? $_GET['tipo'] : null;
        $cliente_id = isset($_GET['cliente_id']) ? intval($_GET['cliente_id']) : null;
        
        $offset = ($page - 1) * $limit;
        
        // Constrói cláusula WHERE para filtros
        $where = '1=1';
        $params = [];
        
        if (!is_null($status) && in_array($status, ['rascunho', 'concluido', 'aprovado'])) {
            $where .= ' AND status = ?';
            $params[] = $status;
        }
        
        if (!is_null($tipo)) {
            $where .= ' AND tipo = ?';
            $params[] = Validator::sanitizeString($tipo);
        }
        
        if (!is_null($cliente_id) && $cliente_id > 0) {
            $where .= ' AND cliente_id = ?';
            $params[] = $cliente_id;
        }
        
        // Conta total de relatórios
        $total = $db->count('relatorios', $where, $params);
        
        // Busca relatórios paginados
        $relatorios = $db->find('relatorios', [
            'where' => $where,
            'params' => $params,
            'order' => 'criado_em DESC',
            'limit' => $limit,
            'offset' => $offset
        ]);
        
        sendSuccess([
            'relatorios' => $relatorios,
            'paginacao' => [
                'pagina_atual' => $page,
                'total_itens' => $total,
                'itens_por_pagina' => $limit,
                'total_paginas' => ceil($total / $limit)
            ]
        ], 'Relatórios obtidos com sucesso', 200);
        break;
    
    /**
     * POST /api/relatorios
     * Cria novo relatório
     * Requer: cliente_id, titulo, descricao, tipo
     * Opcionais: agendamento_id, tarefas_realizadas
     */
    case null:
        if ($method === 'POST') {
            // Valida entrada obrigatória
            if (empty($input['cliente_id'])) {
                sendError('ID do cliente é obrigatório', 400);
            }
            
            if (empty($input['titulo'])) {
                sendError('Título é obrigatório', 400);
            }
            
            if (empty($input['descricao'])) {
                sendError('Descrição é obrigatória', 400);
            }
            
            if (empty($input['tipo'])) {
                sendError('Tipo de relatório é obrigatório', 400);
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
                'titulo' => Validator::sanitizeString($input['titulo']),
                'descricao' => Validator::sanitizeString($input['descricao']),
                'tipo' => Validator::sanitizeString($input['tipo']),
                'agendamento_id' => isset($input['agendamento_id']) ? intval($input['agendamento_id']) : null,
                'tarefas_realizadas' => isset($input['tarefas_realizadas']) ? Validator::sanitizeString($input['tarefas_realizadas']) : null,
                'status' => 'rascunho',
                'criado_em' => date('Y-m-d H:i:s'),
                'criado_por' => $usuario['id']
            ];
            
            // Insere no banco
            $relatorioId = $db->insert('relatorios', $dados);
            
            // Busca relatório criado
            $relatorio = $db->queryOne(
                "SELECT * FROM relatorios WHERE id = ?",
                [$relatorioId]
            );
            
            sendSuccess($relatorio, 'Relatório criado com sucesso', 201);
        }
        break;
    
    /**
     * GET /api/relatorios/:id
     * Obtém relatório com suas fotos
     */
    default:
        if ($id === null || $id <= 0) {
            sendError('ID de relatório inválido', 400);
        }
        
        if (!empty($acao)) {
            
            /**
             * POST /api/relatorios/:id/fotos
             * Adiciona fotos ao relatório
             * Upload via multipart/form-data
             */
            if ($acao === 'fotos' && $method === 'POST') {
                // Verifica se relatório existe
                $relatorio = $db->queryOne(
                    "SELECT id FROM relatorios WHERE id = ?",
                    [$id]
                );
                
                if (!$relatorio) {
                    sendError('Relatório não encontrado', 404);
                }
                
                // Valida se arquivo foi enviado
                if (!isset($_FILES['arquivo']) || empty($_FILES['arquivo']['name'])) {
                    sendError('Nenhuma foto foi enviada', 400);
                }
                
                $arquivo = $_FILES['arquivo'];
                
                // Valida erros de upload
                if ($arquivo['error'] !== UPLOAD_ERR_OK) {
                    sendError('Erro no upload do arquivo', 400);
                }
                
                // Valida tipo de arquivo (apenas imagens)
                $tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!in_array($arquivo['type'], $tiposPermitidos)) {
                    sendError('Tipo de arquivo não permitido. Use JPG, PNG, GIF ou WebP', 400);
                }
                
                // Valida tamanho
                $tamanhoMaximo = 5 * 1024 * 1024; // 5MB
                if ($arquivo['size'] > $tamanhoMaximo) {
                    sendError('Arquivo excede o tamanho máximo de 5MB', 400);
                }
                
                // Cria diretório de uploads se não existir
                if (!is_dir(UPLOADS_PATH)) {
                    mkdir(UPLOADS_PATH, 0755, true);
                }
                
                // Gera nome único para arquivo
                $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
                $nomeFoto = 'foto_rel_' . $id . '_' . uniqid() . '_' . date('YmdHis') . '.' . $extensao;
                $caminhoCompleto = UPLOADS_PATH . '/' . $nomeFoto;
                
                // Move arquivo para diretório de uploads
                if (!move_uploaded_file($arquivo['tmp_name'], $caminhoCompleto)) {
                    sendError('Não foi possível salvar a foto', 500);
                }
                
                // Registra foto no banco
                $dadosFoto = [
                    'relatorio_id' => $id,
                    'nome' => $nomeFoto,
                    'url' => '/uploads/' . $nomeFoto,
                    'descricao' => isset($input['descricao']) ? Validator::sanitizeString($input['descricao']) : null,
                    'criado_em' => date('Y-m-d H:i:s'),
                    'criado_por' => $usuario['id']
                ];
                
                $fotoId = $db->insert('relatorios_fotos', $dadosFoto);
                
                sendSuccess([
                    'foto_id' => $fotoId,
                    'nome' => $nomeFoto,
                    'url' => '/uploads/' . $nomeFoto,
                    'criado_em' => date('Y-m-d H:i:s')
                ], 'Foto adicionada com sucesso', 201);
            }
            
            /**
             * POST /api/relatorios/:id/pdf
             * Gera PDF do relatório
             */
            elseif ($acao === 'pdf' && $method === 'POST') {
                // Busca relatório
                $relatorio = $db->queryOne(
                    "SELECT r.*, c.nome as cliente_nome
                     FROM relatorios r
                     JOIN clientes c ON r.cliente_id = c.id
                     WHERE r.id = ?",
                    [$id]
                );
                
                if (!$relatorio) {
                    sendError('Relatório não encontrado', 404);
                }
                
                // Busca fotos
                $fotos = $db->find('relatorios_fotos', [
                    'where' => 'relatorio_id = ?',
                    'params' => [$id],
                    'order' => 'criado_em ASC'
                ]);
                
                try {
                    // Gera conteúdo do PDF
                    $nomePdf = 'relatorio_' . $id . '_' . date('YmdHis') . '.pdf';
                    $caminhoPdf = UPLOADS_PATH . '/' . $nomePdf;
                    
                    // Conteúdo HTML para o PDF
                    $html = "
                    <html>
                    <head>
                        <meta charset='UTF-8'>
                        <style>
                            body { font-family: Arial, sans-serif; margin: 20px; }
                            .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; }
                            .field { margin: 15px 0; }
                            .field-label { font-weight: bold; margin-bottom: 5px; }
                            .field-value { margin-left: 10px; }
                            img { max-width: 100%; height: auto; margin: 10px 0; }
                        </style>
                    </head>
                    <body>
                        <div class='header'>
                            <h1>Relatório de Serviço</h1>
                        </div>
                        
                        <div class='field'>
                            <div class='field-label'>Relatório #</div>
                            <div class='field-value'>{$relatorio['id']}</div>
                        </div>
                        
                        <div class='field'>
                            <div class='field-label'>Cliente:</div>
                            <div class='field-value'>{$relatorio['cliente_nome']}</div>
                        </div>
                        
                        <div class='field'>
                            <div class='field-label'>Título:</div>
                            <div class='field-value'>{$relatorio['titulo']}</div>
                        </div>
                        
                        <div class='field'>
                            <div class='field-label'>Tipo:</div>
                            <div class='field-value'>{$relatorio['tipo']}</div>
                        </div>
                        
                        <div class='field'>
                            <div class='field-label'>Descrição:</div>
                            <div class='field-value'>{$relatorio['descricao']}</div>
                        </div>
                        
                        " . (!empty($relatorio['tarefas_realizadas']) ? "
                        <div class='field'>
                            <div class='field-label'>Tarefas Realizadas:</div>
                            <div class='field-value'>{$relatorio['tarefas_realizadas']}</div>
                        </div>
                        " : "") . "
                        
                        <div class='field'>
                            <div class='field-label'>Data de Criação:</div>
                            <div class='field-value'>" . date('d/m/Y H:i', strtotime($relatorio['criado_em'])) . "</div>
                        </div>
                        
                        " . (count($fotos) > 0 ? "
                        <hr>
                        <h2>Fotos do Relatório</h2>
                        " . implode('', array_map(function($foto) {
                            return "<p><img src='{$foto['url']}' alt='Foto'></p>";
                        }, $fotos)) . "
                        " : "") . "
                    </body>
                    </html>
                    ";
                    
                    // Cria diretório se não existir
                    if (!is_dir(UPLOADS_PATH)) {
                        mkdir(UPLOADS_PATH, 0755, true);
                    }
                    
                    // Salva como HTML
                    file_put_contents($caminhoPdf, $html);
                    
                    $urlPdf = '/uploads/' . $nomePdf;
                    
                    sendSuccess([
                        'pdf' => $nomePdf,
                        'url' => $urlPdf,
                        'criado_em' => date('Y-m-d H:i:s')
                    ], 'PDF gerado com sucesso', 201);
                    
                } catch (Exception $e) {
                    sendError('Erro ao gerar PDF: ' . $e->getMessage(), 500);
                }
            }
            
            /**
             * POST /api/relatorios/:id/ia-improve
             * Melhora descrição com IA
             */
            elseif ($acao === 'ia-improve' && $method === 'POST') {
                // Busca relatório
                $relatorio = $db->queryOne(
                    "SELECT * FROM relatorios WHERE id = ?",
                    [$id]
                );
                
                if (!$relatorio) {
                    sendError('Relatório não encontrado', 404);
                }
                
                try {
                    // Usa classe de IA se disponível
                    if (class_exists('IA')) {
                        $ia = new IA();
                        $descricaoMelhorada = $ia->melhorarTexto($relatorio['descricao']);
                        
                        // Atualiza descrição
                        $db->update(
                            'relatorios',
                            [
                                'descricao' => $descricaoMelhorada,
                                'atualizado_em' => date('Y-m-d H:i:s'),
                                'atualizado_por' => $usuario['id']
                            ],
                            'id = ?',
                            [$id]
                        );
                        
                        sendSuccess([
                            'descricao_original' => $relatorio['descricao'],
                            'descricao_melhorada' => $descricaoMelhorada
                        ], 'Descrição melhorada com sucesso', 200);
                    } else {
                        sendError('Serviço de IA não disponível', 503);
                    }
                    
                } catch (Exception $e) {
                    sendError('Erro ao melhorar descrição: ' . $e->getMessage(), 500);
                }
            }
            
            else {
                sendError('Ação não encontrada', 404);
            }
        } else {
            
            if ($method === 'GET') {
                // Busca relatório
                $relatorio = $db->queryOne(
                    "SELECT r.*, c.nome as cliente_nome
                     FROM relatorios r
                     LEFT JOIN clientes c ON r.cliente_id = c.id
                     WHERE r.id = ?",
                    [$id]
                );
                
                if (!$relatorio) {
                    sendError('Relatório não encontrado', 404);
                }
                
                // Busca fotos
                $fotos = $db->find('relatorios_fotos', [
                    'where' => 'relatorio_id = ?',
                    'params' => [$id],
                    'order' => 'criado_em ASC'
                ]);
                
                $relatorio['fotos'] = $fotos;
                
                sendSuccess($relatorio, 'Relatório obtido com sucesso', 200);
            }
            
            /**
             * PUT /api/relatorios/:id
             * Atualiza relatório existente
             */
            elseif ($method === 'PUT') {
                // Busca relatório existente
                $relatorio = $db->queryOne(
                    "SELECT * FROM relatorios WHERE id = ?",
                    [$id]
                );
                
                if (!$relatorio) {
                    sendError('Relatório não encontrado', 404);
                }
                
                // Prepara dados para atualização
                $dados = [];
                
                if (isset($input['titulo'])) {
                    $dados['titulo'] = Validator::sanitizeString($input['titulo']);
                }
                
                if (isset($input['descricao'])) {
                    $dados['descricao'] = Validator::sanitizeString($input['descricao']);
                }
                
                if (isset($input['tarefas_realizadas'])) {
                    $dados['tarefas_realizadas'] = empty($input['tarefas_realizadas']) ? null : Validator::sanitizeString($input['tarefas_realizadas']);
                }
                
                if (isset($input['status'])) {
                    if (!in_array($input['status'], ['rascunho', 'concluido', 'aprovado'])) {
                        sendError('Status inválido', 400);
                    }
                    $dados['status'] = $input['status'];
                }
                
                $dados['atualizado_em'] = date('Y-m-d H:i:s');
                $dados['atualizado_por'] = $usuario['id'];
                
                if (empty($dados)) {
                    sendError('Nenhum dado para atualizar', 400);
                }
                
                // Atualiza no banco
                $db->update('relatorios', $dados, 'id = ?', [$id]);
                
                // Busca relatório atualizado
                $relatorioAtualizado = $db->queryOne(
                    "SELECT * FROM relatorios WHERE id = ?",
                    [$id]
                );
                
                sendSuccess($relatorioAtualizado, 'Relatório atualizado com sucesso', 200);
            }
            
            else {
                sendError('Método não permitido', 405);
            }
        }
        break;
}
?>
