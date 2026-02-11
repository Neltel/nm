<?php
/**
 * Constantes da Aplicação
 * 
 * Função: Define constantes utilizadas em toda a aplicação
 * Uso: require_once __DIR__ . '/config/constants.php';
 */

// Diretórios
define('ROOT_PATH', dirname(__DIR__));
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('UPLOADS_PATH', PUBLIC_PATH . '/uploads');
define('LOGS_PATH', PUBLIC_PATH . '/logs');

// URLs
define('BASE_URL', APP_URL ?? 'http://localhost');
define('API_URL', BASE_URL . '/api');

// Status de Pedidos/Orçamentos
define('STATUS_PENDENTE', 'pendente');
define('STATUS_APROVADO', 'aprovado');
define('STATUS_EM_ANDAMENTO', 'em_andamento');
define('STATUS_CONCLUIDO', 'concluido');
define('STATUS_CANCELADO', 'cancelado');

// Status de Pagamentos
define('PAGAMENTO_PENDENTE', 'pendente');
define('PAGAMENTO_PAGO', 'pago');
define('PAGAMENTO_VENCIDO', 'vencido');
define('PAGAMENTO_CANCELADO', 'cancelado');

// Tipos de Usuário
define('USUARIO_ADMIN', 'admin');
define('USUARIO_TECNICO', 'tecnico');
define('USUARIO_CLIENTE', 'cliente');

// Formatos de Data
define('DATE_FORMAT', 'd/m/Y');
define('DATETIME_FORMAT', 'd/m/Y H:i');
define('TIME_FORMAT', 'H:i');

// Paginação
define('ITEMS_PER_PAGE', 20);

// Upload
define('MAX_FILE_SIZE', MAX_UPLOAD_SIZE ?? 10485760); // 10MB
define('ALLOWED_FILE_TYPES', explode(',', ALLOWED_EXTENSIONS ?? 'jpg,jpeg,png,pdf,doc,docx,xls,xlsx'));

// Mensagens
define('MSG_SUCCESS', 'success');
define('MSG_ERROR', 'error');
define('MSG_WARNING', 'warning');
define('MSG_INFO', 'info');
?>
