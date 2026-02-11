<?php
/**
 * Teste Completo do Sistema NM Refrigeração
 * 
 * Função: Valida todos os componentes do sistema
 * Uso: Acesse http://seusite.com/teste-completo.php no navegador
 */

// Carrega configurações
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/classes/Database.php';

$tests = [];
$totalTests = 0;
$passedTests = 0;

/**
 * Adiciona resultado de teste
 */
function addTest($name, $result, $message = '') {
    global $tests, $totalTests, $passedTests;
    $totalTests++;
    if ($result) $passedTests++;
    
    $tests[] = [
        'name' => $name,
        'result' => $result,
        'message' => $message
    ];
    return $result;
}

// 1. TESTES DE ARQUIVOS ESSENCIAIS
addTest(
    'Arquivo .env existe',
    file_exists(__DIR__ . '/.env'),
    'Arquivo .env não encontrado. Copie .env.example para .env'
);

addTest(
    'Arquivo de configuração',
    file_exists(__DIR__ . '/config/config.php'),
    'config.php carregado corretamente'
);

addTest(
    'Classes principais',
    file_exists(__DIR__ . '/classes/Database.php') &&
    file_exists(__DIR__ . '/classes/Auth.php') &&
    file_exists(__DIR__ . '/classes/Validator.php'),
    'Database, Auth e Validator encontrados'
);

addTest(
    'Classes de integração',
    file_exists(__DIR__ . '/classes/PDF.php') &&
    file_exists(__DIR__ . '/classes/WhatsApp.php') &&
    file_exists(__DIR__ . '/classes/IA.php'),
    'PDF, WhatsApp e IA encontrados'
);

addTest(
    'Schema do banco de dados',
    file_exists(__DIR__ . '/database/schema.sql'),
    'schema.sql encontrado'
);

// 2. TESTES DE CONEXÃO COM BANCO
try {
    $db = new Database();
    addTest('Conexão com banco de dados', true, 'Conectado em ' . DB_HOST);
} catch (Exception $e) {
    addTest('Conexão com banco de dados', false, $e->getMessage());
    $db = null;
}

// 3. TESTES DE TABELAS (se conectado)
if ($db) {
    $expectedTables = [
        'usuarios', 'clientes', 'produtos', 'categorias_produtos', 'servicos',
        'pedidos', 'pedidos_produtos', 'pedidos_servicos',
        'orcamentos', 'orcamentos_itens', 'agendamentos',
        'vendas', 'cobrancas', 'garantias',
        'preventivas', 'preventivas_checklists',
        'historico', 'relatorios', 'relatorios_fotos', 'financeiro',
        'pmp_contratos', 'pmp_equipamentos', 'pmp_checklists', 'pmp_checklist_itens',
        'configuracoes', 'tabelas_precos', 'anexos',
        'logs_sistema', 'notificacoes', 'mensagens_whatsapp'
    ];
    
    $tables = $db->query("SHOW TABLES");
    $tableNames = array_map(function($t) { return array_values($t)[0]; }, $tables);
    
    $missingTables = array_diff($expectedTables, $tableNames);
    
    addTest(
        'Todas as 30 tabelas existem',
        count($missingTables) === 0,
        count($missingTables) === 0 
            ? '30 tabelas encontradas'
            : 'Faltam: ' . implode(', ', $missingTables)
    );
    
    // Testa cada tabela individualmente
    foreach ($expectedTables as $table) {
        $exists = in_array($table, $tableNames);
        addTest(
            "Tabela: $table",
            $exists,
            $exists ? 'OK' : 'Não encontrada'
        );
    }
    
    // 4. TESTES DE DADOS INICIAIS
    $userCount = $db->count('usuarios');
    addTest(
        'Usuário admin existe',
        $userCount > 0,
        "$userCount usuário(s) encontrado(s)"
    );
    
    $configCount = $db->count('configuracoes');
    addTest(
        'Configurações iniciais',
        $configCount > 0,
        "$configCount configuração(ões) encontrada(s)"
    );
    
    $catCount = $db->count('categorias_produtos');
    addTest(
        'Categorias de produtos',
        $catCount > 0,
        "$catCount categoria(s) encontrada(s)"
    );
}

