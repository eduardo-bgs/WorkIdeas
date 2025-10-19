<?php
/**
 * ========================================
 * ARQUIVO: config.php
 * DESCRIÇÃO: Configurações centralizadas do sistema
 * ========================================
 *      Configurações essenciais:
 * - Conexão com banco de dados MySQL
 * - Carregamento de variáveis de ambiente (.env)
 * - Funções de segurança e autenticação
 * - Configurações gerais do PHP
 * ========================================
 */

// ====================================
// CARREGAR VARIÁVEIS DE AMBIENTE
// ====================================
/**
 * Carrega as configurações sensíveis do arquivo .env
 * Para manter dados como chaves de API fora do código
 */
function carregarEnv() {
    $envFile = __DIR__ . '/.env';
    
    // Verifica se o arquivo .env existe
    if (!file_exists($envFile)) {
        die("⚠️ ERRO: Arquivo .env não encontrado! Crie o arquivo baseado no .env.example");
    }
    
    // Lê cada linha do arquivo .env
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        // Ignora comentários e linhas vazias
        if (strpos(trim($line), '#') === 0 || empty(trim($line))) {
            continue;
        }
        
        // Separa chave=valor
        list($key, $value) = explode('=', $line, 2);
        
        $key = trim($key);
        $value = trim($value);
        
        // Remove aspas se existirem
        $value = trim($value, '"\'');
        
        // Define como constante PHP
        if (!defined($key)) {
            define($key, $value);
        }
    }
}

// Carrega as variáveis de ambiente
carregarEnv();

// ====================================
// FUNÇÕES DO SISTEMA
// ====================================

/**
 * FUNÇÃO: conectarBanco()
 * Estabelece conexão segura com MySQL
 * RETORNO: Objeto mysqli conectado
 */
function conectarBanco() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Verifica se houve erro na conexão
    if ($conn->connect_error) {
        die("⚠️ Erro na conexão com o banco de dados: " . $conn->connect_error);
    }
    
    // Define charset UTF-8 para suportar acentuação
    $conn->set_charset("utf8mb4");
    
    return $conn;
}

/**
 * FUNÇÃO: iniciarSessao()
 * DESCRIÇÃO: Inicia sessão PHP com configurações de segurança
 * - Previne sequestro de sessão
 * - Valida IP e User Agent
 * - Configura cookies seguros
 */
function iniciarSessao() {
    if (session_status() === PHP_SESSION_NONE) {
        // Configurações de segurança da sessão
        ini_set('session.cookie_httponly', 1);  // Previne acesso via JavaScript
        ini_set('session.use_only_cookies', 1);  // Usa apenas cookies
        ini_set('session.cookie_secure', 0);     // Mudar para 1 se usar HTTPS
        
        session_start();
        
        // Primeira inicialização da sessão
        if (!isset($_SESSION['iniciada'])) {
            session_regenerate_id(true);  // Gera novo ID para segurança
            $_SESSION['iniciada'] = true;
            $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        }
        
        // Validação de segurança: verifica se o IP mudou
        if (isset($_SESSION['ip']) && $_SESSION['ip'] !== $_SERVER['REMOTE_ADDR']) {
            session_destroy();
            header('Location: index.php');
            exit();
        }
    }
}

/**
 * FUNÇÃO: verificarLogin()
 * DESCRIÇÃO: Verifica se usuário está autenticado
 * - Redireciona para login se não estiver logado
 * - Implementa timeout de inatividade (30 minutos)
 */
function verificarLogin() {
    iniciarSessao();
    
    // Verifica se está logado
    if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
        header('Location: index.php');
        exit();
    }
    
    // Verifica tempo de inatividade (30 minutos)
    if (isset($_SESSION['ultimo_acesso'])) {
        $inativo = time() - $_SESSION['ultimo_acesso'];
        if ($inativo > 1800) {
            session_destroy();
            header('Location: index.php?timeout=1');
            exit();
        }
    }
    
    // Atualiza timestamp do último acesso
    $_SESSION['ultimo_acesso'] = time();
}

/**
 * FUNÇÃO: logout()
 * DESCRIÇÃO: Encerra sessão do usuário com segurança
 * - Limpa todas as variáveis de sessão
 * - Remove cookies
 * - Destrói a sessão
 */
function logout() {
    iniciarSessao();
    
    // Limpa todas as variáveis de sessão
    $_SESSION = array();
    
    // Remove o cookie de sessão
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Destrói a sessão completamente
    session_destroy();
    
    header('Location: index.php?logout=sucesso');
    exit();
}

/**
 * FUNÇÃO: sanitizar()
 * DESCRIÇÃO: Limpa dados de entrada contra XSS
 * PARÂMETRO: $data - dado a ser sanitizado
 * RETORNO: String limpa e segura
 */
function sanitizar($data) {
    $data = trim($data);                                    // Remove espaços
    $data = stripslashes($data);                            // Remove barras
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');  // Converte caracteres especiais
    return $data;
}

/**
 * FUNÇÃO: validarEmail()
 * DESCRIÇÃO: Valida formato de email
 * RETORNO: true se válido, false se inválido
 */
function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * FUNÇÃO: gerarHashSenha()
 * DESCRIÇÃO: Cria hash seguro da senha usando bcrypt
 * - Usa algoritmo PASSWORD_DEFAULT (bcrypt)
 * - Automaticamente adiciona (salt) aleatório
 */
function gerarHashSenha($senha) {
    return password_hash($senha, PASSWORD_DEFAULT);
}

/**
 * FUNÇÃO: verificarSenha()
 * DESCRIÇÃO: Verifica se senha corresponde ao hash
 */
function verificarSenha($senha, $hash) {
    return password_verify($senha, $hash);
}

// ====================================
// CONFIGURAÇÕES GERAIS DO PHP
// ====================================

// Define timezone para horário de Brasília
date_default_timezone_set('America/Sao_Paulo');

// Configurações de erro (ajustar!! para produção)
error_reporting(E_ALL);
ini_set('display_errors', 0);  // Muda para 0 em produção
ini_set('log_errors', 1);      // Salva erros em arquivo
ini_set('error_log', __DIR__ . '/error.log');

// Charset padrão UTF-8
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
?>