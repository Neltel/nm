<?php
/**
 * Script de Teste de Login
 * 
 * Simula uma requisição POST para /api/auth/login
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== TESTE DE LOGIN API ===\n\n";

// Simular variáveis de servidor para requisição POST
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REQUEST_URI'] = '/api/auth/login';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'Test Script';

// Simular corpo JSON da requisição
$testData = [
    'email' => 'admin@imperio.com.br',
    'password' => 'admin123'
];

// Criar um stream temporário com os dados JSON
$json = json_encode($testData);
$stream = fopen('php://memory', 'r+');
fwrite($stream, $json);
rewind($stream);

// Não podemos realmente sobrescrever php://input, então vamos testar de forma diferente
// Vamos testar cada componente separadamente

echo "1. Carregando configurações...\n";
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/constants.php';
echo "✓ Configurações carregadas\n\n";

echo "2. Carregando classes...\n";
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/Auth.php';
require_once __DIR__ . '/classes/Validator.php';
echo "✓ Classes carregadas\n\n";

echo "3. Inicializando banco de dados...\n";
try {
    $db = new Database();
    echo "✓ Banco conectado\n\n";
} catch (Exception $e) {
    echo "✗ Erro ao conectar: " . $e->getMessage() . "\n";
    exit(1);
}

echo "4. Inicializando Auth...\n";
$auth = new Auth($db);
echo "✓ Auth inicializado\n\n";

echo "5. Validando dados de entrada...\n";
$input = $testData;
echo "Email: {$input['email']}\n";
echo "Password: " . str_repeat('*', strlen($input['password'])) . "\n\n";

// Validar email
if (!Validator::validateEmail($input['email'])) {
    echo "✗ Email inválido\n";
    exit(1);
}
echo "✓ Email válido\n\n";

// Aceitar tanto 'senha' quanto 'password'
$senha = $input['senha'] ?? $input['password'] ?? '';
if (empty($senha)) {
    echo "✗ Senha não fornecida\n";
    exit(1);
}
echo "✓ Senha fornecida\n\n";

echo "6. Tentando fazer login...\n";
try {
    $resultado = $auth->login($input['email'], $senha);
    
    if (!$resultado) {
        echo "✗ Login falhou: Email ou senha inválidos\n";
        exit(1);
    }
    
    echo "✓ LOGIN REALIZADO COM SUCESSO!\n\n";
    
    echo "=== RESPOSTA DA API ===\n";
    $response = [
        'success' => true,
        'message' => 'Login realizado com sucesso',
        'data' => [
            'token' => $resultado['token'],
            'user' => $resultado['usuario']
        ]
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    echo "\n\n";
    
    echo "=== DETALHES ===\n";
    echo "Token (primeiros 50 caracteres): " . substr($resultado['token'], 0, 50) . "...\n";
    echo "User ID: {$resultado['usuario']['id']}\n";
    echo "Nome: {$resultado['usuario']['nome']}\n";
    echo "Email: {$resultado['usuario']['email']}\n";
    echo "Tipo: {$resultado['usuario']['tipo']}\n";
    
} catch (Exception $e) {
    echo "✗ Erro durante login: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n=== TESTE CONCLUÍDO COM SUCESSO ===\n";
?>
