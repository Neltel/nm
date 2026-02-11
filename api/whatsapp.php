<?php
/**
 * ARQUIVO: whatsapp.php
 * 
 * Função: Endpoints de integração com WhatsApp
 * Entrada: Dados de mensagem, documentos, templates
 * Processamento: Envia mensagens, documentos, templates via WhatsApp
 * Saída: Status de envio, confirmação, mensagens de sucesso/erro
 * 
 * Endpoints disponíveis:
 * - POST /api/whatsapp/send - Envia mensagem simples
 * - POST /api/whatsapp/send-document - Envia documento (imagem, PDF, etc)
 * - POST /api/whatsapp/send-template - Envia template pré-configurado
 * - GET /api/whatsapp/status - Verifica status da conexão
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

// Roteia para ação apropriada
switch ($subroute) {
    
    /**
     * GET /api/whatsapp/status
     * Verifica status da conexão com WhatsApp
     */
    case 'status':
        if ($method !== 'GET') {
            sendError('Método não permitido', 405);
        }
        
        // TODO: Implementar verificação real com API do WhatsApp
        // Por enquanto, retorna status simulado
        
        $status = [
            'conectado' => true,
            'numero_telefone' => getenv('WHATSAPP_PHONE') ?: 'não configurado',
            'api_ativa' => !empty(getenv('WHATSAPP_API_KEY')),
            'ultima_sincronizacao' => date('Y-m-d H:i:s'),
            'mensagens_fila' => 0
        ];
        
        sendSuccess($status, 'Status da conexão obtido com sucesso', 200);
        break;
    
    /**
     * POST /api/whatsapp/send
     * Envia mensagem simples via WhatsApp
     * Requer: telefone (com código do país, ex: 5511999999999), mensagem
     * Opcionais: cliente_id, tipo_mensagem
     */
    case 'send':
        if ($method !== 'POST') {
            sendError('Método não permitido', 405);
        }
        
        // Valida entrada
        if (empty($input['telefone'])) {
            sendError('Telefone é obrigatório', 400);
        }
        
        if (empty($input['mensagem'])) {
            sendError('Mensagem é obrigatória', 400);
        }
        
        // Remove caracteres especiais do telefone
        $telefone = preg_replace('/[^0-9]/', '', $input['telefone']);
        
        // Valida formato do telefone
        if (strlen($telefone) < 10) {
            sendError('Telefone inválido', 400);
        }
        
        // Garante que tem código do país (55 para Brasil)
        if (strlen($telefone) === 10 || strlen($telefone) === 11) {
            if (!preg_match('/^55/', $telefone)) {
                $telefone = '55' . $telefone;
            }
        }
        
        // Sanitiza mensagem
        $mensagem = Validator::sanitizeString($input['mensagem']);
        
        if (strlen($mensagem) > 4096) {
            sendError('Mensagem muito longa (máximo 4096 caracteres)', 400);
        }
        
        // Valida cliente_id se fornecido
        $cliente_id = null;
        if (!empty($input['cliente_id'])) {
            $cliente_id = intval($input['cliente_id']);
            
            $cliente = $db->queryOne(
                "SELECT id FROM clientes WHERE id = ?",
                [$cliente_id]
            );
            
            if (!$cliente) {
                sendError('Cliente não encontrado', 404);
            }
        }
        
        // TODO: Implementar integração real com API do WhatsApp
        // Exemplo usando Twilio, MessageBird, WhatsApp Business API, etc.
        
        $tipo_mensagem = isset($input['tipo_mensagem']) ? $input['tipo_mensagem'] : 'texto';
        
        // Registra tentativa de envio
        $registroEnvio = [
            'telefone' => $telefone,
            'mensagem' => $mensagem,
            'cliente_id' => $cliente_id,
            'tipo' => $tipo_mensagem,
            'status_envio' => 'pendente',
            'criado_em' => date('Y-m-d H:i:s'),
            'criado_por' => $usuario['id']
        ];
        
        // Insere registro de envio
        $envioId = $db->insert('whatsapp_logs', $registroEnvio);
        
        // Simula envio (remover em produção)
        $resposta = [
            'id_envio' => $envioId,
            'telefone' => $telefone,
            'status' => 'enviado',
            'mensagem_id' => 'msg_' . uniqid(),
            'data_envio' => date('Y-m-d H:i:s'),
            'tipo' => $tipo_mensagem
        ];
        
        sendSuccess($resposta, 'Mensagem enviada com sucesso', 201);
        break;
    
    /**
     * POST /api/whatsapp/send-document
     * Envia documento via WhatsApp
     * Requer: telefone, documento (URL ou caminho local)
     * Opcionais: cliente_id, descricao, tipo_documento
     */
    case 'send-document':
        if ($method !== 'POST') {
            sendError('Método não permitido', 405);
        }
        
        // Valida entrada
        if (empty($input['telefone'])) {
            sendError('Telefone é obrigatório', 400);
        }
        
        if (empty($input['documento'])) {
            sendError('Caminho do documento é obrigatório', 400);
        }
        
        // Remove caracteres especiais do telefone
        $telefone = preg_replace('/[^0-9]/', '', $input['telefone']);
        
        // Valida formato do telefone
        if (strlen($telefone) < 10) {
            sendError('Telefone inválido', 400);
        }
        
        // Garante que tem código do país
        if (!preg_match('/^55/', $telefone) && (strlen($telefone) === 10 || strlen($telefone) === 11)) {
            $telefone = '55' . $telefone;
        }
        
        $documento = $input['documento'];
        
        // Valida URL ou caminho local
        if (filter_var($documento, FILTER_VALIDATE_URL)) {
            $urlDocumento = $documento;
        } else {
            // Verifica se arquivo existe localmente
            $caminhoLocal = __DIR__ . '/../' . $documento;
            
            if (!file_exists($caminhoLocal)) {
                sendError('Arquivo não encontrado', 404);
            }
            
            $urlDocumento = $_SERVER['HTTP_HOST'] . '/api/' . $documento;
        }
        
        // Valida cliente_id se fornecido
        $cliente_id = null;
        if (!empty($input['cliente_id'])) {
            $cliente_id = intval($input['cliente_id']);
            
            $cliente = $db->queryOne(
                "SELECT id FROM clientes WHERE id = ?",
                [$cliente_id]
            );
            
            if (!$cliente) {
                sendError('Cliente não encontrado', 404);
            }
        }
        
        $tipo_documento = isset($input['tipo_documento']) ? $input['tipo_documento'] : 'arquivo';
        $descricao = isset($input['descricao']) ? Validator::sanitizeString($input['descricao']) : null;
        
        // Registra tentativa de envio
        $registroEnvio = [
            'telefone' => $telefone,
            'documento' => $documento,
            'cliente_id' => $cliente_id,
            'tipo' => 'documento',
            'subtipo' => $tipo_documento,
            'descricao' => $descricao,
            'status_envio' => 'pendente',
            'criado_em' => date('Y-m-d H:i:s'),
            'criado_por' => $usuario['id']
        ];
        
        // Insere registro de envio
        $envioId = $db->insert('whatsapp_logs', $registroEnvio);
        
        // Simula envio
        $resposta = [
            'id_envio' => $envioId,
            'telefone' => $telefone,
            'status' => 'enviado',
            'mensagem_id' => 'msg_' . uniqid(),
            'data_envio' => date('Y-m-d H:i:s'),
            'tipo' => 'documento',
            'documento_url' => $urlDocumento
        ];
        
        sendSuccess($resposta, 'Documento enviado com sucesso', 201);
        break;
    
    /**
     * POST /api/whatsapp/send-template
     * Envia template pré-configurado via WhatsApp
     * Requer: telefone, template_id
     * Opcionais: cliente_id, variaveis (array de substituições)
     */
    case 'send-template':
        if ($method !== 'POST') {
            sendError('Método não permitido', 405);
        }
        
        // Valida entrada
        if (empty($input['telefone'])) {
            sendError('Telefone é obrigatório', 400);
        }
        
        if (empty($input['template_id'])) {
            sendError('ID do template é obrigatório', 400);
        }
        
        // Remove caracteres especiais do telefone
        $telefone = preg_replace('/[^0-9]/', '', $input['telefone']);
        
        // Valida formato do telefone
        if (strlen($telefone) < 10) {
            sendError('Telefone inválido', 400);
        }
        
        // Garante que tem código do país
        if (!preg_match('/^55/', $telefone) && (strlen($telefone) === 10 || strlen($telefone) === 11)) {
            $telefone = '55' . $telefone;
        }
        
        $template_id = intval($input['template_id']);
        
        // Busca template
        $template = $db->queryOne(
            "SELECT * FROM whatsapp_templates WHERE id = ?",
            [$template_id]
        );
        
        if (!$template) {
            sendError('Template não encontrado', 404);
        }
        
        // Processa variáveis se fornecidas
        $mensagem = $template['conteudo'];
        
        if (!empty($input['variaveis']) && is_array($input['variaveis'])) {
            foreach ($input['variaveis'] as $chave => $valor) {
                $mensagem = str_replace('{{' . $chave . '}}', $valor, $mensagem);
            }
        }
        
        // Valida cliente_id se fornecido
        $cliente_id = null;
        if (!empty($input['cliente_id'])) {
            $cliente_id = intval($input['cliente_id']);
            
            $cliente = $db->queryOne(
                "SELECT id FROM clientes WHERE id = ?",
                [$cliente_id]
            );
            
            if (!$cliente) {
                sendError('Cliente não encontrado', 404);
            }
        }
        
        // Registra tentativa de envio
        $registroEnvio = [
            'telefone' => $telefone,
            'mensagem' => $mensagem,
            'cliente_id' => $cliente_id,
            'tipo' => 'template',
            'template_id' => $template_id,
            'variaveis' => json_encode($input['variaveis'] ?? []),
            'status_envio' => 'pendente',
            'criado_em' => date('Y-m-d H:i:s'),
            'criado_por' => $usuario['id']
        ];
        
        // Insere registro de envio
        $envioId = $db->insert('whatsapp_logs', $registroEnvio);
        
        // Simula envio
        $resposta = [
            'id_envio' => $envioId,
            'telefone' => $telefone,
            'status' => 'enviado',
            'mensagem_id' => 'msg_' . uniqid(),
            'data_envio' => date('Y-m-d H:i:s'),
            'tipo' => 'template',
            'template_id' => $template_id,
            'mensagem_processada' => $mensagem
        ];
        
        sendSuccess($resposta, 'Template enviado com sucesso', 201);
        break;
    
    default:
        sendError('Endpoint não encontrado', 404);
        break;
}
?>
