<?php
/**
 * Classe WhatsApp
 * 
 * Função: Integra envio de mensagens via WhatsApp usando API oficial
 * Entrada: Dados de mensagens (telefone, texto, documentos, templates)
 * Processamento: Formata dados e envia requisições para API WhatsApp Business
 * Saída: Resposta da API ou status de envio
 * Uso: $whatsapp = new WhatsApp(); $whatsapp->sendMessage($numero, $mensagem);
 */

class WhatsApp {
    
    private $apiUrl;
    private $accessToken;
    private $phoneNumberId;
    private $logPath;
    
    /**
     * Construtor - Inicializa credenciais da API WhatsApp
     * 
     * Função: Carrega configurações de acesso à API
     * Entrada: Nenhuma (usa constantes/configurações definidas)
     * Processamento: Define URL da API, token de acesso e ID do número
     * Saída: Objeto WhatsApp pronto para uso
     * 
     * Nota: As credenciais devem estar definidas em constantes ou arquivo de configuração
     * Define padrões:
     * - WHATSAPP_API_URL: https://graph.facebook.com/v18.0/
     * - WHATSAPP_ACCESS_TOKEN: Token de acesso da API
     * - WHATSAPP_PHONE_NUMBER_ID: ID do número de telefone registrado
     */
    public function __construct() {
        // Credenciais da API WhatsApp
        $this->apiUrl = defined('WHATSAPP_API_URL') ? WHATSAPP_API_URL : 'https://graph.facebook.com/v18.0/';
        $this->accessToken = defined('WHATSAPP_ACCESS_TOKEN') ? WHATSAPP_ACCESS_TOKEN : '';
        $this->phoneNumberId = defined('WHATSAPP_PHONE_NUMBER_ID') ? WHATSAPP_PHONE_NUMBER_ID : '';
        $this->logPath = __DIR__ . '/../logs/';
        
        // Cria diretório de logs se não existir
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }
    
