<?php
/**
 * Script de Debug para API Login
 * 
 * Este script testa todo o fluxo de login da API e mostra cada etapa
 * para facilitar o diagnóstico de problemas.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== DEBUG API LOGIN ===\n\n";

// 1. Verificar estrutura de diretórios
echo "1. VERIFICANDO ESTRUTURA DE DIRETÓRIOS\n";
echo "-------------------------------------------\n";
$baseDir = __DIR__;
echo "Base Directory: $baseDir\n";

$dirs = [
    'api' => $baseDir . '/api',
    'classes' => $baseDir . '/classes',
    'config' => $baseDir . '/config',
    'logs' => $baseDir . '/logs',
];

foreach ($dirs as $name => $path) {
    echo "$name: " . ($path) . " - " . (is_dir($path) ? "✓ EXISTE" : "✗ NÃO EXISTE") . "\n";
}

// 2. Verificar arquivos chave
echo "\n2. VERIFICANDO ARQUIVOS CHAVE\n";
echo "-------------------------------------------\n";
$files = [
    'index.php' => $baseDir . '/index.php',
    'routes.php' => $baseDir . '/api/routes.php',
    'auth.php' => $baseDir . '/api/auth.php',
    'Auth.php' => $baseDir . '/classes/Auth.php',
    'Database.php' => $baseDir . '/classes/Database.php',
    'Validator.php' => $baseDir . '/classes/Validator.php',
    'config.php' => $baseDir . '/config/config.php',
    'database.php' => $baseDir . '/config/database.php',
    '.env' => $baseDir . '/.env',
];

foreach ($files as $name => $path) {
    echo "$name: " . (file_exists($path) ? "✓ EXISTE" : "✗ NÃO EXISTE") . "\n";
}

// 3. Carregar configurações
echo "\n3. CARREGANDO CONFIGURAÇÕES\n";
echo "-------------------------------------------\n";
try {
    require_once __DIR__ . '/config/config.php';
    echo "✓ config.php carregado\n";
    
    echo "DB_HOST: " . (defined('DB_HOST') ? DB_HOST : 'NÃO DEFINIDO') . "\n";
    echo "DB_NAME: " . (defined('DB_NAME') ? DB_NAME : 'NÃO DEFINIDO') . "\n";
    echo "DB_USER: " . (defined('DB_USER') ? DB_USER : 'NÃO DEFINIDO') . "\n";
    echo "JWT_SECRET: " . (defined('JWT_SECRET') ? 'DEFINIDO' : 'NÃO DEFINIDO') . "\n";
    echo "JWT_EXPIRATION: " . (defined('JWT_EXPIRATION') ? JWT_EXPIRATION : 'NÃO DEFINIDO') . "\n";
} catch (Exception $e) {
    echo "✗ Erro ao carregar config: " . $e->getMessage() . "\n";
}

// 4. Carregar constantes
echo "\n4. CARREGANDO CONSTANTES\n";
echo "-------------------------------------------\n";
try {
    require_once __DIR__ . '/config/constants.php';
    echo "✓ constants.php carregado\n";
    echo "LOGS_PATH: " . (defined('LOGS_PATH') ? LOGS_PATH : 'NÃO DEFINIDO') . "\n";
} catch (Exception $e) {
    echo "✗ Erro ao carregar constants: " . $e->getMessage() . "\n";
}

// 5. Testar conexão com banco de dados
echo "\n5. TESTANDO CONEXÃO COM BANCO DE DADOS\n";
echo "-------------------------------------------\n";
try {
    require_once __DIR__ . '/classes/Database.php';
    $db = new Database();
    echo "✓ Conexão com banco de dados estabelecida\n";
    
    // Testar query simples
    $result = $db->query("SELECT 1 as test");
    echo "✓ Query de teste executada com sucesso\n";
    
    // Verificar se a tabela usuarios existe
    $tables = $db->query("SHOW TABLES LIKE 'usuarios'");
    if (count($tables) > 0) {
        echo "✓ Tabela 'usuarios' encontrada\n";
        
        // Contar usuários
        $count = $db->count('usuarios');
        echo "  Total de usuários: $count\n";
        
        // Verificar se existe admin@imperio.com.br
        $admin = $db->queryOne("SELECT id, email, nome, tipo FROM usuarios WHERE email = ?", ['admin@imperio.com.br']);
        if ($admin) {
            echo "✓ Usuário admin@imperio.com.br encontrado\n";
            echo "  ID: {$admin['id']}\n";
            echo "  Nome: {$admin['nome']}\n";
            echo "  Tipo: {$admin['tipo']}\n";
        } else {
            echo "✗ Usuário admin@imperio.com.br NÃO encontrado\n";
        }
    } else {
        echo "✗ Tabela 'usuarios' NÃO encontrada\n";
    }
} catch (Exception $e) {
    echo "✗ Erro ao conectar com banco: " . $e->getMessage() . "\n";
}

// 6. Testar classe Auth
echo "\n6. TESTANDO CLASSE AUTH\n";
echo "-------------------------------------------\n";
try {
    require_once __DIR__ . '/classes/Auth.php';
    require_once __DIR__ . '/classes/Validator.php';
    
    if (isset($db)) {
        $auth = new Auth($db);
        echo "✓ Classe Auth inicializada\n";
        
        // Testar geração de token
        $testUser = [
            'id' => 1,
            'email' => 'test@test.com',
            'tipo' => 'admin'
        ];
        $token = $auth->generateToken($testUser);
        echo "✓ Token gerado com sucesso\n";
        echo "  Token (primeiros 50 caracteres): " . substr($token, 0, 50) . "...\n";
        
        // Testar validação de token
        $payload = $auth->validateToken($token);
        if ($payload) {
            echo "✓ Token validado com sucesso\n";
            echo "  User ID: {$payload['user_id']}\n";
            echo "  Email: {$payload['email']}\n";
        } else {
            echo "✗ Falha ao validar token\n";
        }
    } else {
        echo "✗ Database não está disponível para testar Auth\n";
    }
} catch (Exception $e) {
    echo "✗ Erro ao testar Auth: " . $e->getMessage() . "\n";
}

// 7. Testar login (se usuário existir)
echo "\n7. TESTANDO FUNÇÃO DE LOGIN\n";
echo "-------------------------------------------\n";
if (isset($auth) && isset($admin)) {
    echo "NOTA: O login só funcionará se a senha no banco for 'admin123'\n";
    echo "Se a senha estiver diferente, o teste falhará (comportamento esperado)\n\n";
    
    // Tentar login
    $resultado = $auth->login('admin@imperio.com.br', 'admin123');
    if ($resultado) {
        echo "✓ Login realizado com sucesso!\n";
        echo "  Token: " . substr($resultado['token'], 0, 50) . "...\n";
        echo "  Usuário ID: {$resultado['usuario']['id']}\n";
        echo "  Nome: {$resultado['usuario']['nome']}\n";
        echo "  Email: {$resultado['usuario']['email']}\n";
        echo "  Tipo: {$resultado['usuario']['tipo']}\n";
    } else {
        echo "✗ Falha no login (email ou senha inválidos)\n";
        echo "  Possível causa: senha no banco não é 'admin123'\n";
    }
} else {
    echo "✗ Não foi possível testar login (Auth ou Admin não disponível)\n";
}

// 8. Simular requisição POST
echo "\n8. SIMULANDO REQUISIÇÃO POST PARA /api/auth/login\n";
echo "-------------------------------------------\n";
echo "Para testar a requisição completa, use:\n";
echo "curl -X POST https://novo.nmrefrigeracao.business/api/auth/login \\\n";
echo "  -H 'Content-Type: application/json' \\\n";
echo "  -d '{\"email\":\"admin@imperio.com.br\",\"password\":\"admin123\"}'\n";

echo "\n=== FIM DO DEBUG ===\n";
?>
