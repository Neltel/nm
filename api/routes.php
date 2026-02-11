<?php
/**
 * ARQUIVO: routes.php
 * 
 * Função: Roteador principal da API que processa todas as requisições
 * Entrada: Requisição HTTP (URL, método, dados JSON)
 * Processamento: Analisa a URL, roteia para endpoint apropriado, trata CORS
 * Saída: Resposta JSON com dados, erro ou status
 * Uso: Incluído como ponto de entrada de todas as requisições da API
 */

// Headers de CORS
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Responde pré-flight do CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Inclui arquivo de configuração
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/constants.php';

// Inclui classes principais
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Validator.php';

// Função auxiliar para retornar respostas JSON
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

// Função auxiliar para retornar erro
function sendError($message, $statusCode = 400, $data = []) {
    $response = [
        'success' => false,
        'message' => $message,
        'error' => true
    ];
    
    if (!empty($data)) {
        $response['data'] = $data;
    }
    
    sendResponse($response, $statusCode);
}

// Função auxiliar para retornar sucesso
function sendSuccess($data, $message = 'Operação realizada com sucesso', $statusCode = 200) {
    $response = [
        'success' => true,
        'message' => $message,
        'data' => $data
    ];
    
    sendResponse($response, $statusCode);
}

try {
    // Inicializa banco de dados
    $db = new Database();
    
    // Obtém informações da requisição
    $method = $_SERVER['REQUEST_METHOD'];
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $baseUri = str_replace('/public_html/api/', '', $uri);
    
    // Remove barra inicial e final
    $baseUri = trim($baseUri, '/');
    
    // Separa partes da URL
    $parts = explode('/', $baseUri);
    $endpoint = isset($parts[0]) ? $parts[0] : '';
    
    // Recebe dados JSON se enviados
    $input = [];
    if (in_array($method, ['POST', 'PUT'])) {
        $json = file_get_contents('php://input');
        if (!empty($json)) {
            $input = json_decode($json, true) ?? [];
        }
    }
    
    // Roteia para arquivo de endpoint apropriado
    switch ($endpoint) {
        case 'auth':
            require_once __DIR__ . '/auth.php';
            break;
            
        case 'clientes':
            require_once __DIR__ . '/clientes.php';
            break;
            
        case 'produtos':
            require_once __DIR__ . '/produtos.php';
            break;
            
        case 'servicos':
            require_once __DIR__ . '/servicos.php';
            break;
            
        case 'pedidos':
            require_once __DIR__ . '/pedidos.php';
            break;
            
        case 'orcamentos':
            require_once __DIR__ . '/orcamentos.php';
            break;
            
        case 'agendamentos':
            require_once __DIR__ . '/agendamentos.php';
            break;
            
        case 'vendas':
            require_once __DIR__ . '/vendas.php';
            break;
            
        case 'cobrancas':
            require_once __DIR__ . '/cobrancas.php';
            break;
            
        case 'whatsapp':
            require_once __DIR__ . '/whatsapp.php';
            break;
            
        case 'ia':
            require_once __DIR__ . '/ia.php';
            break;
            
        case 'utils':
            require_once __DIR__ . '/utils.php';
            break;
            
        case 'garantias':
            require_once __DIR__ . '/garantias.php';
            break;
            
        case 'preventivas':
            require_once __DIR__ . '/preventivas.php';
            break;
            
        case 'relatorios':
            require_once __DIR__ . '/relatorios.php';
            break;
            
        case 'financeiro':
            require_once __DIR__ . '/financeiro.php';
            break;
            
        case 'pmp':
            require_once __DIR__ . '/pmp.php';
            break;
            
        default:
            sendError('Endpoint não encontrado', 404);
            break;
    }
    
} catch (Exception $e) {
    // Log do erro
    $logFile = LOGS_PATH . '/api_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] ERRO: " . $e->getMessage() . "\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    
    // Resposta de erro
    sendError(
        'Erro interno do servidor',
        500,
        APP_DEBUG === 'true' ? ['exception' => $e->getMessage()] : []
    );
}
?>
