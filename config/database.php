<?php
/**
 * Configurações de Conexão com Banco de Dados
 * 
 * Função: Retorna array com configurações do banco de dados
 * Uso: $dbConfig = require __DIR__ . '/config/database.php';
 */

return [
    'host' => DB_HOST ?? 'localhost',
    'database' => DB_NAME ?? 'nmrefrig_imperio',
    'username' => DB_USER ?? 'nmrefrig_imperio',
    'password' => DB_PASS ?? 'JEJ5qnvpLRbACP7tUhu6',
    'charset' => DB_CHARSET ?? 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ]
];
?>
