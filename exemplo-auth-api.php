<?php
/**
 * ============================================================================
 * EXEMPLO DE IMPLEMENTAÇÃO DE AUTENTICAÇÃO
 * ============================================================================
 * 
 * Este arquivo é um exemplo de como implementar o endpoint /api/auth.php
 * para autenticar usuários no sistema.
 * 
 * Este é um EXEMPLO BÁSICO. Em produção, você deve:
 * - Usar banco de dados real (MySQL, PostgreSQL)
 * - Usar prepared statements (PDO)
 * - Implementar hash seguro de senhas (bcrypt, Argon2)
 * - Implementar JWT para tokens
 * - Implementar rate limiting
 * 
 * @author Sistema Novo
 * @version 1.0.0
 * ============================================================================
 */

// Definir header JSON
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// Apenas aceitar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'sucesso' => false,
        'erro' => true,
        'codigo' => 405,
        'mensagem' => 'Método não permitido. Use POST.',
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ============================================================================
// OBTER DADOS DA REQUISIÇÃO
// ============================================================================

$input = json_decode(file_get_contents('php://input'), true);

$email = $input['email'] ?? '';
$senha = $input['senha'] ?? '';
$lembrarme = $input['lembrarme'] ?? false;

// ============================================================================
// VALIDAÇÃO BÁSICA
// ============================================================================

if (empty($email) || empty($senha)) {
    http_response_code(400);
    echo json_encode([
        'sucesso' => false,
        'erro' => true,
        'codigo' => 400,
        'mensagem' => 'Email e senha são obrigatórios',
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        'sucesso' => false,
        'erro' => true,
        'codigo' => 400,
        'mensagem' => 'Email inválido',
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ============================================================================
// EXEMPLO: USUÁRIOS DE TESTE
// ============================================================================
// NÃO USE ISTO EM PRODUÇÃO! Use banco de dados real.

$usuariosTeste = [
    [
        'id' => 1,
        'email' => 'admin@example.com',
        'senha' => 'admin123', // NÃO use senhas em texto puro!
        'nome' => 'Administrador',
        'tipo' => 'admin'
    ],
    [
        'id' => 2,
        'email' => 'tecnico@example.com',
        'senha' => 'tecnico123',
        'nome' => 'Técnico',
        'tipo' => 'tecnico'
    ],
    [
        'id' => 3,
        'email' => 'cliente@example.com',
        'senha' => 'cliente123',
        'nome' => 'Cliente',
        'tipo' => 'cliente'
    ]
];

// ============================================================================
// AUTENTICAÇÃO
// ============================================================================

$usuarioAutenticado = null;

foreach ($usuariosTeste as $usuario) {
    // TODO: Em produção, use password_verify() com hashes bcrypt
    if ($usuario['email'] === $email && $usuario['senha'] === $senha) {
        $usuarioAutenticado = $usuario;
        break;
    }
}

if (!$usuarioAutenticado) {
    http_response_code(401);
    echo json_encode([
        'sucesso' => false,
        'erro' => true,
        'codigo' => 401,
        'mensagem' => 'Email ou senha inválidos',
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ============================================================================
// GERAR TOKEN (EXEMPLO SIMPLES)
// ============================================================================

// TODO: Em produção, use JWT (JSON Web Token)
$token = bin2hex(random_bytes(32));

// Armazenar token na sessão ou banco de dados
session_start();
$_SESSION['auth_token'] = $token;
$_SESSION['usuario_id'] = $usuarioAutenticado['id'];
$_SESSION['usuario_email'] = $usuarioAutenticado['email'];
$_SESSION['usuario_tipo'] = $usuarioAutenticado['tipo'];

// ============================================================================
// RESPOSTA DE SUCESSO
// ============================================================================

$urlRedirecao = '/';

switch ($usuarioAutenticado['tipo']) {
    case 'admin':
        $urlRedirecao = '/admin/dashboard';
        break;
    case 'tecnico':
        $urlRedirecao = '/tecnico/dashboard';
        break;
    case 'cliente':
    default:
        $urlRedirecao = '/cliente/dashboard';
        break;
}

http_response_code(200);
echo json_encode([
    'sucesso' => true,
    'erro' => false,
    'codigo' => 200,
    'mensagem' => 'Login realizado com sucesso',
    'dados' => [
        'token' => $token,
        'usuario_id' => $usuarioAutenticado['id'],
        'usuario_nome' => $usuarioAutenticado['nome'],
        'usuario_email' => $usuarioAutenticado['email'],
        'usuario_tipo' => $usuarioAutenticado['tipo'],
        'urlRedirecao' => $urlRedirecao,
        'lembrarme' => $lembrarme
    ],
    'timestamp' => date('Y-m-d H:i:s')
], JSON_UNESCAPED_UNICODE);

// ============================================================================
// FIM DO ARQUIVO
// ============================================================================

?>
