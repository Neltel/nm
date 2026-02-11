<?php
/**
 * ARQUIVO: utils.php
 * 
 * Função: Endpoints utilitários da API
 * Entrada: Arquivo para upload, CEP para busca, dados para exportação
 * Processamento: Upload de arquivos, busca de CEP via ViaCEP, exportação para Excel
 * Saída: URL do arquivo, dados do endereço, arquivo Excel gerado
 * 
 * Endpoints disponíveis:
 * - POST /api/utils/upload - Realiza upload de arquivo
 * - GET /api/utils/cep/:cep - Busca informações de CEP
 * - POST /api/utils/export-excel - Exporta dados para arquivo Excel
 */

// Inicializa classe de autenticação
$auth = new Auth($db);

// Valida autenticação para todos os endpoints
$usuario = $auth->getAuthenticatedUser();
if (!$usuario) {
    sendError('Usuário não autenticado', 401);
}

// Obtém subrouta (segunda parte da URL)
$subroute = isset($parts[1]) ? $parts[1] : '';

// Roteia para ação apropriada
switch ($subroute) {
    
    /**
     * POST /api/utils/upload
     * Realiza upload de arquivo
     * Retorna URL do arquivo armazenado
     */
    case 'upload':
        if ($method !== 'POST') {
            sendError('Método não permitido', 405);
        }
        
        // Valida se arquivo foi enviado
        if (!isset($_FILES['arquivo']) || empty($_FILES['arquivo']['name'])) {
            sendError('Nenhum arquivo foi enviado', 400);
        }
        
        $arquivo = $_FILES['arquivo'];
        
        // Valida erros de upload
        if ($arquivo['error'] !== UPLOAD_ERR_OK) {
            $erroMensagens = [
                UPLOAD_ERR_INI_SIZE => 'Arquivo excede o tamanho máximo permitido pela configuração do servidor',
                UPLOAD_ERR_FORM_SIZE => 'Arquivo excede o tamanho máximo permitido pelo formulário',
                UPLOAD_ERR_PARTIAL => 'Upload do arquivo foi interrompido',
                UPLOAD_ERR_NO_FILE => 'Nenhum arquivo foi enviado',
                UPLOAD_ERR_NO_TMP_DIR => 'Diretório temporário não existe',
                UPLOAD_ERR_CANT_WRITE => 'Não foi possível escrever o arquivo no disco',
            ];
            
            $mensagem = $erroMensagens[$arquivo['error']] ?? 'Erro desconhecido no upload';
            sendError($mensagem, 400);
        }
        
        // Valida tipo de arquivo
        if (!Validator::validateFileType($arquivo['name'])) {
            sendError('Tipo de arquivo não permitido', 400);
        }
        
        // Valida tamanho de arquivo
        if (!Validator::validateFileSize($arquivo['size'])) {
            sendError('Arquivo excede o tamanho máximo permitido (' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB)', 400);
        }
        
        // Cria diretório de upload se não existir
        if (!is_dir(UPLOADS_PATH)) {
            mkdir(UPLOADS_PATH, 0755, true);
        }
        
        // Gera nome único para arquivo
        $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
        $nomeArquivo = uniqid('upload_') . '_' . date('YmdHis') . '.' . $extensao;
        $caminhoCompleto = UPLOADS_PATH . '/' . $nomeArquivo;
        
        // Move arquivo para diretório de uploads
        if (!move_uploaded_file($arquivo['tmp_name'], $caminhoCompleto)) {
            sendError('Não foi possível salvar o arquivo', 500);
        }
        
        // Retorna informações do arquivo
        $urlArquivo = '/uploads/' . $nomeArquivo;
        
        sendSuccess([
            'arquivo' => $nomeArquivo,
            'url' => $urlArquivo,
            'tipo' => $arquivo['type'],
            'tamanho' => $arquivo['size'],
            'caminho' => $caminhoCompleto
        ], 'Arquivo enviado com sucesso', 201);
        break;
    
    /**
     * GET /api/utils/cep/:cep
     * Busca informações de CEP usando a API do ViaCEP
     * Retorna informações de endereço (rua, cidade, estado, etc)
     */
    case 'cep':
        if ($method !== 'GET') {
            sendError('Método não permitido', 405);
        }
        
        $cep = isset($parts[2]) ? $parts[2] : '';
        
        // Valida CEP
        if (!Validator::validateCEP($cep)) {
            sendError('CEP inválido', 400);
        }
        
        // Remove formatação
        $cepLimpo = preg_replace('/[^0-9]/', '', $cep);
        
        try {
            // Busca CEP na API do ViaCEP
            $url = "https://viacep.com.br/ws/{$cepLimpo}/json/";
            
            // Desabilita SSL verification se necessário (apenas em desenvolvimento)
            $opcoes = [
                'http' => [
                    'method' => 'GET',
                    'timeout' => 5,
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                    ]
                ]
            ];
            
            $contexto = stream_context_create($opcoes);
            $resposta = @file_get_contents($url, false, $contexto);
            
            if ($resposta === false) {
                sendError('Não foi possível conectar ao serviço de CEP', 503);
            }
            
            $dados = json_decode($resposta, true);
            
            // Verifica se CEP foi encontrado
            if (isset($dados['erro']) && $dados['erro']) {
                sendError('CEP não encontrado', 404);
            }
            
            // Formata resposta
            $resultado = [
                'cep' => Validator::formatCEP($dados['cep']),
                'logradouro' => $dados['logradouro'],
                'complemento' => $dados['complemento'],
                'bairro' => $dados['bairro'],
                'localidade' => $dados['localidade'],
                'uf' => $dados['uf'],
                'ibge' => $dados['ibge'],
                'gia' => $dados['gia'],
                'ddd' => $dados['ddd'],
                'siafi' => $dados['siafi']
            ];
            
            sendSuccess($resultado, 'CEP encontrado com sucesso', 200);
            
        } catch (Exception $e) {
            sendError('Erro ao buscar CEP: ' . $e->getMessage(), 503);
        }
        break;
    
    /**
     * POST /api/utils/export-excel
     * Exporta dados para arquivo Excel
     * Recebe dados da tabela e gera arquivo XLSX
     */
    case 'export-excel':
        if ($method !== 'POST') {
            sendError('Método não permitido', 405);
        }
        
        // Valida entrada
        if (empty($input['tabela'])) {
            sendError('Tabela é obrigatória', 400);
        }
        
        $tabela = $input['tabela'];
        $filtros = isset($input['filtros']) ? $input['filtros'] : [];
        
        // Whitelist de tabelas permitidas para exportação
        $tabelasPermitidas = ['clientes', 'orcamentos', 'agendamentos'];
        
        if (!in_array($tabela, $tabelasPermitidas)) {
            sendError('Tabela não autorizada para exportação', 403);
        }
        
        try {
            // Busca dados da tabela
            $where = '';
            $params = [];
            
            if (!empty($filtros)) {
                foreach ($filtros as $campo => $valor) {
                    if (!empty($where)) {
                        $where .= ' AND ';
                    }
                    $where .= "{$campo} = ?";
                    $params[] = $valor;
                }
            }
            
            if (!empty($where)) {
                $dados = $db->find($tabela, [
                    'where' => $where,
                    'params' => $params
                ]);
            } else {
                $dados = $db->find($tabela);
            }
            
            if (empty($dados)) {
                sendError('Nenhum dado disponível para exportação', 404);
            }
            
            // Cria diretório de exports se não existir
            $exportsPath = UPLOADS_PATH . '/exports';
            if (!is_dir($exportsPath)) {
                mkdir($exportsPath, 0755, true);
            }
            
            // Gera nome do arquivo
            $nomeArquivo = $tabela . '_' . date('YmdHis') . '.csv';
            $caminhoArquivo = $exportsPath . '/' . $nomeArquivo;
            
            // Cria arquivo CSV
            $arquivo = fopen($caminhoArquivo, 'w');
            
            if ($arquivo === false) {
                sendError('Não foi possível criar arquivo de exportação', 500);
            }
            
            // Escreve cabeçalho (nomes das colunas)
            if (!empty($dados)) {
                $colunas = array_keys((array)$dados[0]);
                fputcsv($arquivo, $colunas, ';');
                
                // Escreve dados
                foreach ($dados as $linha) {
                    fputcsv($arquivo, (array)$linha, ';');
                }
            }
            
            fclose($arquivo);
            
            // Retorna informações do arquivo
            $urlArquivo = '/uploads/exports/' . $nomeArquivo;
            
            sendSuccess([
                'arquivo' => $nomeArquivo,
                'url' => $urlArquivo,
                'total_registros' => count($dados),
                'formato' => 'CSV',
                'criado_em' => date('Y-m-d H:i:s')
            ], 'Exportação realizada com sucesso', 201);
            
        } catch (Exception $e) {
            sendError('Erro ao exportar dados: ' . $e->getMessage(), 500);
        }
        break;
    
    default:
        sendError('Ação não encontrada', 404);
        break;
}
?>
