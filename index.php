<?php
/**
 * ============================================================================
 * ROTEADOR PRINCIPAL DA APLICAÇÃO
 * ============================================================================
 * 
 * Este arquivo é o ponto de entrada único (single point of entry) para toda
 * a aplicação. Ele detecta a rota solicitada e encaminha a requisição para
 * o controlador apropriado.
 * 
 * Rotas suportadas:
 * - /admin/* -> Painel Administrativo
 * - /tecnico/* -> Painel Técnico
 * - /cliente/* ou / -> Site Público / Portal do Cliente
 * - /api/* -> API RESTful
 * 
 * @author Sistema Novo
 * @version 1.0.0
 * @since 2024
 * ============================================================================
 */

// ============================================================================
// CONFIGURAÇÕES DE SEGURANÇA
// ============================================================================

// Define o nível de error reporting apropriado
error_reporting(E_ALL);
ini_set('display_errors', 0); // Nunca exibir erros para o usuário
ini_set('log_errors', 1);
ini_set('error_log', dirname(__DIR__) . '/logs/php-errors.log');

// Define o charset padrão para UTF-8
header('Content-Type: application/json; charset=utf-8', true);
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');

// Impede acesso direto a arquivos sensíveis
session_start();
session_regenerate_id(true);

// ============================================================================
// DEFINIÇÕES DE CONSTANTES
// ============================================================================

// Define o diretório raiz da aplicação
define('ROOT_DIR', dirname(__DIR__));
define('PUBLIC_DIR', __DIR__);
define('APP_DIR', ROOT_DIR . '/app');

// Define os diretórios de cada módulo
define('ADMIN_DIR', APP_DIR . '/admin');
define('TECNICO_DIR', APP_DIR . '/tecnico');
define('CLIENTE_DIR', APP_DIR . '/cliente');
define('API_DIR', APP_DIR . '/api');
define('CONFIG_DIR', ROOT_DIR . '/config');
define('LOG_DIR', ROOT_DIR . '/logs');

// Ambiente da aplicação
define('ENV', getenv('APP_ENV') ?: 'production');
define('DEBUG', ENV === 'development');

// ============================================================================
// FUNÇÕES AUXILIARES
// ============================================================================

/**
 * Normaliza o caminho da requisição removendo query strings e barra final
 * 
 * @param string $path Caminho da URL
 * @return string Caminho normalizado
 */
function normalizarCaminho($path) {
    // Remove query string
    $path = explode('?', $path)[0];
    
    // Remove barra final (exceto para raiz)
    if ($path !== '/' && substr($path, -1) === '/') {
        $path = rtrim($path, '/');
    }
    
    // Remove múltiplas barras consecutivas
    $path = preg_replace('#/+#', '/', $path);
    
    return $path;
}

/**
 * Obtém o segmento da rota
 * 
 * @param string $uri URI completa
 * @param int $segmento Número do segmento (0 = primeiro, 1 = segundo, etc)
 * @return string|null Segmento da rota ou null
 */
function obterSegmento($uri, $segmento) {
    $partes = array_filter(explode('/', trim($uri, '/')));
    return $partes[$segmento] ?? null;
}

/**
 * Retorna uma resposta de erro em formato JSON
 * 
 * @param int $codigo Código HTTP
 * @param string $mensagem Mensagem de erro
 * @param array $dados Dados adicionais
 */