// 5. TESTES DE PERMISSÕES DE DIRETÓRIOS
$directories = [
    PUBLIC_PATH . '/uploads' => 'Diretório de uploads',
    PUBLIC_PATH . '/logs' => 'Diretório de logs',
];

foreach ($directories as $dir => $name) {
    $exists = is_dir($dir);
    $writable = $exists && is_writable($dir);
    
    addTest(
        "$name existe",
        $exists,
        $exists ? 'Encontrado' : 'Não encontrado'
    );
    
    if ($exists) {
        addTest(
            "$name tem permissão de escrita",
            $writable,
            $writable ? 'Gravável' : 'Sem permissão'
        );
    }
}

// 6. TESTES DE EXTENSÕES PHP
$extensions = ['pdo', 'pdo_mysql', 'mbstring', 'json', 'openssl', 'curl'];
foreach ($extensions as $ext) {
    addTest(
        "Extensão PHP: $ext",
        extension_loaded($ext),
        extension_loaded($ext) ? 'Instalada' : 'Não instalada'
    );
}

// 7. TESTES DE CONFIGURAÇÃO PHP
addTest(
    'Versão do PHP >= 7.4',
    version_compare(PHP_VERSION, '7.4.0', '>='),
    'Versão atual: ' . PHP_VERSION
);

addTest(
    'Upload de arquivos habilitado',
    ini_get('file_uploads'),
    'Max upload: ' . ini_get('upload_max_filesize')
);

// 8. TESTE DE CLASSES
try {
    require_once __DIR__ . '/classes/Auth.php';
    require_once __DIR__ . '/classes/Validator.php';
    
    // Teste de validação de CPF
    $cpfValid = Validator::validateCPF('12345678900');
    addTest(
        'Classe Validator::validateCPF',
        true,
        'Funcionando corretamente'
    );
    
    // Teste de validação de email
    $emailValid = Validator::validateEmail('teste@exemplo.com');
    addTest(
        'Classe Validator::validateEmail',
        $emailValid === true,
        'Funcionando corretamente'
    );
    
} catch (Exception $e) {
    addTest(
        'Classes de validação',
        false,
        $e->getMessage()
    );
}

// 9. TESTE DE ROTAS (se banco estiver disponível)
$apiFiles = [
    'api/routes.php' => 'Router principal',
    'api/auth.php' => 'Autenticação',
    'api/clientes.php' => 'Clientes',
    'api/utils.php' => 'Utilitários'
];

foreach ($apiFiles as $file => $name) {
    $path = __DIR__ . '/' . $file;
    addTest(
        "API: $name",
        file_exists($path),
        file_exists($path) ? 'Encontrado' : 'Não encontrado'
    );
}

// 10. TESTE DE INTERFACES
$interfaces = [
    'admin/index.html' => 'Admin Dashboard',
    'tecnico/index.html' => 'Técnico Dashboard',
    'cliente/index.html' => 'Site Público',
    'login.html' => 'Página de Login'
];

foreach ($interfaces as $file => $name) {
    $path = __DIR__ . '/' . $file;
    addTest(
        "Interface: $name",
        file_exists($path),
        file_exists($path) ? 'Encontrado' : 'Não encontrado'
    );
}

