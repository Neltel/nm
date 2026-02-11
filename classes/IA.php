<?php
/**
 * Classe IA
 * 
 * Função: Integra serviços de Inteligência Artificial para melhorias de texto e geração de conteúdo
 * Entrada: Textos, prompts e solicitações de processamento
 * Processamento: Envia requisições para API de IA (OpenAI GPT, Google Gemini, etc)
 * Saída: Textos melhorados, checklists gerados, assistência contextual
 * Uso: $ia = new IA(); $ia->improveText($texto);
 */

class IA {
    
    private $apiProvider;
    private $apiKey;
    private $apiUrl;
    private $model;
    private $logPath;
    private $maxTokens;
    private $temperature;
    
    /**
     * Construtor - Inicializa configurações de acesso à API de IA
     * 
     * Função: Carrega credenciais e configurações de provedor de IA
     * Entrada: Nenhuma (usa constantes/arquivo de configuração)
     * Processamento: Define provider (OpenAI, Gemini, etc), URL, modelo, tokens
     * Saída: Objeto IA pronto para usar
     * 
     * Configura padrões:
     * - IA_PROVIDER: 'openai' | 'gemini' | 'claude'
     * - IA_API_KEY: Chave de acesso à API
     * - IA_MODEL: Modelo a usar (gpt-4, gpt-3.5-turbo, gemini-pro, etc)
     * - IA_MAX_TOKENS: Máximo de tokens na resposta
     * - IA_TEMPERATURE: Criatividade da resposta (0-1)
     */
    public function __construct() {
        // Configurações de provedor de IA
        $this->apiProvider = defined('IA_PROVIDER') ? IA_PROVIDER : 'openai';
        $this->apiKey = defined('IA_API_KEY') ? IA_API_KEY : '';
        $this->model = defined('IA_MODEL') ? IA_MODEL : 'gpt-3.5-turbo';
        $this->maxTokens = defined('IA_MAX_TOKENS') ? IA_MAX_TOKENS : 1000;
        $this->temperature = defined('IA_TEMPERATURE') ? IA_TEMPERATURE : 0.7;
        
        // Define URL da API conforme provider
        $this->setApiUrl();
        
        $this->logPath = __DIR__ . '/../logs/';
        
        // Cria diretório de logs se não existir
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }
    
    /**
     * Define URL da API conforme provedor
     * 
     * Função: Configura endpoint correto de acordo com provedor selecionado
     * Entrada: Nenhuma (usa $this->apiProvider)
     * Processamento: Atribui URL do provider
     * Saída: $this->apiUrl configurado
     */
    private function setApiUrl() {
        switch (strtolower($this->apiProvider)) {
            case 'openai':
                $this->apiUrl = 'https://api.openai.com/v1/chat/completions';
                break;
            case 'gemini':
                $this->apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/' . $this->model . ':generateContent';
                break;
            case 'claude':
                $this->apiUrl = 'https://api.anthropic.com/v1/messages';
                break;
            default:
                $this->apiUrl = 'https://api.openai.com/v1/chat/completions';
                $this->apiProvider = 'openai';
        }
    }
    