function erroJson($codigo, $mensagem, $dados = []) {
    http_response_code($codigo);
    echo json_encode([
        'sucesso' => false,
        'erro' => true,
        'codigo' => $codigo,
        'mensagem' => $mensagem,
        'dados' => $dados,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Retorna uma resposta de sucesso em formato JSON
 * 
 * @param array $dados Dados da resposta
 * @param int $codigo Código HTTP
 */
function sucessoJson($dados = [], $codigo = 200) {
    http_response_code($codigo);
    echo json_encode([
        'sucesso' => true,
        'erro' => false,
        'codigo' => $codigo,
        'dados' => $dados,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Registra atividades no log da aplicação
 * 
 * @param string $tipo Tipo de log (info, erro, aviso)
 * @param string $mensagem Mensagem a registrar
 * @param array $contexto Dados contextuais
 */
function registrarLog($tipo, $mensagem, $contexto = []) {
    if (!is_dir(LOG_DIR)) {
        mkdir(LOG_DIR, 0755, true);
    }
    
    $arquivo = LOG_DIR . '/app-' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $contextoStr = !empty($contexto) ? ' | ' . json_encode($contexto) : '';
    $entrada = "[$timestamp] [$tipo] $mensagem$contextoStr\n";
    
    error_log($entrada, 3, $arquivo);
}

/**
 * Carrega um arquivo de rota se existir
 * 
 * @param string $caminho Caminho do arquivo
 * @param array $parametros Parâmetros para passar ao arquivo
 * @return bool True se o arquivo foi carregado
 */
function carregarRota($caminho, $parametros = []) {
    if (!file_exists($caminho)) {
        return false;
    }
    
    extract($parametros);
    ob_start();
    include $caminho;
    ob_end_flush();
    return true;
}

// ============================================================================
// PROCESSAMENTO PRINCIPAL
// ============================================================================

// Obtém a URI solicitada
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Normaliza o caminho
$uri = normalizarCaminho($uri);

// Registra a requisição
registrarLog('info', "Requisição: $metodo $uri", [
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'desconhecido',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'desconhecido'
]);

// Obtém o primeiro segmento da rota (admin, tecnico, cliente, api)
$primeiroSegmento = obterSegmento($uri, 0);
$segundoSegmento = obterSegmento($uri, 1);

// ============================================================================
// ROTEAMENTO
// ============================================================================

try {
    // Rota: /api/*
    if ($primeiroSegmento === 'api') {
        header('Content-Type: application/json; charset=utf-8');
        
        // Define o endpoint da API
        $endpoint = $segundoSegmento ?? 'index';
        $arquivoApi = API_DIR . '/' . $endpoint . '.php';
        
        if (!file_exists($arquivoApi)) {
            erroJson(404, 'Endpoint de API não encontrado', [
                'endpoint_solicitado' => $endpoint
            ]);
        }
        
        // Carrega o endpoint da API
        if (!carregarRota($arquivoApi, [
            'metodo' => $metodo,
            'uri' => $uri,
            'parametros' => $_GET
        ])) {
            erroJson(500, 'Erro ao carregar endpoint da API');
        }
        exit;
    }
    
    // Rota: /admin/*
    elseif ($primeiroSegmento === 'admin') {
        // Verifica autenticação (será implementado posteriormente)
        // TODO: Implementar verificação de autenticação e permissões
        
        header('Content-Type: text/html; charset=utf-8');
        
        // Define a página do admin
        $pagina = $segundoSegmento ?? 'dashboard';
        $arquivoAdmin = ADMIN_DIR . '/' . $pagina . '.php';
        
        // Se o arquivo não existir, tenta carregar index
        if (!file_exists($arquivoAdmin)) {
            $arquivoAdmin = ADMIN_DIR . '/index.php';
        }
        
        if (!file_exists($arquivoAdmin)) {
            erroJson(404, 'Página administrativa não encontrada');
        }
        
        // Carrega a página do admin
        carregarRota($arquivoAdmin, [
            'uri' => $uri,
            'pagina' => $pagina
        ]);
        exit;
    }
    
    // Rota: /tecnico/*
    elseif ($primeiroSegmento === 'tecnico') {
        // Verifica autenticação e permissão de técnico
        // TODO: Implementar verificação de autenticação e permissões
        
        header('Content-Type: text/html; charset=utf-8');
        
        // Define a página do técnico
        $pagina = $segundoSegmento ?? 'dashboard';
        $arquivoTecnico = TECNICO_DIR . '/' . $pagina . '.php';
        
        // Se o arquivo não existir, tenta carregar index
        if (!file_exists($arquivoTecnico)) {
            $arquivoTecnico = TECNICO_DIR . '/index.php';
        }
        
        if (!file_exists($arquivoTecnico)) {
            erroJson(404, 'Página técnica não encontrada');
        }
        
        // Carrega a página do técnico
        carregarRota($arquivoTecnico, [
            'uri' => $uri,
            'pagina' => $pagina
        ]);
        exit;
    }
    
    // Rota: /cliente/* ou /
    else {
        header('Content-Type: text/html; charset=utf-8');
        
        // Define a página do cliente/público
        $pagina = $primeiroSegmento ?? 'index';
        $arquivoCliente = CLIENTE_DIR . '/' . $pagina . '.php';
        
        // Se o arquivo não existir, tenta carregar index
        if (!file_exists($arquivoCliente)) {
            $arquivoCliente = CLIENTE_DIR . '/index.php';
        }
        
        // Se ainda assim não existir, verifica se é arquivo estático
        if (!file_exists($arquivoCliente)) {
            // Tenta servir arquivo estático (HTML, CSS, JS, etc)
            $arquivoEstatico = PUBLIC_DIR . $uri;
            
            if (file_exists($arquivoEstatico) && is_file($arquivoEstatico)) {
                // Define o tipo de conteúdo apropriado
                $extensao = pathinfo($arquivoEstatico, PATHINFO_EXTENSION);
                $tiposConteudo = [
                    'html' => 'text/html',
                    'css' => 'text/css',
                    'js' => 'application/javascript',
                    'json' => 'application/json',
                    'xml' => 'application/xml',
                    'pdf' => 'application/pdf',
                    'png' => 'image/png',
                    'jpg' => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'gif' => 'image/gif',
                    'svg' => 'image/svg+xml',
                    'ico' => 'image/x-icon',
                    'woff' => 'font/woff',
                    'woff2' => 'font/woff2'
                ];
                
                header('Content-Type: ' . ($tiposConteudo[$extensao] ?? 'application/octet-stream'));
                header('Cache-Control: public, max-age=3600');
                readfile($arquivoEstatico);
                exit;
            }
            
            // Arquivo não encontrado
            erroJson(404, 'Página não encontrada', [
                'uri_solicitada' => $uri
            ]);
        }
        
        // Carrega a página do cliente
        carregarRota($arquivoCliente, [
            'uri' => $uri,
            'pagina' => $pagina
        ]);
        exit;
    }
    
} catch (Exception $e) {
    registrarLog('erro', 'Exceção não tratada: ' . $e->getMessage(), [
        'arquivo' => $e->getFile(),
        'linha' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    
    erroJson(500, 'Erro interno do servidor', [
        'mensagem' => DEBUG ? $e->getMessage() : 'Entre em contato com o suporte'
    ]);
}

// ============================================================================
// FALLBACK - Página padrão
// ============================================================================

erroJson(404, 'Nenhuma rota correspondeu à solicitação', [
    'uri' => $uri,
    'metodo' => $metodo
]);

?>
