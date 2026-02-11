<?php
/**
 * ARQUIVO: ia.php
 * 
 * Função: Endpoints de integração com IA (Inteligência Artificial)
 * Entrada: Texto para melhorar, dados para gerar checklists, contexto para assistente
 * Processamento: Processa requisições com IA, gera sugestões e assistência
 * Saída: Textos melhorados, checklists gerados, respostas do assistente
 * 
 * Endpoints disponíveis:
 * - POST /api/ia/improve-text - Melhora e refina texto
 * - POST /api/ia/generate-checklist - Gera checklist baseado em contexto
 * - POST /api/ia/assistente - Assistente de IA geral
 * - GET /api/ia/status - Verifica status da conexão com IA
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
     * GET /api/ia/status
     * Verifica status da conexão com IA
     */
    case 'status':
        if ($method !== 'GET') {
            sendError('Método não permitido', 405);
        }
        
        // TODO: Verificar real com API de IA (OpenAI, Claude, etc)
        
        $status = [
            'conectado' => true,
            'servico' => getenv('IA_SERVICE') ?: 'openai',
            'modelo' => getenv('IA_MODEL') ?: 'gpt-3.5-turbo',
            'ativo' => !empty(getenv('IA_API_KEY')),
            'uso_mes' => [
                'requisicoes' => 45,
                'tokens_usados' => 128945,
                'limite_tokens' => 1000000
            ],
            'ultima_sincronizacao' => date('Y-m-d H:i:s')
        ];
        
        sendSuccess($status, 'Status da IA obtido com sucesso', 200);
        break;
    
    /**
     * POST /api/ia/improve-text
     * Melhora e refina texto
     * Requer: texto
     * Opcionais: tipo (email, descricao, titulo, proposta), tom (formal, informal, profissional)
     */
    case 'improve-text':
        if ($method !== 'POST') {
            sendError('Método não permitido', 405);
        }
        
        // Valida entrada
        if (empty($input['texto'])) {
            sendError('Texto é obrigatório', 400);
        }
        
        $texto = $input['texto'];
        
        if (strlen($texto) > 5000) {
            sendError('Texto muito longo (máximo 5000 caracteres)', 400);
        }
        
        if (strlen($texto) < 10) {
            sendError('Texto muito curto (mínimo 10 caracteres)', 400);
        }
        
        // Parâmetros opcionais
        $tipo = isset($input['tipo']) ? $input['tipo'] : 'geral';
        $tom = isset($input['tom']) ? $input['tom'] : 'profissional';
        
        // Valida tipo
        $tiposValidos = ['email', 'descricao', 'titulo', 'proposta', 'geral', 'relatorio'];
        if (!in_array($tipo, $tiposValidos)) {
            sendError('Tipo de texto inválido', 400);
        }
        
        // Valida tom
        $tonsValidos = ['formal', 'informal', 'profissional', 'amigavel', 'tecnico'];
        if (!in_array($tom, $tonsValidos)) {
            sendError('Tom inválido', 400);
        }
        
        // Constrói prompt para IA
        $prompt = "Melhore o seguinte texto de tipo '{$tipo}' com tom '{$tom}'. ";
        $prompt .= "Mantenha o significado original mas refine a qualidade, clareza e impacto. ";
        $prompt .= "Retorne apenas o texto melhorado sem explicações.\n\n";
        $prompt .= "Texto original:\n{$texto}";
        
        // TODO: Integrar com API de IA real
        // Exemplo: 
        // $textoMelhorado = callIAAPI($prompt);
        
        // Simulação - remover em produção
        $textoMelhorado = $texto . "\n\n[Versão melhorada pela IA com tom {$tom}]";
        
        // Registra uso
        $registroUso = [
            'tipo_uso' => 'improve-text',
            'usuario_id' => $usuario['id'],
            'tokens_entrada' => strlen($texto) / 4,
            'tokens_saida' => strlen($textoMelhorado) / 4,
            'parametros' => json_encode(['tipo' => $tipo, 'tom' => $tom]),
            'criado_em' => date('Y-m-d H:i:s')
        ];
        
        $db->insert('ia_uso_logs', $registroUso);
        
        $resposta = [
            'texto_original' => $texto,
            'texto_melhorado' => $textoMelhorado,
            'tipo' => $tipo,
            'tom' => $tom,
            'melhorias_aplicadas' => [
                'clareza' => true,
                'concisao' => true,
                'impacto' => true
            ]
        ];
        
        sendSuccess($resposta, 'Texto melhorado com sucesso', 200);
        break;
    
    /**
     * POST /api/ia/generate-checklist
     * Gera checklist baseado em contexto
     * Requer: contexto (assunto)
     * Opcionais: nivel (basico, intermediario, avancado), idioma
     */
    case 'generate-checklist':
        if ($method !== 'POST') {
            sendError('Método não permitido', 405);
        }
        
        // Valida entrada
        if (empty($input['contexto'])) {
            sendError('Contexto é obrigatório', 400);
        }
        
        $contexto = Validator::sanitizeString($input['contexto']);
        
        if (strlen($contexto) > 500) {
            sendError('Contexto muito longo (máximo 500 caracteres)', 400);
        }
        
        if (strlen($contexto) < 3) {
            sendError('Contexto muito curto (mínimo 3 caracteres)', 400);
        }
        
        // Parâmetros opcionais
        $nivel = isset($input['nivel']) ? $input['nivel'] : 'intermediario';
        $idioma = isset($input['idioma']) ? $input['idioma'] : 'pt-br';
        
        // Valida nível
        $niveisValidos = ['basico', 'intermediario', 'avancado'];
        if (!in_array($nivel, $niveisValidos)) {
            sendError('Nível inválido', 400);
        }
        
        // Constrói prompt para IA
        $prompt = "Gere um checklist em português de {$nivel} complexidade para: {$contexto}\n";
        $prompt .= "Retorne como JSON array com objetos contendo 'item' (string) e 'descricao' (string).\n";
        $prompt .= "Mínimo 5 e máximo 15 itens.";
        
        // TODO: Integrar com API de IA real
        
        // Simulação
        $checklist = [
            ['item' => 'Item 1', 'descricao' => 'Descrição do primeiro item'],
            ['item' => 'Item 2', 'descricao' => 'Descrição do segundo item'],
            ['item' => 'Item 3', 'descricao' => 'Descrição do terceiro item'],
            ['item' => 'Item 4', 'descricao' => 'Descrição do quarto item'],
            ['item' => 'Item 5', 'descricao' => 'Descrição do quinto item']
        ];
        
        // Registra uso
        $registroUso = [
            'tipo_uso' => 'generate-checklist',
            'usuario_id' => $usuario['id'],
            'tokens_entrada' => strlen($contexto) / 4,
            'tokens_saida' => strlen(json_encode($checklist)) / 4,
            'parametros' => json_encode(['nivel' => $nivel, 'idioma' => $idioma]),
            'criado_em' => date('Y-m-d H:i:s')
        ];
        
        $db->insert('ia_uso_logs', $registroUso);
        
        $resposta = [
            'contexto' => $contexto,
            'nivel' => $nivel,
            'total_itens' => count($checklist),
            'checklist' => $checklist
        ];
        
        sendSuccess($resposta, 'Checklist gerado com sucesso', 200);
        break;
    
    /**
     * POST /api/ia/assistente
     * Assistente de IA geral
     * Requer: pergunta
     * Opcionais: contexto, historico (array de trocas anteriores)
     */
    case 'assistente':
        if ($method !== 'POST') {
            sendError('Método não permitido', 405);
        }
        
        // Valida entrada
        if (empty($input['pergunta'])) {
            sendError('Pergunta é obrigatória', 400);
        }
        
        $pergunta = Validator::sanitizeString($input['pergunta']);
        
        if (strlen($pergunta) > 2000) {
            sendError('Pergunta muito longa (máximo 2000 caracteres)', 400);
        }
        
        if (strlen($pergunta) < 3) {
            sendError('Pergunta muito curta (mínimo 3 caracteres)', 400);
        }
        
        // Parâmetros opcionais
        $contexto = isset($input['contexto']) ? Validator::sanitizeString($input['contexto']) : null;
        $historico = isset($input['historico']) && is_array($input['historico']) ? $input['historico'] : [];
        
        // Limita histórico
        $historico = array_slice($historico, -10);
        
        // Constrói mensagem para IA
        $mensagem = "Você é um assistente de negócios útil e profissional.\n";
        
        if (!empty($contexto)) {
            $mensagem .= "Contexto: {$contexto}\n\n";
        }
        
        $mensagem .= "Pergunta: {$pergunta}";
        
        // TODO: Integrar com API de IA real com suporte a histórico
        
        // Simulação
        $resposta_ia = "Essa é uma ótima pergunta. Com base no contexto fornecido, aqui estão alguns pontos a considerar:\n\n";
        $resposta_ia .= "1. Primeiro ponto importante\n";
        $resposta_ia .= "2. Segundo ponto relevante\n";
        $resposta_ia .= "3. Terceiro aspecto a levar em conta\n\n";
        $resposta_ia .= "Recomendo avaliar todos esses fatores na sua situação específica.";
        
        // Registra uso
        $registroUso = [
            'tipo_uso' => 'assistente',
            'usuario_id' => $usuario['id'],
            'tokens_entrada' => (strlen($pergunta) + strlen($contexto ?? '')) / 4,
            'tokens_saida' => strlen($resposta_ia) / 4,
            'parametros' => json_encode(['tem_contexto' => !empty($contexto), 'historico_itens' => count($historico)]),
            'criado_em' => date('Y-m-d H:i:s')
        ];
        
        $db->insert('ia_uso_logs', $registroUso);
        
        $resposta = [
            'pergunta' => $pergunta,
            'contexto' => $contexto,
            'resposta' => $resposta_ia,
            'confianca' => 0.92,
            'tempo_resposta_ms' => 1230,
            'fontes' => []
        ];
        
        sendSuccess($resposta, 'Resposta gerada com sucesso', 200);
        break;
    
    default:
        sendError('Endpoint não encontrado', 404);
        break;
}
?>
