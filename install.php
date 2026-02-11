<?php
/**
 * Instalador do Sistema NM Refrigeração
 * 
 * Função: Instala o banco de dados, cria tabelas, e configura o sistema
 * Uso: Acesse http://seusite.com/install.php no navegador
 * IMPORTANTE: Delete este arquivo após a instalação!
 */

// Previne execução se já instalado
if (file_exists(__DIR__ . '/.installed')) {
    die('Sistema já instalado! Delete o arquivo .installed para reinstalar.');
}

// Carrega configurações
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/constants.php';

$errors = [];
$success = [];
$warnings = [];

// Processa instalação se formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 1. Testa conexão com banco
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET,
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $success[] = "✓ Conexão com MySQL estabelecida";
        
        // 2. Cria banco de dados se não existir
        $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $success[] = "✓ Banco de dados '" . DB_NAME . "' criado/verificado";
        
        // 3. Seleciona o banco
        $pdo->exec("USE " . DB_NAME);
        
        // 4. Executa schema.sql
        $schema = file_get_contents(__DIR__ . '/database/schema.sql');
        if ($schema === false) {
            throw new Exception("Arquivo schema.sql não encontrado!");
        }
        
        // Divide em statements individuais
        $statements = array_filter(
            array_map('trim', explode(';', $schema)),
            function($stmt) { return !empty($stmt) && !preg_match('/^--/', $stmt); }
        );
        
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $pdo->exec($statement);
            }
        }
        $success[] = "✓ 30 tabelas criadas com sucesso";
        
        // 5. Cria usuário admin padrão
        $senhaAdmin = password_hash('admin123456', PASSWORD_DEFAULT);
        $pdo->exec("
            INSERT INTO usuarios (nome, email, senha, tipo, telefone, cpf, ativo, criado_em)
            VALUES (
                'Administrador',
                'admin@nmrefrigeracao.business',
                '$senhaAdmin',
                'admin',
                '(11) 99999-9999',
                '12345678900',
                1,
                NOW()
            )
            ON DUPLICATE KEY UPDATE email = email
        ");
        $success[] = "✓ Usuário administrador criado";
        $success[] = "  → Email: admin@nmrefrigeracao.business";
        $success[] = "  → Senha: admin123456";
        $warnings[] = "⚠ ALTERE A SENHA DO ADMIN IMEDIATAMENTE!";
        
        // 6. Insere configurações padrão
        $configs = [
            ['chave' => 'empresa_nome', 'valor' => 'NM Refrigeração', 'grupo' => 'empresa', 'descricao' => 'Nome da empresa'],
            ['chave' => 'empresa_cnpj', 'valor' => '12.345.678/0001-90', 'grupo' => 'empresa', 'descricao' => 'CNPJ da empresa'],
            ['chave' => 'empresa_telefone', 'valor' => '(11) 99999-9999', 'grupo' => 'empresa', 'descricao' => 'Telefone'],
            ['chave' => 'empresa_email', 'valor' => 'contato@nmrefrigeracao.business', 'grupo' => 'empresa', 'descricao' => 'Email'],
            ['chave' => 'empresa_endereco', 'valor' => 'Rua Exemplo, 123', 'grupo' => 'empresa', 'descricao' => 'Endereço'],
            ['chave' => 'empresa_cidade', 'valor' => 'São Paulo', 'grupo' => 'empresa', 'descricao' => 'Cidade'],
            ['chave' => 'empresa_estado', 'valor' => 'SP', 'grupo' => 'empresa', 'descricao' => 'Estado'],
            ['chave' => 'empresa_cep', 'valor' => '01234-567', 'grupo' => 'empresa', 'descricao' => 'CEP'],
            ['chave' => 'sistema_itens_por_pagina', 'valor' => '20', 'grupo' => 'sistema', 'descricao' => 'Itens por página'],
            ['chave' => 'whatsapp_ativo', 'valor' => '0', 'grupo' => 'integracoes', 'descricao' => 'WhatsApp ativo'],
            ['chave' => 'ia_ativo', 'valor' => '0', 'grupo' => 'integracoes', 'descricao' => 'IA ativa'],
        ];
        
        $stmt = $pdo->prepare("
            INSERT INTO configuracoes (chave, valor, grupo, descricao, atualizado_em)
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        foreach ($configs as $config) {
            $stmt->execute([$config['chave'], $config['valor'], $config['grupo'], $config['descricao']]);
        }
        $success[] = "✓ Configurações padrão inseridas";
        
        // 7. Cria categorias de produtos padrão
        $categorias = [
            ['nome' => 'Ar Condicionado', 'descricao' => 'Equipamentos de ar condicionado'],
            ['nome' => 'Peças e Componentes', 'descricao' => 'Peças de reposição'],
            ['nome' => 'Ferramentas', 'descricao' => 'Ferramentas e equipamentos'],
            ['nome' => 'Gases Refrigerantes', 'descricao' => 'Gases e fluidos'],
            ['nome' => 'Acessórios', 'descricao' => 'Acessórios diversos']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO categorias_produtos (nome, descricao) VALUES (?, ?)");
        foreach ($categorias as $cat) {
            $stmt->execute([$cat['nome'], $cat['descricao']]);
        }
        $success[] = "✓ Categorias de produtos criadas";
        
        // 8. Cria diretórios necessários
        $dirs = [
            PUBLIC_PATH . '/uploads',
            PUBLIC_PATH . '/logs',
            PUBLIC_PATH . '/css',
            PUBLIC_PATH . '/js',
            PUBLIC_PATH . '/images'
        ];
        
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            if (!file_exists($dir . '/.gitkeep')) {
                touch($dir . '/.gitkeep');
            }
        }
        $success[] = "✓ Diretórios criados com permissões corretas";
        
        // 9. Cria arquivo .env se não existir
        if (!file_exists(__DIR__ . '/.env')) {
            copy(__DIR__ . '/.env.example', __DIR__ . '/.env');
            $warnings[] = "⚠ Configure o arquivo .env com suas chaves de API";
        }
        
        // 10. Marca como instalado
        file_put_contents(__DIR__ . '/.installed', date('Y-m-d H:i:s'));
        $success[] = "✓ Instalação concluída com sucesso!";
        
    } catch (PDOException $e) {
        $errors[] = "Erro no banco de dados: " . $e->getMessage();
    } catch (Exception $e) {
        $errors[] = "Erro: " . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação - NM Refrigeração</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 800px;
            width: 100%;
            padding: 40px;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 32px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 16px;
        }
        .requirements {
            background: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 4px;
        }
        .requirements h3 {
            color: #007bff;
            margin-bottom: 15px;
        }
        .requirements ul {
            list-style: none;
            padding-left: 0;
        }
        .requirements li {
            padding: 8px 0;
            color: #555;
        }
        .requirements li:before {
            content: "✓ ";
            color: #28a745;
            font-weight: bold;
            margin-right: 8px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            color: #555;
            font-weight: 500;
            margin-bottom: 8px;
        }
        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        input:focus {
            outline: none;
            border-color: #667eea;
        }
        button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            transition: transform 0.2s;
        }
        button:hover {
            transform: translateY(-2px);
        }
        .messages {
            margin-top: 30px;
        }
        .message {
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 4px;
            font-size: 14px;
        }
        .success {
            background: #d4edda;
            border-left: 4px solid #28a745;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            color: #721c24;
        }
        .warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            color: #856404;
        }
        .next-steps {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 20px;
            margin-top: 20px;
            border-radius: 4px;
        }
        .next-steps h3 {
            color: #2196F3;
            margin-bottom: 15px;
        }
        .next-steps ol {
            padding-left: 20px;
            color: #555;
        }
        .next-steps li {
            padding: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🛠️ Instalador NM Refrigeração</h1>
        <p class="subtitle">Sistema Integrado de Gestão - Versão 1.0</p>
        
        <?php if (empty($success) && empty($errors)): ?>
        <div class="requirements">
            <h3>Requisitos do Sistema</h3>
            <ul>
                <li>PHP 7.4 ou superior</li>
                <li>MySQL 5.7 ou superior</li>
                <li>Extensões PHP: PDO, PDO_MySQL, mbstring, json</li>
                <li>Permissões de escrita na pasta /public</li>
            </ul>
        </div>
        
        <form method="POST">
            <p style="color: #666; margin-bottom: 20px;">
                Clique no botão abaixo para iniciar a instalação. O processo irá:
            </p>
            <ul style="color: #666; margin-bottom: 30px; padding-left: 20px;">
                <li>Criar o banco de dados "<?= DB_NAME ?>"</li>
                <li>Criar 30 tabelas do sistema</li>
                <li>Inserir dados iniciais</li>
                <li>Criar usuário administrador</li>
                <li>Configurar diretórios</li>
            </ul>
            
            <button type="submit">Iniciar Instalação</button>
        </form>
        <?php endif; ?>
        
        <?php if (!empty($errors) || !empty($success) || !empty($warnings)): ?>
        <div class="messages">
            <?php foreach ($errors as $error): ?>
            <div class="message error"><?= htmlspecialchars($error) ?></div>
            <?php endforeach; ?>
            
            <?php foreach ($success as $msg): ?>
            <div class="message success"><?= htmlspecialchars($msg) ?></div>
            <?php endforeach; ?>
            
            <?php foreach ($warnings as $warning): ?>
            <div class="message warning"><?= htmlspecialchars($warning) ?></div>
            <?php endforeach; ?>
        </div>
        
        <?php if (empty($errors) && !empty($success)): ?>
        <div class="next-steps">
            <h3>📋 Próximos Passos</h3>
            <ol>
                <li><strong>DELETE</strong> este arquivo (install.php) por segurança</li>
                <li>Configure as chaves de API no arquivo <code>.env</code></li>
                <li>Acesse <code>/login.html</code> para fazer login</li>
                <li>Altere a senha do administrador imediatamente</li>
                <li>Configure os dados da empresa em Configurações</li>
                <li>Execute <code>/teste-completo.php</code> para validar</li>
            </ol>
            <br>
            <a href="/login.html" style="display: inline-block; background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">
                Ir para Login
            </a>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