    /**
     * Envia mensagem de texto via WhatsApp
     * 
     * Função: Envia mensagem simples de texto para um número WhatsApp
     * Entrada: $numero (string) - Número com código do país (ex: 5511999999999)
     *          $mensagem (string) - Texto da mensagem (máx 4096 caracteres)
     * Processamento: Formata payload JSON e faz requisição POST à API
     * Saída: Array com resposta da API (message_id ou erro)
     * 
     * @param string $numero Número WhatsApp com código de país (55 + DDD + número)
     * @param string $mensagem Texto da mensagem (até 4096 caracteres)
     * @return array Resposta da API com message_id ou erro
     * 
     * @throws Exception Se houver erro na requisição
     * 
     * Uso: $wa = new WhatsApp();
     *      $resultado = $wa->sendMessage('5511999999999', 'Olá! Este é um teste.');
     *      if (isset($resultado['message_id'])) {
     *          echo "Mensagem enviada com sucesso: " . $resultado['message_id'];
     *      }
     */
    public function sendMessage($numero, $mensagem) {
        try {
            // Validação
            if (empty($numero) || empty($mensagem)) {
                throw new Exception("Número e mensagem são obrigatórios");
            }
            
            // Remove formatação do número
            $numero = preg_replace('/[^0-9]/', '', $numero);
            
            // Valida comprimento da mensagem
            if (strlen($mensagem) > 4096) {
                throw new Exception("Mensagem excede 4096 caracteres");
            }
            
            // Monta payload
            $payload = [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $numero,
                'type' => 'text',
                'text' => [
                    'preview_url' => true,
                    'body' => $mensagem
                ]
            ];
            
            // Envia requisição
            $response = $this->makeRequest($payload);
            
            // Log de sucesso
            $this->logInfo("Mensagem enviada para $numero. ID: " . ($response['messages'][0]['id'] ?? 'desconhecido'));
            
            return $response;
            
        } catch (Exception $e) {
            $this->logError("Erro ao enviar mensagem: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Envia documento (arquivo) via WhatsApp
     * 
     * Função: Envia arquivo (PDF, imagem, vídeo, áudio) para contato WhatsApp
     * Entrada: $numero (string) - Número WhatsApp
     *          $caminhoArquivo (string) - Caminho local do arquivo OU URL pública
     *          $tipoArquivo (string) - Tipo (document, image, video, audio)
     *          $caption (string) - Legenda/descrição do arquivo (opcional)
     * Processamento: Verifica arquivo, faz upload ou usa URL, formata payload
     * Saída: Array com resposta da API
     * 
     * @param string $numero Número WhatsApp com código de país
     * @param string $caminhoArquivo Caminho local ou URL do arquivo
     * @param string $tipoArquivo Tipo: document, image, video, audio
     * @param string|null $caption Legenda opcional (apenas para image/video)
     * @return array Resposta da API
     * 
     * @throws Exception Se arquivo não existir ou tipo inválido
     * 
     * Uso: $wa = new WhatsApp();
     *      $wa->sendDocument('5511999999999', '/caminho/para/orcamento.pdf', 'document');
     */
    public function sendDocument($numero, $caminhoArquivo, $tipoArquivo = 'document', $caption = null) {
        try {
            // Validação
            if (empty($numero) || empty($caminhoArquivo) || empty($tipoArquivo)) {
                throw new Exception("Número, arquivo e tipo são obrigatórios");
            }
            
            $numero = preg_replace('/[^0-9]/', '', $numero);
            
            // Tipos permitidos
            $tiposPermitidos = ['document', 'image', 'video', 'audio', 'sticker'];
            if (!in_array($tipoArquivo, $tiposPermitidos)) {
                throw new Exception("Tipo de arquivo inválido. Permitidos: " . implode(', ', $tiposPermitidos));
            }
            
            // Obtém URL do arquivo
            if (filter_var($caminhoArquivo, FILTER_VALIDATE_URL)) {
                $urlArquivo = $caminhoArquivo;
            } else {
                // Valida se arquivo existe
                if (!file_exists($caminhoArquivo)) {
                    throw new Exception("Arquivo não encontrado: $caminhoArquivo");
                }
                
                // Faz upload do arquivo (requer implementação de upload handler)
                $urlArquivo = $this->uploadFile($caminhoArquivo, $tipoArquivo);
            }
            
            // Monta payload
            $payload = [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $numero,
                'type' => $tipoArquivo,
                $tipoArquivo => [
                    'link' => $urlArquivo
                ]
            ];
            
            // Adiciona caption se fornecido
            if (!empty($caption) && in_array($tipoArquivo, ['image', 'video'])) {
                $payload[$tipoArquivo]['caption'] = $caption;
            }
            
            // Envia requisição
            $response = $this->makeRequest($payload);
            
            // Log
            $this->logInfo("Documento enviado para $numero. Tipo: $tipoArquivo");
            
            return $response;
            
        } catch (Exception $e) {
            $this->logError("Erro ao enviar documento: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Envia mensagem usando template pré-definido
     * 
     * Função: Envia mensagem formatada usando template aprovado pela WhatsApp
     * Entrada: $numero (string) - Número WhatsApp
     *          $nomeTemplate (string) - Nome do template registrado
     *          $parametros (array) - Valores para os placeholders do template
     *          $idioma (string) - Código do idioma (pt_BR, en_US, etc)
     * Processamento: Valida template, formata parâmetros, envia requisição
     * Saída: Array com resposta da API
     * 
     * Templates devem ser criados no painel WhatsApp Business:
     * - Exemplo: "orcamento_enviado" com parâmetros {{1}}, {{2}}, {{3}}
     * - Os parâmetros na função correspondem aos placeholders em ordem
     * 
     * @param string $numero Número WhatsApp com código de país
     * @param string $nomeTemplate Nome do template registrado
     * @param array $parametros Array com valores para placeholders
     * @param string $idioma Código do idioma (padrão: pt_BR)
     * @return array Resposta da API
     * 
     * @throws Exception Se template inválido ou parâmetros insuficientes
     * 
     * Uso: $wa = new WhatsApp();
     *      $wa->sendTemplate('5511999999999', 'orcamento_enviado', 
     *          ['Cliente Silva', 'ORÇ-001', 'R$ 1.500,00'], 'pt_BR');
     * 
     * Template exemplo (criado no painel WhatsApp):
     * Olá {{1}},
     * Seu orçamento {{2}} no valor de {{3}} está pronto.
     * Clique no link para visualizar.
     */
    public function sendTemplate($numero, $nomeTemplate, $parametros = [], $idioma = 'pt_BR') {
        try {
            // Validação
            if (empty($numero) || empty($nomeTemplate)) {
                throw new Exception("Número e nome do template são obrigatórios");
            }
            
            $numero = preg_replace('/[^0-9]/', '', $numero);
            
            // Valida idioma
            $idiomas = ['pt_BR', 'en_US', 'es_ES', 'it_IT', 'de_DE', 'fr_FR'];
            if (!in_array($idioma, $idiomas)) {
                $idioma = 'pt_BR';
            }
            
            // Monta array de componentes
            $components = [
                [
                    'type' => 'body',
                    'parameters' => []
                ]
            ];
            
            // Adiciona parâmetros se fornecidos
            if (!empty($parametros) && is_array($parametros)) {
                foreach ($parametros as $param) {
                    $components[0]['parameters'][] = [
                        'type' => 'text',
                        'text' => (string)$param
                    ];
                }
            }
            
            // Monta payload
            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $numero,
                'type' => 'template',
                'template' => [
                    'name' => $nomeTemplate,
                    'language' => [
                        'code' => $idioma
                    ],
                    'components' => $components
                ]
            ];
            
            // Envia requisição
            $response = $this->makeRequest($payload);
            
            // Log
            $this->logInfo("Template '$nomeTemplate' enviado para $numero");
            
            return $response;
            
        } catch (Exception $e) {
            $this->logError("Erro ao enviar template: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Faz requisição à API WhatsApp
     * 
     * Função: Executa requisição HTTP POST para API do WhatsApp
     * Entrada: $payload (array) - Dados a enviar em formato JSON
     * Processamento: Formata headers, faz requisição cURL, processa resposta
     * Saída: Array com resposta decodificada da API
     * 
     * @param array $payload Dados a enviar
     * @return array Resposta da API decodificada
     * 
     * @throws Exception Em caso de erro de conexão ou resposta inválida
     */
    private function makeRequest($payload) {
        try {
            // Validação de credenciais
            if (empty($this->accessToken) || empty($this->phoneNumberId)) {
                throw new Exception("Credenciais WhatsApp não configuradas");
            }
            
            // Prepara requisição cURL
            $url = $this->apiUrl . $this->phoneNumberId . '/messages';
            
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $this->accessToken,
                    'Content-Type: application/json'
                ],
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_TIMEOUT => 30,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2
            ]);
            
            // Executa requisição
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            // Verifica erros de conexão
            if (!$response && $curlError) {
                throw new Exception("Erro de conexão: $curlError");
            }
            
            // Decodifica resposta
            $responseData = json_decode($response, true);
            
            // Verifica resposta de erro da API
            if ($httpCode >= 400 || (isset($responseData['error']))) {
                $errorMsg = isset($responseData['error']['message']) 
                    ? $responseData['error']['message'] 
                    : "Erro HTTP $httpCode";
                throw new Exception("Erro da API: $errorMsg");
            }
            
            return $responseData;
            
        } catch (Exception $e) {
            $this->logError("Erro na requisição à API: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Faz upload de arquivo para servidor de mídia
     * 
     * Função: Realiza upload de arquivo local para servidor e retorna URL
     * Entrada: $caminhoArquivo (string) - Caminho local do arquivo
     *          $tipo (string) - Tipo de arquivo
     * Processamento: Valida arquivo, faz upload usando API, retorna URL pública
     * Saída: URL pública do arquivo
     * 
     * Nota: Requer integração com servidor de mídia (AWS S3, Azure, etc)
     * 
     * @param string $caminhoArquivo Caminho local do arquivo
     * @param string $tipo Tipo de arquivo
     * @return string URL pública do arquivo
     * 
     * @throws Exception Se arquivo inválido ou upload falhar
     */
    private function uploadFile($caminhoArquivo, $tipo) {
        try {
            // Validações
            if (!file_exists($caminhoArquivo)) {
                throw new Exception("Arquivo não encontrado: $caminhoArquivo");
            }
            
            $fileSize = filesize($caminhoArquivo);
            if ($fileSize === false || $fileSize == 0) {
                throw new Exception("Arquivo vazio ou inválido");
            }
            
            // Limites de tamanho por tipo (em bytes)
            $limites = [
                'document' => 100 * 1024 * 1024,    // 100MB
                'image' => 16 * 1024 * 1024,        // 16MB
                'video' => 16 * 1024 * 1024,        // 16MB
                'audio' => 16 * 1024 * 1024         // 16MB
            ];
            
            if (isset($limites[$tipo]) && $fileSize > $limites[$tipo]) {
                throw new Exception("Arquivo excede tamanho máximo permitido");
            }
            
            // TODO: Implementar upload para servidor de mídia (AWS S3, Azure, etc)
            // Exemplo de URL retornada:
            // return 'https://meustorage.blob.core.windows.net/arquivos/documento_12345.pdf';
            
            // Implementação placeholder - usar arquivo temporário local
            $filename = basename($caminhoArquivo);
            $uploadDir = __DIR__ . '/../uploads/whatsapp/';
            
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $destino = $uploadDir . time() . '_' . $filename;
            
            if (!copy($caminhoArquivo, $destino)) {
                throw new Exception("Falha ao fazer upload do arquivo");
            }
            
            // Retorna URL do arquivo (ajustar conforme sua configuração)
            $baseUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/uploads/whatsapp/';
            return $baseUrl . basename($destino);
            
        } catch (Exception $e) {
            $this->logError("Erro ao fazer upload: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Registra informação em arquivo de log
     * 
     * @param string $message Mensagem a registrar
     */
    private function logInfo($message) {
        $this->logToFile('INFO', $message);
    }
    
    /**
     * Registra erro em arquivo de log
     * 
     * @param string $message Mensagem de erro
     */
    private function logError($message) {
        $this->logToFile('ERROR', $message);
    }
    
    /**
     * Registra mensagem em arquivo de log
     * 
     * @param string $level Nível do log (INFO, ERROR, WARNING)
     * @param string $message Mensagem
     */
    private function logToFile($level, $message) {
        $logFile = $this->logPath . 'whatsapp_' . date('Y-m-d') . '.log';
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] [{$level}] {$message}\n";
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
}
?>
