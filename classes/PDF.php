<?php
/**
 * Classe PDF
 * 
 * Função: Gera documentos PDF (orçamentos, garantias, recibos) usando TCPDF
 * Entrada: Dados dos documentos (cliente, produtos, valores, etc)
 * Processamento: Formata dados e cria arquivo PDF usando biblioteca TCPDF
 * Saída: Arquivo PDF ou stream para download
 * Uso: $pdf = new PDF(); $pdf->generateOrcamento($dados);
 */

require_once __DIR__ . '/../vendor/autoload.php';

class PDF {
    
    private $tcpdf;
    private $empresa;
    private $logosPath;
    
    /**
     * Construtor - Inicializa configurações básicas do PDF
     * 
     * Função: Prepara o objeto TCPDF com configurações padrão da empresa
     * Entrada: Nenhuma (usa constantes definidas)
     * Processamento: Define orientação, medidas, fonte e informações da empresa
     * Saída: Objeto PDF inicializado
     */
    public function __construct() {
        $this->logosPath = __DIR__ . '/../assets/images/';
        $this->empresa = [
            'nome' => 'Novo Site',
            'cnpj' => '00.000.000/0000-00',
            'endereco' => 'Rua Exemplo, 123',
            'cidade' => 'São Paulo, SP',
            'telefone' => '(11) 99999-9999',
            'email' => 'contato@novosite.com.br',
            'website' => 'www.novosite.com.br'
        ];
    }
    
    /**
     * Inicializa novo documento PDF
     * 
     * Função: Cria instância TCPDF com configurações
     * Entrada: Orientação (P/L) e tamanho (A4, etc)
     * Processamento: Configura página, margens, fonte
     * Saída: PDF preparado para conteúdo
     */
    private function initPDF($orientation = 'P', $size = 'A4') {
        $this->tcpdf = new \TCPDF($orientation, 'mm', $size, true, 'UTF-8', false);
        
        // Configurações gerais
        $this->tcpdf->SetCreator('Novo Site');
        $this->tcpdf->SetAuthor($this->empresa['nome']);
        $this->tcpdf->SetDefaultMonospacedFont(\PDF_FONT_MONOSPACED);
        $this->tcpdf->SetMargins(15, 15, 15);
        $this->tcpdf->SetAutoPageBreak(true, 15);
        $this->tcpdf->SetFont('helvetica', '', 10);
        
        // Adiciona página
        $this->tcpdf->AddPage();
        
        return $this->tcpdf;
    }
    
    /**
     * Adiciona cabeçalho padrão com logo e informações da empresa
     * 
     * Função: Insere logo e dados da empresa no topo do PDF
     * Entrada: Nenhuma
     * Processamento: Desenha logo, nome, contatos no cabeçalho
     * Saída: Cabeçalho formatado no PDF
     */
    private function adicionarCabecalho() {
        // Logo (se existir)
        $logoPath = $this->logosPath . 'logo.png';
        if (file_exists($logoPath)) {
            $this->tcpdf->Image($logoPath, 15, 10, 30, 0, 'PNG');
        }
        
        // Informações da empresa
        $this->tcpdf->SetXY(50, 12);
        $this->tcpdf->SetFont('helvetica', 'B', 16);
        $this->tcpdf->Cell(0, 5, $this->empresa['nome'], 0, 1);
        
        $this->tcpdf->SetFont('helvetica', '', 9);
        $this->tcpdf->SetX(50);
        $this->tcpdf->Cell(0, 4, 'CNPJ: ' . $this->empresa['cnpj'], 0, 1);
        
        $this->tcpdf->SetX(50);
        $this->tcpdf->Cell(0, 4, $this->empresa['endereco'] . ' - ' . $this->empresa['cidade'], 0, 1);
        
        $this->tcpdf->SetX(50);
        $this->tcpdf->Cell(0, 4, 'Tel: ' . $this->empresa['telefone'] . ' | Email: ' . $this->empresa['email'], 0, 1);
        
        $this->tcpdf->Ln(3);
        
        // Linha separadora
        $this->tcpdf->SetDrawColor(200, 200, 200);
        $this->tcpdf->Line(15, $this->tcpdf->GetY(), 195, $this->tcpdf->GetY());
        $this->tcpdf->Ln(3);
    }
    
