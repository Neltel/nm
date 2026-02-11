<?php
/**
 * ARQUIVO: auth.php
 * 
 * Função: Endpoints de autenticação e gerenciamento de usuários
 * Entrada: Credenciais, tokens JWT, dados de usuário
 * Processamento: Login, logout, refresh token, obter usuário, alterar senha
 * Saída: Token JWT, dados do usuário, mensagens de sucesso/erro
 * 
 * Endpoints disponíveis:
 * - POST /api/auth/login - Realiza login do usuário
 * - POST /api/auth/logout - Realiza logout do usuário
 * - POST /api/auth/refresh - Renova o token JWT
 * - GET /api/auth/me - Obtém dados do usuário autenticado
 * - POST /api/auth/change-password - Altera senha do usuário
 */

// Inicializa classe de autenticação
$auth = new Auth($db);

// Obtém subrota (segunda parte da URL)
$subroute = isset($parts[1]) ? $parts[1] : '';

// Roteia para ação apropriada baseado no método e subrouta
switch ($subroute) {
    
    /**
     * POST /api/auth/login
     * Realiza login do usuário com email e senha
     * Retorna token JWT e dados do usuário
     */
    case 'login':
        if ($method !== 'POST') {
            sendError('Método não permitido', 405);
        }
        
        // Valida entrada
        if (empty($input['email']) || empty($input['senha'])) {
            sendError('Email e senha são obrigatórios', 400);
        }
        
        // Valida formato do email
        if (!Validator::validateEmail($input['email'])) {
            sendError('Formato de email inválido', 400);
        }
        
        // Tenta fazer login
        $resultado = $auth->login($input['email'], $input['senha']);
        
        if (!$resultado) {
            sendError('Email ou senha inválidos', 401);
        }
        
        // Sucesso
        sendSuccess($resultado, 'Login realizado com sucesso', 200);
        break;
    
    /**
     * POST /api/auth/logout
     * Realiza logout do usuário
     * Nota: O logout é feito no cliente removendo o token
     */
    case 'logout':
        if ($method !== 'POST') {
            sendError('Método não permitido', 405);
        }
        
        // Valida autenticação
        $usuario = $auth->getAuthenticatedUser();
        if (!$usuario) {
            sendError('Usuário não autenticado', 401);
        }
        
        // Atualiza último logout
        $db->update(
            'usuarios',
            ['ultimo_logout' => date('Y-m-d H:i:s')],
            'id = ?',
            [$usuario['id']]
        );
        
        sendSuccess([], 'Logout realizado com sucesso', 200);
        break;
    
    /**
     * POST /api/auth/refresh
     * Renova o token JWT do usuário
     * Retorna novo token com prazo renovado
     */
    case 'refresh':
        if ($method !== 'POST') {
            sendError('Método não permitido', 405);
        }
        
        // Valida autenticação
        $usuario = $auth->getAuthenticatedUser();
        if (!$usuario) {
            sendError('Token inválido ou expirado', 401);
        }
        
        // Obtém dados completos do usuário
        $usuarioCompleto = $db->queryOne(
            "SELECT * FROM usuarios WHERE id = ? AND ativo = 1",
            [$usuario['id']]
        );
        
        if (!$usuarioCompleto) {
            sendError('Usuário não encontrado', 404);
        }
        
        // Gera novo token
        $novoToken = $auth->generateToken($usuarioCompleto);
        
        sendSuccess([
            'token' => $novoToken,
            'usuario' => [
                'id' => $usuarioCompleto['id'],
                'nome' => $usuarioCompleto['nome'],
                'email' => $usuarioCompleto['email'],
                'tipo' => $usuarioCompleto['tipo']
            ]
        ], 'Token renovado com sucesso', 200);
        break;
    
    /**
     * GET /api/auth/me
     * Obtém dados do usuário autenticado
     * Retorna informações do usuário logado
     */
    case 'me':
        if ($method !== 'GET') {
            sendError('Método não permitido', 405);
        }
        
        // Valida autenticação
        $usuario = $auth->getAuthenticatedUser();
        if (!$usuario) {
            sendError('Usuário não autenticado', 401);
        }
        
        // Obtém dados completos
        $usuarioCompleto = $db->queryOne(
            "SELECT id, nome, email, tipo, criado_em, ultimo_login FROM usuarios WHERE id = ?",
            [$usuario['id']]
        );
        
        sendSuccess($usuarioCompleto, 'Dados do usuário obtidos com sucesso', 200);
        break;
    
    /**
     * POST /api/auth/change-password
     * Altera a senha do usuário autenticado
     * Requer senha atual e nova senha
     */
    case 'change-password':
        if ($method !== 'POST') {
            sendError('Método não permitido', 405);
        }
        
        // Valida autenticação
        $usuario = $auth->getAuthenticatedUser();
        if (!$usuario) {
            sendError('Usuário não autenticado', 401);
        }
        
        // Valida entrada
        if (empty($input['senha_atual']) || empty($input['senha_nova']) || empty($input['confirmar_senha'])) {
            sendError('Todos os campos de senha são obrigatórios', 400);
        }
        
        // Valida confirmação de senha
        if ($input['senha_nova'] !== $input['confirmar_senha']) {
            sendError('A nova senha e confirmação não correspondem', 400);
        }
        
        // Valida comprimento mínimo
        if (strlen($input['senha_nova']) < 6) {
            sendError('A nova senha deve ter no mínimo 6 caracteres', 400);
        }
        
        // Tenta alterar senha
        $resultado = $auth->changePassword(
            $usuario['id'],
            $input['senha_atual'],
            $input['senha_nova']
        );
        
        if (!$resultado) {
            sendError('Senha atual incorreta', 401);
        }
        
        sendSuccess([], 'Senha alterada com sucesso', 200);
        break;
    
    default:
        sendError('Ação não encontrada', 404);
        break;
}
?>
