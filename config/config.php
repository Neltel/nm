<?php
/**
 * Arquivo de Configuração Principal
 * 
 * Função: Carrega as configurações do arquivo .env e define constantes globais
 * Uso: require_once __DIR__ . '/config/config.php';
 */

// Carrega variáveis do arquivo .env
function loadEnv($path) {
    if (!file_exists($path)) {
        die("Arquivo .env não encontrado. Copie .env.example para .env e configure.");
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Ignora comentários
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parse linha formato: KEY=VALUE
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        // Define como constante se não existir
        if (!defined($name)) {
            define($name, $value);
        }
    }
}

// Carrega .env do diretório raiz
loadEnv(__DIR__ . '/../.env');

// Define timezone
date_default_timezone_set(defined('TIMEZONE') ? TIMEZONE : 'America/Sao_Paulo');

// Configurações de erro baseadas no ambiente
if (defined('APP_DEBUG') && APP_DEBUG === 'true') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Configurações de sessão
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);

// Headers de segurança
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');

// Inicia sessão se ainda não foi iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
