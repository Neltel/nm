<?php
/**
 * Classe Database
 * 
 * Função: Gerencia conexões e operações com o banco de dados
 * Entrada: Configurações do banco de dados
 * Processamento: Cria conexão PDO e fornece métodos CRUD genéricos
 * Saída: Resultados das consultas
 * Uso: $db = new Database();
 */

class Database {
    private $pdo;
    private $config;
    
    /**
     * Construtor - Inicializa a conexão com o banco
     * 
     * @param array|null $config Configurações do banco (opcional)
     */
    public function __construct($config = null) {
        if ($config === null) {
            $config = require __DIR__ . '/../config/database.php';
        }
        
        $this->config = $config;
        $this->connect();
    }
    
    /**
     * Estabelece conexão com o banco de dados
     */
    private function connect() {
        try {
            $dsn = sprintf(
                "mysql:host=%s;dbname=%s;charset=%s",
                $this->config['host'],
                $this->config['database'],
                $this->config['charset']
            );
            
            $this->pdo = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                $this->config['options']
            );
            
            // Define timezone
            $this->pdo->exec("SET time_zone = '-03:00'");
            
        } catch (PDOException $e) {
            $this->logError("Erro de conexão: " . $e->getMessage());
            throw new Exception("Não foi possível conectar ao banco de dados");
        }
    }
    
    /**
     * Retorna a instância PDO
     * 
     * @return PDO
     */
    public function getPdo() {
        return $this->pdo;
    }
    
    /**
     * Executa uma query SELECT
     * 
     * @param string $sql Query SQL
     * @param array $params Parâmetros para prepared statement
     * @return array Resultados da query
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->logError("Erro na query: " . $e->getMessage() . " | SQL: " . $sql);
            throw new Exception("Erro ao executar consulta");
        }
    }
    
    /**
     * Executa uma query SELECT e retorna apenas uma linha
     * 
     * @param string $sql Query SQL
     * @param array $params Parâmetros
     * @return array|false Resultado ou false
     */
    public function queryOne($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (PDOException $e) {
            $this->logError("Erro na query: " . $e->getMessage());
            throw new Exception("Erro ao executar consulta");
        }
    }
    
    /**
     * Insere um registro na tabela
     * 
     * @param string $table Nome da tabela
     * @param array $data Dados para inserir (chave => valor)
     * @return int ID do registro inserido
     */
    public function insert($table, $data) {
        try {
            $fields = implode(', ', array_keys($data));
            $placeholders = implode(', ', array_fill(0, count($data), '?'));
            
            $sql = "INSERT INTO {$table} ({$fields}) VALUES ({$placeholders})";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(array_values($data));
            
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            $this->logError("Erro ao inserir: " . $e->getMessage());
            throw new Exception("Erro ao inserir registro");
        }
    }
    
    /**
     * Atualiza registros na tabela
     * 
     * @param string $table Nome da tabela
     * @param array $data Dados para atualizar
     * @param string $where Condição WHERE
     * @param array $whereParams Parâmetros da condição
     * @return int Número de linhas afetadas
     */
    public function update($table, $data, $where, $whereParams = []) {
        try {
            $sets = [];
            foreach ($data as $field => $value) {
                $sets[] = "{$field} = ?";
            }
            $setString = implode(', ', $sets);
            
            $sql = "UPDATE {$table} SET {$setString} WHERE {$where}";
            $params = array_merge(array_values($data), $whereParams);
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->rowCount();
        } catch (PDOException $e) {
            $this->logError("Erro ao atualizar: " . $e->getMessage());
            throw new Exception("Erro ao atualizar registro");
        }
    }
    
    /**
     * Deleta registros da tabela
     * 
     * @param string $table Nome da tabela
     * @param string $where Condição WHERE
     * @param array $params Parâmetros
     * @return int Número de linhas deletadas
     */
    public function delete($table, $where, $params = []) {
        try {
            $sql = "DELETE FROM {$table} WHERE {$where}";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->rowCount();
        } catch (PDOException $e) {
            $this->logError("Erro ao deletar: " . $e->getMessage());
            throw new Exception("Erro ao deletar registro");
        }
    }
    
    /**
     * Busca registros com paginação
     * 
     * @param string $table Nome da tabela
     * @param array $options Opções (where, order, limit, offset)
     * @return array Resultados
     */
    public function find($table, $options = []) {
        $sql = "SELECT * FROM {$table}";
        $params = [];
        
        // WHERE
        if (isset($options['where'])) {
            $sql .= " WHERE " . $options['where'];
            $params = $options['params'] ?? [];
        }
        
        // ORDER BY
        if (isset($options['order'])) {
            $sql .= " ORDER BY " . $options['order'];
        }
        
        // LIMIT e OFFSET
        if (isset($options['limit'])) {
            $sql .= " LIMIT " . intval($options['limit']);
            if (isset($options['offset'])) {
                $sql .= " OFFSET " . intval($options['offset']);
            }
        }
        
        return $this->query($sql, $params);
    }
    
    /**
     * Conta registros
     * 
     * @param string $table Nome da tabela
     * @param string $where Condição WHERE (opcional)
     * @param array $params Parâmetros (opcional)
     * @return int Contagem
     */
    public function count($table, $where = null, $params = []) {
        $sql = "SELECT COUNT(*) as total FROM {$table}";
        if ($where) {
            $sql .= " WHERE {$where}";
        }
        
        $result = $this->queryOne($sql, $params);
        return $result ? (int)$result['total'] : 0;
    }
    
    /**
     * Inicia uma transação
     */
    public function beginTransaction() {
        $this->pdo->beginTransaction();
    }
    
    /**
     * Confirma uma transação
     */
    public function commit() {
        $this->pdo->commit();
    }
    
    /**
     * Desfaz uma transação
     */
    public function rollback() {
        $this->pdo->rollback();
    }
    
    /**
     * Registra erros no log
     * 
     * @param string $message Mensagem de erro
     */
    private function logError($message) {
        $logFile = LOGS_PATH . '/database_' . date('Y-m-d') . '.log';
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}\n";
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
}
?>
