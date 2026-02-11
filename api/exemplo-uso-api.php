<?php
/**
 * ARQUIVO: exemplo-uso-api.php
 * 
 * Função: Exemplos de como usar a API REST
 * Entrada: N/A (uso via CLI ou browser)
 * Processamento: Demonstra requisições cURL para todos os endpoints
 * Saída: Exemplos e sugestões
 * 
 * Como usar:
 * 1. Configure o $baseUrl com o URL da sua API
 * 2. Ajuste email e senha conforme necessário
 * 3. Execute via CLI: php exemplo-uso-api.php
 */

// URL base da API
$baseUrl = 'http://localhost/api/routes.php';

// Classe auxiliar para fazer requisições
class ApiClient {
    private $baseUrl;
    private $token = null;
    
    public function __construct($baseUrl) {
        $this->baseUrl = $baseUrl;
    }
    
    /**
     * Faz uma requisição HTTP
     */
    public function request($method, $endpoint, $data = null, $headers = []) {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
        
        $defaultHeaders = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        
        if ($this->token) {
            $defaultHeaders[] = 'Authorization: Bearer ' . $this->token;
        }
        
        $headers = array_merge($defaultHeaders, $headers);
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);
        
        if ($data !== null && in_array($method, ['POST', 'PUT'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($response === false) {
            echo "Erro cURL: " . curl_error($ch) . "\n";
            curl_close($ch);
            return null;
        }
        
        curl_close($ch);
        
        return [
            'code' => $httpCode,
            'body' => json_decode($response, true)
        ];
    }
    
    /**
     * Define o token JWT
     */
    public function setToken($token) {
        $this->token = $token;
    }
    
    /**
     * Obtém o token JWT
     */
    public function getToken() {
        return $this->token;
    }
}

/**
 * Função auxiliar para exibir resultado formatado
 */
function exibirResultado($titulo, $resultado) {
    echo "\n" . str_repeat("=", 80) . "\n";
    echo "[$titulo]\n";
    echo str_repeat("=", 80) . "\n";
    
    if ($resultado === null) {
        echo "Erro ao fazer requisição\n";
        return;
    }
    
    echo "Status: " . $resultado['code'] . "\n";
    echo json_encode($resultado['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
}

// ============================================================================
// EXEMPLOS DE USO
// ============================================================================

echo "\n╔════════════════════════════════════════════════════════════════════════════════╗";
echo "\n║                         EXEMPLOS DE USO DA API                                 ║";
echo "\n╚════════════════════════════════════════════════════════════════════════════════╝\n";

// Inicializa cliente API
$api = new ApiClient($baseUrl);

// ============================================================================
// 1. AUTENTICAÇÃO
// ============================================================================

echo "\n[1] AUTENTICAÇÃO\n";
echo "─────────────────\n";

// Login
echo "\n1.1 - Fazendo login...\n";
$loginResult = $api->request('POST', '/auth/login', [
    'email' => 'admin@example.com',
    'senha' => 'senha123'
]);
exibirResultado('POST /auth/login', $loginResult);

// Verifica se login foi bem-sucedido
if ($loginResult && $loginResult['code'] === 200 && $loginResult['body']['success']) {
    $token = $loginResult['body']['data']['token'];
    $api->setToken($token);
    echo "\n✓ Token obtido com sucesso!\n";
} else {
    echo "\n✗ Erro no login. Usando token de exemplo para continuar.\n";
    $api->setToken('token_exemplo');
}

// Obter dados do usuário logado
echo "\n1.2 - Obtendo dados do usuário autenticado...\n";
$meResult = $api->request('GET', '/auth/me');
exibirResultado('GET /auth/me', $meResult);

// Renovar token
echo "\n1.3 - Renovando token JWT...\n";
$refreshResult = $api->request('POST', '/auth/refresh');
exibirResultado('POST /auth/refresh', $refreshResult);

// ============================================================================
// 2. GERENCIAMENTO DE CLIENTES
// ============================================================================

echo "\n\n[2] GERENCIAMENTO DE CLIENTES\n";
echo "──────────────────────────────\n";

// Listar clientes com paginação
echo "\n2.1 - Listando clientes (página 1, 10 itens)...\n";
$listResult = $api->request('GET', '/clientes?page=1&limit=10&order=nome%20ASC');
exibirResultado('GET /clientes', $listResult);

// Buscar cliente específico
echo "\n2.2 - Obtendo cliente com ID 1...\n";
$getResult = $api->request('GET', '/clientes/1');
exibirResultado('GET /clientes/1', $getResult);

// Buscar clientes por termo
echo "\n2.3 - Buscando clientes por termo 'João'...\n";
$searchResult = $api->request('GET', '/clientes/search?q=Jo%C3%A3o&page=1&limit=10');
exibirResultado('GET /clientes/search', $searchResult);

// Criar novo cliente
echo "\n2.4 - Criando novo cliente...\n";
$createResult = $api->request('POST', '/clientes', [
    'nome' => 'Empresa Teste Ltda',
    'email' => 'contato@empresateste.com.br',
    'cpf' => '12345678901',
    'telefone' => '(11) 98765-4321',
    'endereco' => 'Rua das Flores, 123',
    'cidade' => 'São Paulo',
    'estado' => 'SP',
    'cep' => '01310-100',
    'observacoes' => 'Cliente criado via API'
]);
exibirResultado('POST /clientes', $createResult);

// Guardar ID do cliente criado para próximas operações
$novoClienteId = $createResult && $createResult['code'] === 201 
    ? $createResult['body']['data']['id'] 
    : 1;

// Atualizar cliente
echo "\n2.5 - Atualizando cliente ID $novoClienteId...\n";
$updateResult = $api->request('PUT', "/clientes/{$novoClienteId}", [
    'telefone' => '(11) 91234-5678',
    'observacoes' => 'Telefone atualizado via API'
]);
exibirResultado("PUT /clientes/{$novoClienteId}", $updateResult);

// Deletar cliente
echo "\n2.6 - Deletando cliente ID $novoClienteId...\n";
$deleteResult = $api->request('DELETE', "/clientes/{$novoClienteId}");
exibirResultado("DELETE /clientes/{$novoClienteId}", $deleteResult);

// ============================================================================
// 3. ENDPOINTS UTILITÁRIOS
// ============================================================================

echo "\n\n[3] ENDPOINTS UTILITÁRIOS\n";
echo "─────────────────────────\n";

// Buscar CEP
echo "\n3.1 - Buscando informações de CEP 01310-100...\n";
$cepResult = $api->request('GET', '/utils/cep/01310-100');
exibirResultado('GET /utils/cep/01310-100', $cepResult);

// Exportar dados para Excel
echo "\n3.2 - Exportando dados de clientes para CSV...\n";
$exportResult = $api->request('POST', '/utils/export-excel', [
    'tabela' => 'clientes',
    'filtros' => [
        'estado' => 'SP'
    ]
]);
exibirResultado('POST /utils/export-excel', $exportResult);

// ============================================================================
// 4. TESTE DE ERROS
// ============================================================================

echo "\n\n[4] TESTES DE ERROS (VALIDAÇÕES)\n";
echo "─────────────────────────────────\n";

// Login com credenciais inválidas
echo "\n4.1 - Tentando login com credenciais inválidas...\n";
$invalidLoginResult = $api->request('POST', '/auth/login', [
    'email' => 'invalido@example.com',
    'senha' => 'senhaErrada'
]);
exibirResultado('POST /auth/login (ERRO)', $invalidLoginResult);

// Email inválido
echo "\n4.2 - Tentando criar cliente com email inválido...\n";
$invalidEmailResult = $api->request('POST', '/clientes', [
    'nome' => 'Teste',
    'email' => 'emailinvalido'
]);
exibirResultado('POST /clientes - Email inválido (ERRO)', $invalidEmailResult);

// CPF inválido
echo "\n4.3 - Tentando criar cliente com CPF inválido...\n";
$invalidCpfResult = $api->request('POST', '/clientes', [
    'nome' => 'Teste',
    'email' => 'teste@example.com',
    'cpf' => '11111111111'
]);
exibirResultado('POST /clientes - CPF inválido (ERRO)', $invalidCpfResult);

// CEP inválido
echo "\n4.4 - Tentando buscar CEP inválido...\n";
$invalidCepResult = $api->request('GET', '/utils/cep/12345');
exibirResultado('GET /utils/cep - CEP inválido (ERRO)', $invalidCepResult);

// Alterar senha
echo "\n4.5 - Alterando senha do usuário autenticado...\n";
$changePasswordResult = $api->request('POST', '/auth/change-password', [
    'senha_atual' => 'senha123',
    'senha_nova' => 'novaSenha456',
    'confirmar_senha' => 'novaSenha456'
]);
exibirResultado('POST /auth/change-password', $changePasswordResult);

// ============================================================================
// RESUMO
// ============================================================================

echo "\n\n╔════════════════════════════════════════════════════════════════════════════════╗";
echo "\n║                                  RESUMO                                         ║";
echo "\n╚════════════════════════════════════════════════════════════════════════════════╝\n";

echo <<<EOF
Os exemplos acima demonstram como usar a API para:

✓ AUTENTICAÇÃO:
  - Login com credenciais (obter token JWT)
  - Obter dados do usuário autenticado
  - Renovar token
  - Alterar senha

✓ CLIENTES:
  - Listar clientes com paginação
  - Buscar clientes por termo
  - Obter cliente específico
  - Criar novo cliente
  - Atualizar cliente existente
  - Deletar cliente

✓ UTILITÁRIOS:
  - Buscar informações de CEP (ViaCEP)
  - Exportar dados para Excel/CSV

PRÓXIMOS PASSOS:
1. Use estes exemplos como base para sua aplicação
2. Implemente tratamento de erros robusto no cliente
3. Use uma biblioteca HTTP (axios, fetch, etc.) em produção
4. Considere adicionar cache para consultas de CEP
5. Implemente rate limiting se necessário

EOF;

echo "\n";
?>
