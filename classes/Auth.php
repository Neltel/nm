<?php
/**
 * Classe Auth
 * 
 * Função: Gerencia autenticação de usuários e tokens JWT
 * Entrada: Credenciais de usuário ou token
 * Processamento: Valida credenciais, gera e valida tokens JWT
 * Saída: Token JWT ou dados do usuário autenticado
 * Uso: $auth = new Auth($db);
 */

class Auth {
    private $db;
    private $secret;
    private $expiration;
    
    /**
     * Construtor
     * 
     * @param Database $db Instância do banco de dados
     */
    public function __construct($db) {
        $this->db = $db;
        $this->secret = JWT_SECRET ?? 'default_secret_change_this';
        $this->expiration = JWT_EXPIRATION ?? 3600;
    }
    
    /**
     * Realiza login do usuário
     * 
     * @param string $email Email do usuário
     * @param string $senha Senha do usuário
     * @return array|false Dados do usuário e token, ou false
     */
    public function login($email, $senha) {
        $usuario = $this->db->queryOne(
            "SELECT * FROM usuarios WHERE email = ? AND ativo = 1",
            [$email]
        );
        
        if (!$usuario) {
            return false;
        }
        
        // Verifica senha
        if (!password_verify($senha, $usuario['senha'])) {
            return false;
        }
        
        // Gera token JWT
        $token = $this->generateToken($usuario);
        
        // Atualiza último login
        $this->db->update(
            'usuarios',
            ['ultimo_login' => date('Y-m-d H:i:s')],
            'id = ?',
            [$usuario['id']]
        );
        
        return [
            'usuario' => [
                'id' => $usuario['id'],
                'nome' => $usuario['nome'],
                'email' => $usuario['email'],
                'tipo' => $usuario['tipo']
            ],
            'token' => $token
        ];
    }
    
    /**
     * Gera token JWT
     * 
     * @param array $usuario Dados do usuário
     * @return string Token JWT
     */
    public function generateToken($usuario) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode([
            'user_id' => $usuario['id'],
            'email' => $usuario['email'],
            'tipo' => $usuario['tipo'],
            'iat' => time(),
            'exp' => time() + $this->expiration
        ]);
        
        $base64UrlHeader = $this->base64UrlEncode($header);
        $base64UrlPayload = $this->base64UrlEncode($payload);
        
        $signature = hash_hmac(
            'sha256',
            $base64UrlHeader . "." . $base64UrlPayload,
            $this->secret,
            true
        );
        $base64UrlSignature = $this->base64UrlEncode($signature);
        
        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }
    
    /**
     * Valida e decodifica token JWT
     * 
     * @param string $token Token JWT
     * @return array|false Payload do token ou false
     */
    public function validateToken($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }
        
        list($base64UrlHeader, $base64UrlPayload, $base64UrlSignature) = $parts;
        
        // Verifica assinatura
        $signature = hash_hmac(
            'sha256',
            $base64UrlHeader . "." . $base64UrlPayload,
            $this->secret,
            true
        );
        $base64UrlSignatureExpected = $this->base64UrlEncode($signature);
        
        if ($base64UrlSignature !== $base64UrlSignatureExpected) {
            return false;
        }
        
        // Decodifica payload
        $payload = json_decode($this->base64UrlDecode($base64UrlPayload), true);
        
        // Verifica expiração
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return false;
        }
        
        return $payload;
    }
    
    /**
     * Verifica se usuário tem permissão
     * 
     * @param array $usuario Dados do usuário
     * @param string $permissao Permissão requerida
     * @return bool
     */
    public function hasPermission($usuario, $permissao) {
        // Admin tem todas as permissões
        if ($usuario['tipo'] === USUARIO_ADMIN) {
            return true;
        }
        
        // Técnico tem permissões específicas
        if ($usuario['tipo'] === USUARIO_TECNICO) {
            $permissoesTecnico = [
                'orcamentos', 'tabelas_precos', 'historico',
                'garantias', 'preventivas', 'clientes',
                'relatorios', 'financeiro', 'pmp', 'ia'
            ];
            return in_array($permissao, $permissoesTecnico);
        }
        
        // Cliente tem permissões limitadas
        if ($usuario['tipo'] === USUARIO_CLIENTE) {
            $permissoesCliente = ['agendamento', 'calculadora', 'guia'];
            return in_array($permissao, $permissoesCliente);
        }
        
        return false;
    }
    
    /**
     * Obtém usuário autenticado a partir do token
     * 
     * @return array|false Dados do usuário ou false
     */
    public function getAuthenticatedUser() {
        $headers = getallheaders();
        $token = null;
        
        // Busca token no header Authorization
        if (isset($headers['Authorization'])) {
            $matches = [];
            if (preg_match('/Bearer\s+(.*)$/i', $headers['Authorization'], $matches)) {
                $token = $matches[1];
            }
        }
        
        if (!$token) {
            return false;
        }
        
        $payload = $this->validateToken($token);
        if (!$payload) {
            return false;
        }
        
        // Busca dados atualizados do usuário
        $usuario = $this->db->queryOne(
            "SELECT id, nome, email, tipo FROM usuarios WHERE id = ? AND ativo = 1",
            [$payload['user_id']]
        );
        
        return $usuario ?: false;
    }
    
    /**
     * Cria novo usuário
     * 
     * @param array $dados Dados do usuário
     * @return int ID do usuário criado
     */
    public function createUser($dados) {
        $dados['senha'] = password_hash($dados['senha'], PASSWORD_DEFAULT);
        $dados['criado_em'] = date('Y-m-d H:i:s');
        $dados['ativo'] = 1;
        
        return $this->db->insert('usuarios', $dados);
    }
    
    /**
     * Altera senha do usuário
     * 
     * @param int $userId ID do usuário
     * @param string $senhaAtual Senha atual
     * @param string $senhaNova Nova senha
     * @return bool
     */
    public function changePassword($userId, $senhaAtual, $senhaNova) {
        $usuario = $this->db->queryOne(
            "SELECT senha FROM usuarios WHERE id = ?",
            [$userId]
        );
        
        if (!$usuario || !password_verify($senhaAtual, $usuario['senha'])) {
            return false;
        }
        
        $senhaHash = password_hash($senhaNova, PASSWORD_DEFAULT);
        $this->db->update(
            'usuarios',
            ['senha' => $senhaHash],
            'id = ?',
            [$userId]
        );
        
        return true;
    }
    
    /**
     * Codifica em base64 URL-safe
     * 
     * @param string $data Dados para codificar
     * @return string
     */
    private function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Decodifica base64 URL-safe
     * 
     * @param string $data Dados para decodificar
     * @return string
     */
    private function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
?>
