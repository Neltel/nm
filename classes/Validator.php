<?php
/**
 * Classe Validator
 * 
 * Função: Valida diferentes tipos de dados (email, CPF, CNPJ, etc)
 * Entrada: Dados para validar
 * Processamento: Aplica regras de validação
 * Saída: true se válido, false se inválido
 * Uso: Validator::validateEmail($email);
 */

class Validator {
    
    /**
     * Valida email
     * 
     * @param string $email Email para validar
     * @return bool
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Valida CPF
     * 
     * @param string $cpf CPF para validar
     * @return bool
     */
    public static function validateCPF($cpf) {
        // Remove caracteres não numéricos
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        
        // Verifica se tem 11 dígitos
        if (strlen($cpf) != 11) {
            return false;
        }
        
        // Verifica se todos os dígitos são iguais
        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }
        
        // Valida primeiro dígito verificador
        $soma = 0;
        for ($i = 0; $i < 9; $i++) {
            $soma += $cpf[$i] * (10 - $i);
        }
        $resto = $soma % 11;
        $digito1 = ($resto < 2) ? 0 : 11 - $resto;
        
        if ($cpf[9] != $digito1) {
            return false;
        }
        
        // Valida segundo dígito verificador
        $soma = 0;
        for ($i = 0; $i < 10; $i++) {
            $soma += $cpf[$i] * (11 - $i);
        }
        $resto = $soma % 11;
        $digito2 = ($resto < 2) ? 0 : 11 - $resto;
        
        return $cpf[10] == $digito2;
    }
    
    /**
     * Valida CNPJ
     * 
     * @param string $cnpj CNPJ para validar
     * @return bool
     */
    public static function validateCNPJ($cnpj) {
        // Remove caracteres não numéricos
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
        
        // Verifica se tem 14 dígitos
        if (strlen($cnpj) != 14) {
            return false;
        }
        
        // Verifica se todos os dígitos são iguais
        if (preg_match('/(\d)\1{13}/', $cnpj)) {
            return false;
        }
        
        // Valida primeiro dígito verificador
        $soma = 0;
        $multiplicador = [5,4,3,2,9,8,7,6,5,4,3,2];
        for ($i = 0; $i < 12; $i++) {
            $soma += $cnpj[$i] * $multiplicador[$i];
        }
        $resto = $soma % 11;
        $digito1 = ($resto < 2) ? 0 : 11 - $resto;
        
        if ($cnpj[12] != $digito1) {
            return false;
        }
        
        // Valida segundo dígito verificador
        $soma = 0;
        $multiplicador = [6,5,4,3,2,9,8,7,6,5,4,3,2];
        for ($i = 0; $i < 13; $i++) {
            $soma += $cnpj[$i] * $multiplicador[$i];
        }
        $resto = $soma % 11;
        $digito2 = ($resto < 2) ? 0 : 11 - $resto;
        
        return $cnpj[13] == $digito2;
    }
    
    /**
     * Valida data no formato brasileiro (dd/mm/yyyy)
     * 
     * @param string $date Data para validar
     * @return bool
     */
    public static function validateDate($date) {
        $parts = explode('/', $date);
        if (count($parts) != 3) {
            return false;
        }
        
        list($day, $month, $year) = $parts;
        return checkdate($month, $day, $year);
    }
    
    /**
     * Valida telefone brasileiro
     * 
     * @param string $phone Telefone para validar
     * @return bool
     */
    public static function validatePhone($phone) {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        // Aceita (XX) 9XXXX-XXXX ou (XX) XXXX-XXXX
        return strlen($phone) >= 10 && strlen($phone) <= 11;
    }
    
    /**
     * Valida CEP
     * 
     * @param string $cep CEP para validar
     * @return bool
     */
    public static function validateCEP($cep) {
        $cep = preg_replace('/[^0-9]/', '', $cep);
        return strlen($cep) === 8;
    }
    
    /**
     * Valida campo obrigatório
     * 
     * @param mixed $value Valor para validar
     * @return bool
     */
    public static function required($value) {
        if (is_string($value)) {
            return trim($value) !== '';
        }
        return !empty($value);
    }
    
    /**
     * Valida tamanho mínimo
     * 
     * @param string $value Valor para validar
     * @param int $min Tamanho mínimo
     * @return bool
     */
    public static function minLength($value, $min) {
        return strlen($value) >= $min;
    }
    
    /**
     * Valida tamanho máximo
     * 
     * @param string $value Valor para validar
     * @param int $max Tamanho máximo
     * @return bool
     */
    public static function maxLength($value, $max) {
        return strlen($value) <= $max;
    }
    
    /**
     * Valida valor numérico
     * 
     * @param mixed $value Valor para validar
     * @return bool
     */
    public static function isNumeric($value) {
        return is_numeric($value);
    }
    
    /**
     * Valida valor dentro de um intervalo
     * 
     * @param mixed $value Valor para validar
     * @param int|float $min Valor mínimo
     * @param int|float $max Valor máximo
     * @return bool
     */
    public static function between($value, $min, $max) {
        return $value >= $min && $value <= $max;
    }
    
    /**
     * Valida se arquivo é de tipo permitido
     * 
     * @param string $filename Nome do arquivo
     * @param array $allowedTypes Tipos permitidos
     * @return bool
     */
    public static function validateFileType($filename, $allowedTypes = null) {
        if ($allowedTypes === null) {
            $allowedTypes = ALLOWED_FILE_TYPES;
        }
        
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($extension, $allowedTypes);
    }
    
    /**
     * Valida tamanho de arquivo
     * 
     * @param int $fileSize Tamanho do arquivo em bytes
     * @param int $maxSize Tamanho máximo permitido
     * @return bool
     */
    public static function validateFileSize($fileSize, $maxSize = null) {
        if ($maxSize === null) {
            $maxSize = MAX_FILE_SIZE;
        }
        
        return $fileSize <= $maxSize;
    }
    
    /**
     * Sanitiza string removendo tags HTML
     * 
     * @param string $value Valor para sanitizar
     * @return string
     */
    public static function sanitizeString($value) {
        return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Formata CPF
     * 
     * @param string $cpf CPF para formatar
     * @return string
     */
    public static function formatCPF($cpf) {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
    }
    
    /**
     * Formata CNPJ
     * 
     * @param string $cnpj CNPJ para formatar
     * @return string
     */
    public static function formatCNPJ($cnpj) {
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
        return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $cnpj);
    }
    
    /**
     * Formata telefone
     * 
     * @param string $phone Telefone para formatar
     * @return string
     */
    public static function formatPhone($phone) {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($phone) == 11) {
            return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $phone);
        } else if (strlen($phone) == 10) {
            return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $phone);
        }
        return $phone;
    }
    
    /**
     * Formata CEP
     * 
     * @param string $cep CEP para formatar
     * @return string
     */
    public static function formatCEP($cep) {
        $cep = preg_replace('/[^0-9]/', '', $cep);
        return preg_replace('/(\d{5})(\d{3})/', '$1-$2', $cep);
    }
}
?>