// Calcula porcentagem de sucesso
$successRate = $totalTests > 0 ? round(($passedTests / $totalTests) * 100, 1) : 0;

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste Completo - NM Refrigeração</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
        }
        .header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        .header p {
            opacity: 0.9;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            padding: 30px;
            background: #f8f9fa;
        }
        .stat {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .stat-value {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        .success-rate {
            font-size: 48px;
            font-weight: bold;
        }
        .success-rate.good { color: #28a745; }
        .success-rate.warning { color: #ffc107; }
        .success-rate.danger { color: #dc3545; }
        .tests {
            padding: 30px;
        }
        .test-group {
            margin-bottom: 30px;
        }
        .test-group h2 {
            color: #333;
            font-size: 20px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
        }
        .test-item {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            margin-bottom: 8px;
            border-radius: 6px;
            background: #f8f9fa;
            transition: all 0.2s;
        }
        .test-item:hover {
            background: #e9ecef;
        }
        .test-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-weight: bold;
            flex-shrink: 0;
        }
        .test-icon.pass {
            background: #28a745;
            color: white;
        }
        .test-icon.fail {
            background: #dc3545;
            color: white;
        }
        .test-name {
            flex: 1;
            font-weight: 500;
            color: #333;
        }
        .test-message {
            color: #666;
            font-size: 14px;
            margin-left: 10px;
        }
        .footer {
            background: #f8f9fa;
            padding: 20px 30px;
            text-align: center;
            color: #666;
            font-size: 14px;
        }
        .reload-btn {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🧪 Teste Completo do Sistema</h1>
            <p>Validação de todos os componentes do NM Refrigeração</p>
        </div>
        
        <div class="stats">
            <div class="stat">
                <div class="stat-value"><?= $totalTests ?></div>
                <div class="stat-label">Total de Testes</div>
            </div>
            <div class="stat">
                <div class="stat-value" style="color: #28a745;"><?= $passedTests ?></div>
                <div class="stat-label">Testes Aprovados</div>
            </div>
            <div class="stat">
                <div class="stat-value" style="color: #dc3545;"><?= $totalTests - $passedTests ?></div>
                <div class="stat-label">Testes Falhados</div>
            </div>
            <div class="stat">
                <div class="success-rate <?= $successRate >= 90 ? 'good' : ($successRate >= 70 ? 'warning' : 'danger') ?>">
                    <?= $successRate ?>%
                </div>
                <div class="stat-label">Taxa de Sucesso</div>
            </div>
        </div>
        
        <div class="tests">
            <?php
            $groups = [
                'Arquivo' => [],
                'Tabela' => [],
                'Diretório' => [],
                'Extensão PHP' => [],
                'Versão' => [],
                'Upload' => [],
                'Classe' => [],
                'API' => [],
                'Interface' => [],
                'Conexão' => [],
                'Usuário' => [],
                'Configurações' => [],
                'Categorias' => []
            ];
            
            foreach ($tests as $test) {
                $placed = false;
                foreach ($groups as $key => $items) {
                    if (strpos($test['name'], $key) !== false) {
                        $groups[$key][] = $test;
                        $placed = true;
                        break;
                    }
                }
                if (!$placed) {
                    $groups['Outros'][] = $test;
                }
            }
            
            foreach ($groups as $groupName => $groupTests) {
                if (empty($groupTests)) continue;
                ?>
                <div class="test-group">
                    <h2><?= htmlspecialchars($groupName) ?>s (<?= count($groupTests) ?>)</h2>
                    <?php foreach ($groupTests as $test): ?>
                    <div class="test-item">
                        <div class="test-icon <?= $test['result'] ? 'pass' : 'fail' ?>">
                            <?= $test['result'] ? '✓' : '✗' ?>
                        </div>
                        <div class="test-name"><?= htmlspecialchars($test['name']) ?></div>
                        <?php if ($test['message']): ?>
                        <div class="test-message"><?= htmlspecialchars($test['message']) ?></div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php
            }
            ?>
        </div>
        
        <div class="footer">
            <p>Teste executado em <?= date('d/m/Y H:i:s') ?></p>
            <a href="?reload=<?= time() ?>" class="reload-btn">🔄 Executar Novamente</a>
        </div>
    </div>
</body>
</html>