    /**
     * Gera documento de Orçamento
     * 
     * Função: Cria PDF com detalhes do orçamento
     * Entrada: Array com dados do orçamento:
     *   - numero: Número do orçamento
     *   - data: Data do orçamento
     *   - cliente: Dados do cliente (nome, email, telefone, etc)
     *   - produtos: Array com itens (descricao, quantidade, preco, total)
     *   - desconto: Valor ou percentual de desconto
     *   - observacoes: Observações adicionais
     * Processamento: Formata dados e monta layout profissional de orçamento
     * Saída: PDF com orçamento formatado (salvo ou enviado ao navegador)
     * 
     * @param array $dados Dados do orçamento
     * @param bool $download Se true, força download; se false, exibe no navegador
     * @return void
     * 
     * Uso: $pdf = new PDF(); $pdf->generateOrcamento($dados, true);
     */
    public function generateOrcamento($dados, $download = false) {
        try {
            // Validação básica
            if (!isset($dados['cliente']) || !isset($dados['produtos'])) {
                throw new Exception("Dados incompletos para gerar orçamento");
            }
            
            // Inicializa PDF
            $this->initPDF();
            
            // Adiciona cabeçalho
            $this->adicionarCabecalho();
            
            // Título
            $this->tcpdf->SetFont('helvetica', 'B', 14);
            $this->tcpdf->Cell(0, 8, 'ORÇAMENTO', 0, 1, 'C');
            
            // Número e data
            $this->tcpdf->SetFont('helvetica', '', 10);
            $this->tcpdf->Ln(2);
            $this->tcpdf->SetX(15);
            $this->tcpdf->Cell(95, 5, 'Nº Orçamento: ' . ($dados['numero'] ?? 'S/N'), 0, 0);
            $this->tcpdf->Cell(95, 5, 'Data: ' . date('d/m/Y', strtotime($dados['data'] ?? date('Y-m-d'))), 0, 1, 'R');
            
            // Validade
            if (isset($dados['validade'])) {
                $this->tcpdf->SetX(15);
                $this->tcpdf->Cell(0, 5, 'Válido até: ' . date('d/m/Y', strtotime($dados['validade'])), 0, 1);
            }
            
            $this->tcpdf->Ln(3);
            
            // Dados do cliente
            $this->tcpdf->SetFont('helvetica', 'B', 10);
            $this->tcpdf->Cell(0, 5, 'CLIENTE', 0, 1);
            
            $this->tcpdf->SetFont('helvetica', '', 9);
            $this->tcpdf->SetX(15);
            $this->tcpdf->Cell(0, 4, 'Nome: ' . ($dados['cliente']['nome'] ?? ''), 0, 1);
            
            $this->tcpdf->SetX(15);
            $this->tcpdf->Cell(0, 4, 'Email: ' . ($dados['cliente']['email'] ?? ''), 0, 1);
            
            if (isset($dados['cliente']['telefone'])) {
                $this->tcpdf->SetX(15);
                $this->tcpdf->Cell(0, 4, 'Telefone: ' . $dados['cliente']['telefone'], 0, 1);
            }
            
            if (isset($dados['cliente']['endereco'])) {
                $this->tcpdf->SetX(15);
                $this->tcpdf->Cell(0, 4, 'Endereço: ' . $dados['cliente']['endereco'], 0, 1);
            }
            
            $this->tcpdf->Ln(3);
            
            // Tabela de produtos
            $this->adicionarTabelaProdutos($dados['produtos']);
            
            // Resumo financeiro
            $this->adicionarResumoFinanceiro($dados);
            
            // Observações
            if (isset($dados['observacoes']) && !empty($dados['observacoes'])) {
                $this->tcpdf->Ln(2);
                $this->tcpdf->SetFont('helvetica', 'B', 10);
                $this->tcpdf->Cell(0, 5, 'OBSERVAÇÕES', 0, 1);
                
                $this->tcpdf->SetFont('helvetica', '', 9);
                $this->tcpdf->SetX(15);
                $this->tcpdf->MultiCell(165, 4, $dados['observacoes']);
            }
            
            // Rodapé
            $this->adicionarRodape();
            
            // Output
            $filename = 'orcamento_' . ($dados['numero'] ?? date('YmdHis')) . '.pdf';
            
            if ($download) {
                $this->tcpdf->Output($filename, 'D');
            } else {
                $this->tcpdf->Output($filename, 'I');
            }
            
        } catch (Exception $e) {
            $this->logError("Erro ao gerar orçamento: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Gera documento de Garantia
     * 
     * Função: Cria PDF com termos e condições de garantia
     * Entrada: Array com dados de garantia:
     *   - numero: Número do termo de garantia
     *   - data: Data de emissão
     *   - cliente: Dados do cliente
     *   - produto: Descrição do produto/serviço
     *   - periodo: Período de garantia (ex: "12 meses")
     *   - condicoes: Termos e condições
     *   - cobertura: O que está coberto
     *   - exclusoes: O que não está coberto
     * Processamento: Monta documento profissional com termos legais
     * Saída: PDF com garantia formatada
     * 
     * @param array $dados Dados da garantia
     * @param bool $download Se true, força download
     * @return void
     * 
     * Uso: $pdf = new PDF(); $pdf->generateGarantia($dados);
     */
    public function generateGarantia($dados, $download = false) {
        try {
            // Validação
            if (!isset($dados['cliente']) || !isset($dados['produto'])) {
                throw new Exception("Dados incompletos para gerar garantia");
            }
            
            // Inicializa PDF
            $this->initPDF();
            
            // Cabeçalho
            $this->adicionarCabecalho();
            
            // Título
            $this->tcpdf->SetFont('helvetica', 'B', 14);
            $this->tcpdf->Cell(0, 8, 'TERMO DE GARANTIA', 0, 1, 'C');
            
            // Número e data
            $this->tcpdf->SetFont('helvetica', '', 10);
            $this->tcpdf->Ln(2);
            $this->tcpdf->SetX(15);
            $this->tcpdf->Cell(95, 5, 'Nº: ' . ($dados['numero'] ?? 'S/N'), 0, 0);
            $this->tcpdf->Cell(95, 5, 'Data: ' . date('d/m/Y', strtotime($dados['data'] ?? date('Y-m-d'))), 0, 1, 'R');
            
            $this->tcpdf->Ln(3);
            
            // Dados do cliente
            $this->tcpdf->SetFont('helvetica', 'B', 10);
            $this->tcpdf->Cell(0, 5, 'CLIENTE', 0, 1);
            
            $this->tcpdf->SetFont('helvetica', '', 9);
            $this->tcpdf->SetX(15);
            $this->tcpdf->Cell(0, 4, 'Nome: ' . ($dados['cliente']['nome'] ?? ''), 0, 1);
            
            $this->tcpdf->SetX(15);
            $this->tcpdf->Cell(0, 4, 'Email: ' . ($dados['cliente']['email'] ?? ''), 0, 1);
            
            if (isset($dados['cliente']['telefone'])) {
                $this->tcpdf->SetX(15);
                $this->tcpdf->Cell(0, 4, 'Telefone: ' . $dados['cliente']['telefone'], 0, 1);
            }
            
            $this->tcpdf->Ln(3);
            
            // Produto/Serviço
            $this->tcpdf->SetFont('helvetica', 'B', 10);
            $this->tcpdf->Cell(0, 5, 'PRODUTO/SERVIÇO', 0, 1);
            
            $this->tcpdf->SetFont('helvetica', '', 9);
            $this->tcpdf->SetX(15);
            $this->tcpdf->MultiCell(165, 4, $dados['produto']);
            
            // Período de garantia
            $this->tcpdf->Ln(2);
            $this->tcpdf->SetFont('helvetica', 'B', 10);
            $this->tcpdf->Cell(0, 5, 'PERÍODO DE GARANTIA', 0, 1);
            
            $this->tcpdf->SetFont('helvetica', '', 9);
            $this->tcpdf->SetX(15);
            $this->tcpdf->Cell(0, 4, 'Duração: ' . ($dados['periodo'] ?? '12 meses'), 0, 1);
            
            if (isset($dados['data_inicio']) && isset($dados['data_fim'])) {
                $this->tcpdf->SetX(15);
                $this->tcpdf->Cell(0, 4, 'De ' . date('d/m/Y', strtotime($dados['data_inicio'])) . ' a ' . date('d/m/Y', strtotime($dados['data_fim'])), 0, 1);
            }
            
            // Cobertura
            if (isset($dados['cobertura'])) {
                $this->tcpdf->Ln(2);
                $this->tcpdf->SetFont('helvetica', 'B', 10);
                $this->tcpdf->Cell(0, 5, 'O QUE ESTÁ COBERTO', 0, 1);
                
                $this->tcpdf->SetFont('helvetica', '', 9);
                $this->tcpdf->SetX(15);
                $this->tcpdf->MultiCell(165, 4, $dados['cobertura']);
            }
            
            // Exclusões
            if (isset($dados['exclusoes'])) {
                $this->tcpdf->Ln(2);
                $this->tcpdf->SetFont('helvetica', 'B', 10);
                $this->tcpdf->Cell(0, 5, 'O QUE NÃO ESTÁ COBERTO', 0, 1);
                
                $this->tcpdf->SetFont('helvetica', '', 9);
                $this->tcpdf->SetX(15);
                $this->tcpdf->MultiCell(165, 4, $dados['exclusoes']);
            }
            
            // Condições
            if (isset($dados['condicoes'])) {
                $this->tcpdf->Ln(2);
                $this->tcpdf->SetFont('helvetica', 'B', 10);
                $this->tcpdf->Cell(0, 5, 'TERMOS E CONDIÇÕES', 0, 1);
                
                $this->tcpdf->SetFont('helvetica', '', 9);
                $this->tcpdf->SetX(15);
                $this->tcpdf->MultiCell(165, 4, $dados['condicoes']);
            }
            
            // Rodapé
            $this->adicionarRodape();
            
            // Output
            $filename = 'garantia_' . ($dados['numero'] ?? date('YmdHis')) . '.pdf';
            
            if ($download) {
                $this->tcpdf->Output($filename, 'D');
            } else {
                $this->tcpdf->Output($filename, 'I');
            }
            
        } catch (Exception $e) {
            $this->logError("Erro ao gerar garantia: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Gera documento de Recibo
     * 
     * Função: Cria PDF com comprovante de pagamento/recebimento
     * Entrada: Array com dados do recibo:
     *   - numero: Número do recibo
     *   - data: Data do pagamento
     *   - cliente: Dados do cliente
     *   - descricao: Descrição do pagamento
     *   - valor: Valor pago
     *   - forma_pagamento: Forma de pagamento (dinheiro, cartão, etc)
     *   - referencia: Referência da compra/serviço
     * Processamento: Formata dados em layout simples de recibo
     * Saída: PDF com recibo formatado
     * 
     * @param array $dados Dados do recibo
     * @param bool $download Se true, força download
     * @return void
     * 
     * Uso: $pdf = new PDF(); $pdf->generateRecibo($dados);
     */
    public function generateRecibo($dados, $download = false) {
        try {
            // Validação
            if (!isset($dados['cliente']) || !isset($dados['valor'])) {
                throw new Exception("Dados incompletos para gerar recibo");
            }
            
            // Inicializa PDF
            $this->initPDF();
            
            // Cabeçalho
            $this->adicionarCabecalho();
            
            // Título
            $this->tcpdf->SetFont('helvetica', 'B', 14);
            $this->tcpdf->Cell(0, 8, 'RECIBO', 0, 1, 'C');
            
            // Número e data
            $this->tcpdf->SetFont('helvetica', '', 10);
            $this->tcpdf->Ln(2);
            $this->tcpdf->SetX(15);
            $this->tcpdf->Cell(95, 5, 'Nº: ' . ($dados['numero'] ?? 'S/N'), 0, 0);
            $this->tcpdf->Cell(95, 5, 'Data: ' . date('d/m/Y H:i', strtotime($dados['data'] ?? date('Y-m-d H:i:s'))), 0, 1, 'R');
            
            $this->tcpdf->Ln(3);
            
            // Dados do cliente
            $this->tcpdf->SetFont('helvetica', 'B', 10);
            $this->tcpdf->Cell(0, 5, 'RECEBIDO DE:', 0, 1);
            
            $this->tcpdf->SetFont('helvetica', '', 10);
            $this->tcpdf->SetX(15);
            $this->tcpdf->Cell(0, 5, $dados['cliente']['nome'] ?? '', 0, 1);
            
            if (isset($dados['cliente']['cpf_cnpj'])) {
                $this->tcpdf->SetX(15);
                $this->tcpdf->Cell(0, 5, 'CPF/CNPJ: ' . $dados['cliente']['cpf_cnpj'], 0, 1);
            }
            
            $this->tcpdf->Ln(3);
            
            // Descrição
            $this->tcpdf->SetFont('helvetica', 'B', 10);
            $this->tcpdf->Cell(0, 5, 'DESCRIÇÃO DO PAGAMENTO:', 0, 1);
            
            $this->tcpdf->SetFont('helvetica', '', 10);
            $this->tcpdf->SetX(15);
            $this->tcpdf->MultiCell(165, 5, $dados['descricao'] ?? 'Pagamento de serviços prestados');
            
            // Referência
            if (isset($dados['referencia'])) {
                $this->tcpdf->SetFont('helvetica', '', 9);
                $this->tcpdf->SetX(15);
                $this->tcpdf->Cell(0, 4, 'Referência: ' . $dados['referencia'], 0, 1);
            }
            
            $this->tcpdf->Ln(4);
            
            // Valor
            $this->tcpdf->SetFont('helvetica', 'B', 12);
            $this->tcpdf->SetX(15);
            $this->tcpdf->Cell(165, 8, 'VALOR: R$ ' . number_format($dados['valor'], 2, ',', '.'), 0, 1, 'R');
            
            // Forma de pagamento
            if (isset($dados['forma_pagamento'])) {
                $this->tcpdf->Ln(2);
                $this->tcpdf->SetFont('helvetica', '', 10);
                $this->tcpdf->SetX(15);
                $this->tcpdf->Cell(0, 5, 'Forma de Pagamento: ' . $dados['forma_pagamento'], 0, 1);
            }
            
            // Observações
            if (isset($dados['observacoes'])) {
                $this->tcpdf->Ln(3);
                $this->tcpdf->SetFont('helvetica', '', 9);
                $this->tcpdf->SetX(15);
                $this->tcpdf->MultiCell(165, 4, $dados['observacoes']);
            }
            
            // Espaço para assinatura
            $this->tcpdf->Ln(8);
            $this->tcpdf->SetX(15);
            $this->tcpdf->Cell(80, 0, '', 'T', 1);
            $this->tcpdf->SetX(15);
            $this->tcpdf->SetFont('helvetica', '', 9);
            $this->tcpdf->Cell(80, 4, 'Assinatura / Carimbo', 0, 1, 'C');
            
            // Rodapé
            $this->adicionarRodape();
            
            // Output
            $filename = 'recibo_' . ($dados['numero'] ?? date('YmdHis')) . '.pdf';
            
            if ($download) {
                $this->tcpdf->Output($filename, 'D');
            } else {
                $this->tcpdf->Output($filename, 'I');
            }
            
        } catch (Exception $e) {
            $this->logError("Erro ao gerar recibo: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Adiciona tabela com produtos ao PDF
     * 
     * Função: Desenha tabela com itens do orçamento
     * Entrada: Array de produtos com (descricao, quantidade, preco, total)
     * Processamento: Calcula e formata dados em layout de tabela
     * Saída: Tabela formatada no PDF
     */
    private function adicionarTabelaProdutos($produtos) {
        $this->tcpdf->SetFont('helvetica', 'B', 9);
        
        // Cabeçalho da tabela
        $this->tcpdf->SetFillColor(220, 220, 220);
        $this->tcpdf->SetX(15);
        $this->tcpdf->Cell(85, 6, 'DESCRIÇÃO', 1, 0, 'L', true);
        $this->tcpdf->Cell(25, 6, 'QTDE', 1, 0, 'C', true);
        $this->tcpdf->Cell(30, 6, 'VALOR UNI.', 1, 0, 'R', true);
        $this->tcpdf->Cell(30, 6, 'TOTAL', 1, 1, 'R', true);
        
        // Itens
        $this->tcpdf->SetFont('helvetica', '', 9);
        $this->tcpdf->SetFillColor(255, 255, 255);
        
        foreach ($produtos as $produto) {
            $this->tcpdf->SetX(15);
            $this->tcpdf->Cell(85, 6, substr($produto['descricao'] ?? '', 0, 50), 1, 0, 'L');
            $this->tcpdf->Cell(25, 6, $produto['quantidade'] ?? 1, 1, 0, 'C');
            $this->tcpdf->Cell(30, 6, 'R$ ' . number_format($produto['preco'] ?? 0, 2, ',', '.'), 1, 0, 'R');
            $this->tcpdf->Cell(30, 6, 'R$ ' . number_format($produto['total'] ?? 0, 2, ',', '.'), 1, 1, 'R');
        }
        
        $this->tcpdf->Ln(2);
    }
    
    /**
     * Adiciona resumo financeiro ao PDF
     * 
     * Função: Calcula e exibe subtotal, descontos e total
     * Entrada: Array com dados de valores
     * Processamento: Soma produtos, aplica descontos, calcula total
     * Saída: Resumo financeiro formatado
     */
    private function adicionarResumoFinanceiro($dados) {
        // Calcula subtotal
        $subtotal = 0;
        if (isset($dados['produtos'])) {
            foreach ($dados['produtos'] as $produto) {
                $subtotal += $produto['total'] ?? 0;
            }
        }
        
        // Aplicar desconto
        $desconto = 0;
        if (isset($dados['desconto'])) {
            if (isset($dados['tipo_desconto']) && $dados['tipo_desconto'] === 'percentual') {
                $desconto = ($subtotal * $dados['desconto']) / 100;
            } else {
                $desconto = $dados['desconto'];
            }
        }
        
        // Total
        $total = $subtotal - $desconto;
        
        $this->tcpdf->SetFont('helvetica', '', 10);
        $this->tcpdf->SetX(95);
        $this->tcpdf->Cell(60, 5, 'Subtotal:', 0, 0, 'R');
        $this->tcpdf->Cell(30, 5, 'R$ ' . number_format($subtotal, 2, ',', '.'), 0, 1, 'R');
        
        if ($desconto > 0) {
            $this->tcpdf->SetX(95);
            $this->tcpdf->Cell(60, 5, 'Desconto:', 0, 0, 'R');
            $this->tcpdf->Cell(30, 5, '- R$ ' . number_format($desconto, 2, ',', '.'), 0, 1, 'R');
        }
        
        $this->tcpdf->SetFont('helvetica', 'B', 11);
        $this->tcpdf->SetX(95);
        $this->tcpdf->SetFillColor(220, 220, 220);
        $this->tcpdf->Cell(60, 6, 'TOTAL:', 0, 0, 'R', true);
        $this->tcpdf->Cell(30, 6, 'R$ ' . number_format($total, 2, ',', '.'), 0, 1, 'R', true);
    }
    
    /**
     * Adiciona rodapé padrão ao PDF
     * 
     * Função: Insere informações de contato no final do documento
     * Entrada: Nenhuma
     * Processamento: Formata rodapé com contatos e website
     * Saída: Rodapé no final da página
     */
    private function adicionarRodape() {
        $this->tcpdf->SetFont('helvetica', '', 8);
        $this->tcpdf->SetDrawColor(200, 200, 200);
        $this->tcpdf->Line(15, $this->tcpdf->GetY() + 3, 195, $this->tcpdf->GetY() + 3);
        
        $this->tcpdf->SetY(-20);
        $this->tcpdf->SetTextColor(100, 100, 100);
        $this->tcpdf->Cell(0, 4, 'Tel: ' . $this->empresa['telefone'] . ' | Email: ' . $this->empresa['email'] . ' | ' . $this->empresa['website'], 0, 1, 'C');
        $this->tcpdf->Cell(0, 4, 'Documento gerado em: ' . date('d/m/Y H:i:s'), 0, 0, 'C');
    }
    
    /**
     * Registra erros em arquivo de log
     * 
     * @param string $message Mensagem de erro
     */
    private function logError($message) {
        $logFile = __DIR__ . '/../logs/pdf_' . date('Y-m-d') . '.log';
        
        // Cria diretório se não existir
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}\n";
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
}
?>
