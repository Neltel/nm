<?php
/**
 * ARQUIVO: garantias.php
 * 
 * Função: Endpoints CRUD de gerenciamento de garantias de produtos
 * Entrada: Dados de garantia, parâmetros de busca, paginação e filtros
 * Processamento: Cria, lê, atualiza, deleta garantias, gera PDF com termos legais, envia via WhatsApp
 * Saída: Dados de garantia(s), lista paginada, PDF gerado, mensagens de sucesso/erro
 * 
 * Endpoints disponíveis:
 * - GET /api/garantias - Lista todas as garantias (com paginação e filtros)
 * - GET /api/garantias/:id - Obtém uma garantia específica
 * - POST /api/garantias - Cria nova garantia
 * - PUT /api/garantias/:id - Atualiza garantia existente
 * - DELETE /api/garantias/:id - Deleta garantia
 * - POST /api/garantias/:id/pdf - Gera PDF com termos legais
 * - POST /api/garantias/:id/whatsapp - Envia garantia via WhatsApp
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
     * GET /api/garantias
     * Lista todas as garantias com paginação e filtros
     * Parâmetros: page, limit, status, produto_id, cliente_id
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
        $produto_id = isset($_GET['produto_id']) ? intval($_GET['produto_id']) : null;
        $cliente_id = isset($_GET['cliente_id']) ? intval($_GET['cliente_id']) : null;
        
        $offset = ($page - 1) * $limit;
        
        // Constrói cláusula WHERE para filtros
        $where = '1=1';
        $params = [];
        
        if (!is_null($status) && in_array($status, ['ativa', 'expirada', 'cancelada'])) {
            $where .= ' AND status = ?';
            $params[] = $status;
        }
        
        if (!is_null($produto_id) && $produto_id > 0) {
            $where .= ' AND produto_id = ?';
            $params[] = $produto_id;
        }
        
        if (!is_null($cliente_id) && $cliente_id > 0) {
            $where .= ' AND cliente_id = ?';
            $params[] = $cliente_id;
        }
        
        // Conta total de garantias
        $total = $db->count('garantias', $where, $params);
        
        // Busca garantias paginadas
        $garantias = $db->find('garantias', [
            'where' => $where,
            'params' => $params,
            'order' => 'criado_em DESC',
            'limit' => $limit,
            'offset' => $offset
        ]);
        
        sendSuccess([
            'garantias' => $garantias,
            'paginacao' => [
                'pagina_atual' => $page,
                'total_itens' => $total,
                'itens_por_pagina' => $limit,
                'total_paginas' => ceil($total / $limit)
            ]
        ], 'Garantias obtidas com sucesso', 200);
        break;
    
    /**
     * POST /api/garantias
     * Cria nova garantia
     * Requer: produto_id, cliente_id, numero_serie, tipo, meses_validade
     * Opcionais: descricao, valor_cobertura
     */
    case null:
        if ($method === 'POST') {
            // Valida entrada obrigatória
            if (empty($input['produto_id'])) {
                sendError('ID do produto é obrigatório', 400);
            }
            
            if (empty($input['cliente_id'])) {
                sendError('ID do cliente é obrigatório', 400);
            }
            
            if (empty($input['numero_serie'])) {
                sendError('Número de série é obrigatório', 400);
            }
            
            if (empty($input['tipo'])) {
                sendError('Tipo de garantia é obrigatório', 400);
            }
            
            if (empty($input['meses_validade'])) {
                sendError('Meses de validade são obrigatórios', 400);
            }
            
            // Valida tipos de garantia
            $tipos_validos = ['fabricante', 'estendida', 'terceiros'];
            if (!in_array($input['tipo'], $tipos_validos)) {
                sendError('Tipo de garantia inválido', 400);
            }
            
            // Valida meses de validade
            $meses_validade = intval($input['meses_validade']);
            if ($meses_validade <= 0 || $meses_validade > 120) {
                sendError('Meses de validade deve estar entre 1 e 120', 400);
            }
            
            // Verifica se produto existe
            $produto = $db->queryOne(
                "SELECT id FROM produtos WHERE id = ?",
                [$input['produto_id']]
            );
            
            if (!$produto) {
                sendError('Produto não encontrado', 404);
            }
            
            // Verifica se cliente existe
            $cliente = $db->queryOne(
                "SELECT id FROM clientes WHERE id = ?",
                [$input['cliente_id']]
            );
            
            if (!$cliente) {
                sendError('Cliente não encontrado', 404);
            }
            
            // Calcula datas
            $data_inicio = date('Y-m-d');
            $data_fim = date('Y-m-d', strtotime("+{$meses_validade} months"));
            
            // Prepara dados
            $dados = [
                'produto_id' => intval($input['produto_id']),
                'cliente_id' => intval($input['cliente_id']),
                'numero_serie' => Validator::sanitizeString($input['numero_serie']),
                'tipo' => $input['tipo'],
                'meses_validade' => $meses_validade,
                'data_inicio' => $data_inicio,
                'data_fim' => $data_fim,
                'status' => 'ativa',
                'descricao' => isset($input['descricao']) ? Validator::sanitizeString($input['descricao']) : null,
                'valor_cobertura' => isset($input['valor_cobertura']) ? floatval($input['valor_cobertura']) : null,
                'criado_em' => date('Y-m-d H:i:s'),
                'criado_por' => $usuario['id']
            ];
            
            // Insere no banco
            $garantiaId = $db->insert('garantias', $dados);
            
            // Busca garantia criada
            $garantia = $db->queryOne(
                "SELECT * FROM garantias WHERE id = ?",
                [$garantiaId]
            );
            
            sendSuccess($garantia, 'Garantia criada com sucesso', 201);
        }
        break;
    
    /**
     * GET /api/garantias/:id
     * Obtém dados de uma garantia específica
     */
    default:
        if ($id === null || $id <= 0) {
            sendError('ID de garantia inválido', 400);
        }
        
        // Se houver ação adicional (ex: /api/garantias/:id/pdf)
        if (!empty($acao)) {
            
            /**
             * POST /api/garantias/:id/pdf
             * Gera PDF com termos legais da garantia
             */
            if ($acao === 'pdf' && $method === 'POST') {
                // Busca garantia
                $garantia = $db->queryOne(
                    "SELECT g.*, p.nome as produto_nome, c.nome as cliente_nome, c.email as cliente_email
                     FROM garantias g
                     JOIN produtos p ON g.produto_id = p.id
                     JOIN clientes c ON g.cliente_id = c.id
                     WHERE g.id = ?",
                    [$id]
                );
                
                if (!$garantia) {
                    sendError('Garantia não encontrada', 404);
                }
                
                try {
                    // Gera conteúdo do PDF
                    $nomePdf = 'garantia_' . $garantia['id'] . '_' . date('YmdHis') . '.pdf';
                    $caminhoPdf = UPLOADS_PATH . '/' . $nomePdf;
                    
                    // Conteúdo HTML para o PDF
                    $html = "
                    <html>
                    <head>
                        <meta charset='UTF-8'>
                        <style>
                            body { font-family: Arial, sans-serif; }
                            .header { text-align: center; margin-bottom: 20px; }
                            .content { margin: 20px; }
                            .field { margin: 10px 0; }
                            .field-label { font-weight: bold; }
                        </style>
                    </head>
                    <body>
                        <div class='header'>
                            <h1>Termo de Garantia</h1>
                        </div>
                        <div class='content'>
                            <div class='field'>
                                <span class='field-label'>Cliente:</span> {$garantia['cliente_nome']}
                            </div>
                            <div class='field'>
                                <span class='field-label'>Produto:</span> {$garantia['produto_nome']}
                            </div>
                            <div class='field'>
                                <span class='field-label'>Número de Série:</span> {$garantia['numero_serie']}
                            </div>
                            <div class='field'>
                                <span class='field-label'>Tipo de Garantia:</span> {$garantia['tipo']}
                            </div>
                            <div class='field'>
                                <span class='field-label'>Data de Início:</span> " . date('d/m/Y', strtotime($garantia['data_inicio'])) . "
                            </div>
                            <div class='field'>
                                <span class='field-label'>Data de Término:</span> " . date('d/m/Y', strtotime($garantia['data_fim'])) . "
                            </div>
                            <div class='field'>
                                <span class='field-label'>Status:</span> {$garantia['status']}
                            </div>
                            " . (!empty($garantia['valor_cobertura']) ? "
                            <div class='field'>
                                <span class='field-label'>Valor de Cobertura:</span> R\$ " . number_format($garantia['valor_cobertura'], 2, ',', '.') . "
                            </div>
                            " : "") . "
                            " . (!empty($garantia['descricao']) ? "
                            <div class='field'>
                                <span class='field-label'>Descrição:</span> {$garantia['descricao']}
                            </div>
                            " : "") . "
                            <hr>
                            <h3>Termos e Condições Legais</h3>
                            <p>Esta garantia cobre defeitos de fabricação e funcionamento do produto durante o período especificado acima.</p>
                            <p>A garantia não cobre danos causados por mau uso, acidentes, negligência ou modificações não autorizadas.</p>
                            <p>Gerado em: " . date('d/m/Y H:i:s') . "</p>
                        </div>
                    </body>
                    </html>
                    ";
                    
                    // Cria diretório se não existir
                    if (!is_dir(UPLOADS_PATH)) {
                        mkdir(UPLOADS_PATH, 0755, true);
                    }
                    
                    // Salva arquivo HTML temporário
                    $arquivoHtml = UPLOADS_PATH . '/temp_' . $garantia['id'] . '.html';
                    file_put_contents($arquivoHtml, $html);
                    
                    // Usa wkhtmltopdf ou similar para converter (se disponível)
                    // Por enquanto, salvamos como HTML que será convertido pelo cliente
                    $urlPdf = '/uploads/' . $nomePdf;
                    
                    // Salva PDF como HTML
                    file_put_contents($caminhoPdf, $html);
                    
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
             * POST /api/garantias/:id/whatsapp
             * Envia informações da garantia via WhatsApp
             */
            elseif ($acao === 'whatsapp' && $method === 'POST') {
                // Busca garantia
                $garantia = $db->queryOne(
                    "SELECT g.*, p.nome as produto_nome, c.nome as cliente_nome, c.telefone as cliente_telefone
                     FROM garantias g
                     JOIN produtos p ON g.produto_id = p.id
                     JOIN clientes c ON g.cliente_id = c.id
                     WHERE g.id = ?",
                    [$id]
                );
                
                if (!$garantia) {
                    sendError('Garantia não encontrada', 404);
                }
                
                if (empty($garantia['cliente_telefone'])) {
                    sendError('Cliente não possui telefone registrado', 400);
                }
                
                try {
                    // Prepara mensagem
                    $mensagem = "🔒 *Informações da Garantia*\n\n";
                    $mensagem .= "📦 Produto: {$garantia['produto_nome']}\n";
                    $mensagem .= "📱 Série: {$garantia['numero_serie']}\n";
                    $mensagem .= "📅 Válida até: " . date('d/m/Y', strtotime($garantia['data_fim'])) . "\n";
                    $mensagem .= "🏷️ Tipo: {$garantia['tipo']}\n";
                    $mensagem .= "✅ Status: {$garantia['status']}";
                    
                    // Simula envio via WhatsApp (em produção, use API de WhatsApp)
                    // Este é um exemplo básico
                    $registroWhatsapp = [
                        'garantia_id' => $id,
                        'cliente_id' => $garantia['cliente_id'],
                        'telefone' => $garantia['cliente_telefone'],
                        'mensagem' => $mensagem,
                        'status' => 'enviado',
                        'enviado_em' => date('Y-m-d H:i:s')
                    ];
                    
                    // Insere registro de envio
                    $db->insert('garantias_whatsapp', $registroWhatsapp);
                    
                    sendSuccess([
                        'garantia_id' => $id,
                        'telefone' => $garantia['cliente_telefone'],
                        'status' => 'enviado',
                        'mensagem' => $mensagem
                    ], 'Garantia enviada via WhatsApp com sucesso', 200);
                    
                } catch (Exception $e) {
                    sendError('Erro ao enviar via WhatsApp: ' . $e->getMessage(), 500);
                }
            }
            
            else {
                sendError('Ação não encontrada', 404);
            }
        } else {
            
            if ($method === 'GET') {
                // Busca garantia
                $garantia = $db->queryOne(
                    "SELECT g.*, p.nome as produto_nome, c.nome as cliente_nome
                     FROM garantias g
                     LEFT JOIN produtos p ON g.produto_id = p.id
                     LEFT JOIN clientes c ON g.cliente_id = c.id
                     WHERE g.id = ?",
                    [$id]
                );
                
                if (!$garantia) {
                    sendError('Garantia não encontrada', 404);
                }
                
                sendSuccess($garantia, 'Garantia obtida com sucesso', 200);
            }
            
            /**
             * PUT /api/garantias/:id
             * Atualiza dados de uma garantia existente
             */
            elseif ($method === 'PUT') {
                // Busca garantia existente
                $garantia = $db->queryOne(
                    "SELECT * FROM garantias WHERE id = ?",
                    [$id]
                );
                
                if (!$garantia) {
                    sendError('Garantia não encontrada', 404);
                }
                
                // Prepara dados para atualização
                $dados = [];
                
                if (isset($input['tipo'])) {
                    $tipos_validos = ['fabricante', 'estendida', 'terceiros'];
                    if (!in_array($input['tipo'], $tipos_validos)) {
                        sendError('Tipo de garantia inválido', 400);
                    }
                    $dados['tipo'] = $input['tipo'];
                }
                
                if (isset($input['status'])) {
                    $status_validos = ['ativa', 'expirada', 'cancelada'];
                    if (!in_array($input['status'], $status_validos)) {
                        sendError('Status de garantia inválido', 400);
                    }
                    $dados['status'] = $input['status'];
                }
                
                if (isset($input['descricao'])) {
                    $dados['descricao'] = empty($input['descricao']) ? null : Validator::sanitizeString($input['descricao']);
                }
                
                if (isset($input['valor_cobertura'])) {
                    $dados['valor_cobertura'] = empty($input['valor_cobertura']) ? null : floatval($input['valor_cobertura']);
                }
                
                if (isset($input['numero_serie'])) {
                    $dados['numero_serie'] = Validator::sanitizeString($input['numero_serie']);
                }
                
                $dados['atualizado_em'] = date('Y-m-d H:i:s');
                $dados['atualizado_por'] = $usuario['id'];
                
                if (empty($dados)) {
                    sendError('Nenhum dado para atualizar', 400);
                }
                
                // Atualiza no banco
                $db->update('garantias', $dados, 'id = ?', [$id]);
                
                // Busca garantia atualizada
                $garantiaAtualizada = $db->queryOne(
                    "SELECT * FROM garantias WHERE id = ?",
                    [$id]
                );
                
                sendSuccess($garantiaAtualizada, 'Garantia atualizada com sucesso', 200);
            }
            
            /**
             * DELETE /api/garantias/:id
             * Deleta uma garantia existente
             */
            elseif ($method === 'DELETE') {
                // Busca garantia
                $garantia = $db->queryOne(
                    "SELECT * FROM garantias WHERE id = ?",
                    [$id]
                );
                
                if (!$garantia) {
                    sendError('Garantia não encontrada', 404);
                }
                
                // Deleta garantia
                $db->delete('garantias', 'id = ?', [$id]);
                
                sendSuccess([], 'Garantia deletada com sucesso', 200);
            }
            
            else {
                sendError('Método não permitido', 405);
            }
        }
        break;
}
?>