    /**
     * Melhora texto fornecido usando IA
     * 
     * Função: Aprimora texto existente em aspectos gramaticais, clareza, profissionalismo
     * Entrada: $texto (string) - Texto a melhorar
     *          $estilo (string) - Estilo desejado (profissional, casual, formal, etc)
     *          $idioma (string) - Idioma de saída (pt_BR, en_US, etc)
     * Processamento: Envia prompt à API de IA, processa resposta
     * Saída: Texto melhorado e otimizado
     * 
     * @param string $texto Texto a melhorar (máx 5000 caracteres)
     * @param string $estilo Estilo: profissional, casual, formal, técnico
     * @param string $idioma Idioma: pt_BR, en_US, es_ES, etc
     * @return string Texto melhorado
     * 
     * @throws Exception Se erro de API ou entrada inválida
     * 
     * Uso: $ia = new IA();
     *      $texto = "Oi, tudo bem? Precisamos de um relatorio sobre vendas do mes.";
     *      $melhorado = $ia->improveText($texto, 'profissional', 'pt_BR');
     *      echo $melhorado;
     *      // Saída: "Prezado, segue o relatório mensal de vendas conforme solicitado."
     */
    public function improveText($texto, $estilo = 'profissional', $idioma = 'pt_BR') {
        try {
            // Validações
            if (empty($texto)) {
                throw new Exception("Texto não pode estar vazio");
            }
            
            if (strlen($texto) > 5000) {
                throw new Exception("Texto excede 5000 caracteres");
            }
            
            // Estilos permitidos
            $estilosPermitidos = ['profissional', 'casual', 'formal', 'técnico', 'criativo'];
            if (!in_array($estilo, $estilosPermitidos)) {
                $estilo = 'profissional';
            }
            
            // Monta prompt
            $prompt = "Melhore o seguinte texto em estilo '$estilo' e no idioma '$idioma'. "
                    . "Mantenha o significado original mas torne mais claro, profissional e bem estruturado.\n\n"
                    . "Texto original:\n"
                    . $texto
                    . "\n\nTexto melhorado:";
            
            // Faz requisição à API
            $response = $this->callAI($prompt, 'text');
            
            // Log
            $this->logInfo("Texto melhorado com sucesso. Estilo: $estilo");
            
            return $response;
            
        } catch (Exception $e) {
            $this->logError("Erro ao melhorar texto: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Gera checklist baseado em descrição
     * 
     * Função: Cria lista de tarefas/verificação automática usando IA
     * Entrada: $descricao (string) - Descrição do projeto/processo
     *          $tipo (string) - Tipo de checklist (projeto, vendas, atendimento, etc)
     *          $idioma (string) - Idioma
     * Processamento: Analisa descrição e gera checklist estruturado em JSON
     * Saída: Array PHP com itens do checklist
     * 
     * @param string $descricao Descrição do projeto/processo
     * @param string $tipo Tipo: projeto, vendas, atendimento, manutencao, etc
     * @param string $idioma Idioma: pt_BR, en_US, es_ES
     * @return array Array com checklist estruturado:
     *               [
     *                   'titulo' => 'Checklist...',
     *                   'items' => [
     *                       ['tarefa' => '...', 'prioridade' => 'alta', 'tempo' => '30min'],
     *                       ...
     *                   ]
     *               ]
     * 
     * @throws Exception Se erro de API
     * 
     * Uso: $ia = new IA();
     *      $checklist = $ia->generateChecklist(
     *          'Lançar novo produto de software com integração OpenAI',
     *          'projeto',
     *          'pt_BR'
     *      );
     *      foreach ($checklist['items'] as $item) {
     *          echo "☐ " . $item['tarefa'] . " (Prioridade: " . $item['prioridade'] . ")\n";
     *      }
     */
    public function generateChecklist($descricao, $tipo = 'projeto', $idioma = 'pt_BR') {
        try {
            // Validações
            if (empty($descricao)) {
                throw new Exception("Descrição não pode estar vazia");
            }
            
            if (strlen($descricao) > 2000) {
                throw new Exception("Descrição excede 2000 caracteres");
            }
            
            // Tipos permitidos
            $tiposPermitidos = ['projeto', 'vendas', 'atendimento', 'manutencao', 'testes', 'implantacao'];
            if (!in_array($tipo, $tiposPermitidos)) {
                $tipo = 'projeto';
            }
            
            // Monta prompt
            $prompt = "Gere um checklist detalhado em formato JSON para a seguinte descrição. "
                    . "Tipo: $tipo. Idioma: $idioma.\n\n"
                    . "Descrição:\n$descricao\n\n"
                    . "Retorne APENAS um JSON válido no seguinte formato (sem markdown):\n"
                    . "{\n"
                    . "  \"titulo\": \"Nome do Checklist\",\n"
                    . "  \"descricao\": \"Descrição breve\",\n"
                    . "  \"items\": [\n"
                    . "    {\"tarefa\": \"Descrição da tarefa\", \"prioridade\": \"alta/média/baixa\", \"tempo\": \"estimativa\"},\n"
                    . "    ...\n"
                    . "  ]\n"
                    . "}";
            
            // Faz requisição
            $response = $this->callAI($prompt, 'json');
            
            // Valida e processa JSON
            $checklist = json_decode($response, true);
            if (!$checklist || !isset($checklist['items'])) {
                throw new Exception("Resposta de IA em formato inválido");
            }
            
            // Log
            $this->logInfo("Checklist gerado com sucesso. Tipo: $tipo. Items: " . count($checklist['items']));
            
            return $checklist;
            
        } catch (Exception $e) {
            $this->logError("Erro ao gerar checklist: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Assistente contextual para perguntas gerais
     * 
     * Função: Fornece respostas a perguntas de negócio, técnicas ou gerais
     * Entrada: $pergunta (string) - Pergunta do usuário
     *          $contexto (string) - Contexto adicional (opcional)
     *          $tipo (string) - Tipo de resposta desejada
     * Processamento: Envia pergunta à API mantendo contexto, processa resposta
     * Saída: String com resposta da IA
     * 
     * @param string $pergunta Pergunta a fazer à IA
     * @param string|null $contexto Contexto adicional (empresa, projeto, etc)
     * @param string $tipo Tipo: resposta, explicacao, dica, codigo
     * @return string Resposta da IA
     * 
     * @throws Exception Se erro de API
     * 
     * Uso: $ia = new IA();
     *      $resposta = $ia->assistente(
     *          "Como melhorar a experiência do usuário em uma loja online?",
     *          "Somos uma loja de eletrônicos",
     *          'resposta'
     *      );
     *      echo $resposta;
     */
    public function assistente($pergunta, $contexto = null, $tipo = 'resposta') {
        try {
            // Validações
            if (empty($pergunta)) {
                throw new Exception("Pergunta não pode estar vazia");
            }
            
            if (strlen($pergunta) > 3000) {
                throw new Exception("Pergunta excede 3000 caracteres");
            }
            
            // Tipos de resposta
            $tiposPermitidos = ['resposta', 'explicacao', 'dica', 'codigo', 'lista', 'analise'];
            if (!in_array($tipo, $tiposPermitidos)) {
                $tipo = 'resposta';
            }
            
            // Monta prompt com contexto
            $prompt = "Você é um assistente especializado em negócios e tecnologia.\n\n";
            
            if (!empty($contexto)) {
                $prompt .= "CONTEXTO:\n$contexto\n\n";
            }
            
            $prompt .= "Tipo de resposta desejada: $tipo\n";
            $prompt .= "PERGUNTA:\n$pergunta\n\n";
            $prompt .= "Forneça uma resposta completa e útil:";
            
            // Faz requisição
            $response = $this->callAI($prompt, 'text');
            
            // Log
            $this->logInfo("Assistente respondeu pergunta. Tipo: $tipo");
            
            return $response;
            
        } catch (Exception $e) {
            $this->logError("Erro no assistente: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Faz requisição à API de IA
     * 
     * Função: Executa chamada ao provedor de IA configurado
     * Entrada: $prompt (string) - Prompt/pergunta para IA
     *          $tipoResposta (string) - Tipo de resposta esperada (text, json)
     * Processamento: Formata requisição, envia via cURL, processa resposta
     * Saída: String com resposta da IA
     * 
     * @param string $prompt Prompt a enviar
     * @param string $tipoResposta Tipo: text ou json
     * @return string Resposta da IA
     * 
     * @throws Exception Em caso de erro
     */
    private function callAI($prompt, $tipoResposta = 'text') {
        try {
            // Validação de credenciais
            if (empty($this->apiKey)) {
                throw new Exception("Chave de API não configurada");
            }
            
            // Prepara payload conforme provider
            switch (strtolower($this->apiProvider)) {
                case 'openai':
                    $payload = $this->prepareOpenAIPayload($prompt, $tipoResposta);
                    $headers = $this->getOpenAIHeaders();
                    break;
                case 'gemini':
                    $payload = $this->prepareGeminiPayload($prompt, $tipoResposta);
                    $headers = $this->getGeminiHeaders();
                    break;
                case 'claude':
                    $payload = $this->prepareClaudePayload($prompt, $tipoResposta);
                    $headers = $this->getClaudeHeaders();
                    break;
                default:
                    throw new Exception("Provider de IA não suportado");
            }
            
            // Faz requisição cURL
            $ch = curl_init($this->apiUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_TIMEOUT => 60,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            // Verifica erros
            if (!$response && $curlError) {
                throw new Exception("Erro de conexão: $curlError");
            }
            
            if ($httpCode >= 400) {
                throw new Exception("Erro HTTP $httpCode na API de IA");
            }
            
            // Processa resposta conforme provider
            $result = $this->parseAIResponse($response);
            
            return $result;
            
        } catch (Exception $e) {
            $this->logError("Erro ao chamar API de IA: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Prepara payload para OpenAI
     */
    private function prepareOpenAIPayload($prompt, $tipoResposta) {
        $payload = [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => $this->temperature,
            'max_tokens' => $this->maxTokens
        ];
        
        if ($tipoResposta === 'json') {
            $payload['response_format'] = ['type' => 'json_object'];
        }
        
        return $payload;
    }
    
    /**
     * Prepara payload para Google Gemini
     */
    private function prepareGeminiPayload($prompt, $tipoResposta) {
        return [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => $this->temperature,
                'maxOutputTokens' => $this->maxTokens
            ]
        ];
    }
    
    /**
     * Prepara payload para Anthropic Claude
     */
    private function prepareClaudePayload($prompt, $tipoResposta) {
        return [
            'model' => $this->model,
            'max_tokens' => $this->maxTokens,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ]
        ];
    }
    
    /**
     * Obtém headers para OpenAI
     */
    private function getOpenAIHeaders() {
        return [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
            'User-Agent: NovoSiteIA/1.0'
        ];
    }
    
    /**
     * Obtém headers para Gemini
     */
    private function getGeminiHeaders() {
        return [
            'Content-Type: application/json',
            'User-Agent: NovoSiteIA/1.0'
        ];
    }
    
    /**
     * Obtém headers para Claude
     */
    private function getClaudeHeaders() {
        return [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
            'anthropic-version: 2023-06-01',
            'User-Agent: NovoSiteIA/1.0'
        ];
    }
    
    /**
     * Processa resposta da API de IA
     * 
     * Função: Decodifica e extrai conteúdo da resposta conforme provider
     * Entrada: $response (string) - Resposta JSON da API
     * Processamento: Decodifica e extrai campo de conteúdo
     * Saída: String com conteúdo da resposta
     */
    private function parseAIResponse($response) {
        $data = json_decode($response, true);
        
        if (!$data) {
            throw new Exception("Resposta inválida da API de IA");
        }
        
        // Extrai conteúdo conforme provider
        switch (strtolower($this->apiProvider)) {
            case 'openai':
                return $data['choices'][0]['message']['content'] ?? '';
            case 'gemini':
                return $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
            case 'claude':
                return $data['content'][0]['text'] ?? '';
            default:
                return '';
        }
    }
    
    /**
     * Registra informação em log
     * 
     * @param string $message Mensagem a registrar
     */
    private function logInfo($message) {
        $this->logToFile('INFO', $message);
    }
    
    /**
     * Registra erro em log
     * 
     * @param string $message Mensagem de erro
     */
    private function logError($message) {
        $this->logToFile('ERROR', $message);
    }
    
    /**
     * Registra mensagem em arquivo de log
     * 
     * @param string $level Nível (INFO, ERROR, WARNING)
     * @param string $message Mensagem
     */
    private function logToFile($level, $message) {
        $logFile = $this->logPath . 'ia_' . date('Y-m-d') . '.log';
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] [{$level}] {$message}\n";
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
}
?>
