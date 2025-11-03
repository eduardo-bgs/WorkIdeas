<?php
/**
 * ========================================
 * ARQUIVO: processar_ia.php
 * DESCRIÇÃO: Processa requisições para API Gemini
 * ========================================
 */

require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');

// ====================================
// VALIDAÇÃO DE AUTENTICAÇÃO
// ====================================
// Garante que apenas usuários logados podem usar a IA
iniciarSessao();

if (!isset($_SESSION['usuario_logado']) || !$_SESSION['usuario_logado']) {
    echo json_encode([
        'sucesso' => false,
        'erro' => 'Sessão expirada. Faça login novamente.'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// ====================================
// VALIDAÇÃO DA REQUISIÇÃO
// ====================================
// Verifica se a pergunta foi enviada via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['pergunta'])) {
    echo json_encode([
        'sucesso' => false,
        'erro' => 'Pergunta não enviada.'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// Limpa espaços em branco da pergunta
$pergunta = trim($_POST['pergunta']);

// Validação: pergunta não pode estar vazia
if (empty($pergunta)) {
    echo json_encode([
        'sucesso' => false,
        'erro' => 'Pergunta vazia. Digite algo para continuar.'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// Validação: limita tamanho da pergunta (1000 caracteres)
if (strlen($pergunta) > 1000) {
    echo json_encode([
        'sucesso' => false,
        'erro' => 'Pergunta muito longa. Limite: 1000 caracteres.'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// ====================================
// PREPARAÇÃO DO PROMPT PARA GEMINI
// ====================================
/**
 * Define o contexto e comportamento esperado da IA
 * O prompt sistema orienta a IA a ser um especialista em projetos acadêmicos
 */
$prompt_sistema = "Você é um assistente especializado em sugestões de projetos acadêmicos. " .
                  "Sua função é ajudar estudantes universitários a desenvolver ideias criativas e viáveis " .
                  "para trabalhos de conclusão de curso (TCC), artigos científicos, projetos de pesquisa e trabalhos acadêmicos.\n\n" .
                  "IMPORTANTE:\n" .
                  "- Forneça sugestões práticas e inovadoras\n" .
                  "- Inclua sempre: título do projeto, objetivos principais, metodologia sugerida e resultados esperados\n" .
                  "- Adapte suas sugestões ao nível universitário\n" .
                  "- Use linguagem clara e profissional\n" .
                  "- Sugira de 2 a 3 ideias quando apropriado\n\n";

// Combina o prompt sistema com a pergunta do usuário
$prompt_completo = $prompt_sistema . "Pergunta do aluno: " . $pergunta;

// ====================================
// CONFIGURAÇÃO DOS DADOS DA API
// ====================================
/**
 * Estrutura JSON esperada pela API Gemini
 * 
 * contents: array com o conteúdo da conversa
 * generationConfig: parâmetros de geração da resposta
 * safetySettings: configurações de segurança/filtros
 */
$dados_api = [
    'contents' => [
        [
            'parts' => [
                [
                    'text' => $prompt_completo
                ]
            ]
        ]
    ],
    // Configurações de geração da resposta
    'generationConfig' => [
        'temperature' => 0.7,           // Criatividade (0.0=conservador, 1.0=criativo)
        'topK' => 40,                   // Diversidade de tokens considerados
        'topP' => 0.95,                 // Probabilidade acumulada de tokens
        'maxOutputTokens' => 2000,      // Tamanho máximo da resposta
        'stopSequences' => []           // Sequências que param a geração
    ],
    // Configurações de segurança (filtros de conteúdo)
    'safetySettings' => [
        [
            'category' => 'HARM_CATEGORY_HARASSMENT',
            'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
        ],
        [
            'category' => 'HARM_CATEGORY_HATE_SPEECH',
            'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
        ],
        [
            'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
            'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
        ],
        [
            'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
            'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
        ]
    ]
];

// ====================================
// REQUISIÇÃO HTTP COM cURL
// ====================================
/**
 * cURL é uma biblioteca PHP para fazer requisições HTTP ***
 * Usamos ela para se comunicar com a API do Google Gemini **
 */

// Pega a chave da API do arquivo .env (variável de ambiente)
$api_key = GEMINI_API_KEY;

// Monta a URL completa da API com a chave
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$api_key}";
// Inicializa sessão cURL
$curl = curl_init();

// Define todas as configurações do cURL
curl_setopt_array($curl, [
    CURLOPT_URL => $url,                        // URL da API
    CURLOPT_RETURNTRANSFER => true,             // Retorna resposta como string
    CURLOPT_ENCODING => '',                     // Aceita qualquer encoding
    CURLOPT_MAXREDIRS => 10,                    // Máximo de
CURLOPT_TIMEOUT => 30,                      // Timeout de 30 segundos
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',            // Método HTTP POST
    CURLOPT_POSTFIELDS => json_encode($dados_api),  // Dados em JSON
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json'        // Define tipo do conteúdo
    ],
]);

// Executa a requisição
$resposta_api = curl_exec($curl);

// Captura possíveis erros
$erro_curl = curl_error($curl);

// Pega o código HTTP da resposta (200=sucesso, 400=erro, etc)
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

// Fecha a conexão cURL
curl_close($curl);

// ====================================
// PROCESSAMENTO DA RESPOSTA
// ====================================

// Verifica se houve erro na requisição cURL
if ($erro_curl) {
    echo json_encode([
        'sucesso' => false,
        'erro' => 'Erro na comunicação com a API Gemini: ' . $erro_curl
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// Converte resposta JSON em array PHP
$resposta_decodificada = json_decode($resposta_api, true);

// ====================================
// TRATAMENTO DE ERROS DA API
// ====================================
// Verifica se a API retornou erro (código diferente de 200)
if ($http_code !== 200) {
    $mensagem_erro = 'Erro desconhecido';
    
    // Tenta extrair mensagem de erro da resposta
    if (isset($resposta_decodificada['error']['message'])) {
        $mensagem_erro = $resposta_decodificada['error']['message'];
    }
    
    // Traduz erros comuns para mensagens amigáveis
    if (strpos($mensagem_erro, 'API key') !== false) {
        $mensagem_erro = 'Chave da API Gemini inválida. Verifique o arquivo .env';
    } elseif ($http_code === 429) {
        $mensagem_erro = 'Limite de requisições excedido. Tente novamente em alguns minutos.';
    } elseif ($http_code === 403) {
        $mensagem_erro = 'Acesso negado. Verifique se a API Key está ativa no Google Cloud.';
    }
    
    echo json_encode([
        'sucesso' => false,
        'erro' => $mensagem_erro
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// ====================================
// EXTRAÇÃO DO TEXTO DA RESPOSTA
// ====================================
/**
 * A resposta da API vem em formato JSON aninhado
 * Precisamos navegar pela estrutura para pegar o texto gerado
 */
if (!isset($resposta_decodificada['candidates'][0]['content']['parts'][0]['text'])) {
    echo json_encode([
        'sucesso' => false,
        'erro' => 'Resposta da IA em formato inesperado. Tente novamente.'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// Extrai o texto gerado pela IA
$resposta_ia = $resposta_decodificada['candidates'][0]['content']['parts'][0]['text'];

// Remove espaços desnecessários
$resposta_ia = trim($resposta_ia);

// ====================================
// SALVAMENTO NO BANCO DE DADOS
// ====================================
/**
 * Registra a interação no MySQL para:
 * - Histórico do usuário
 * - Análise de uso
 * - Backup das conversas
 */
try {
    $conn = conectarBanco();
    
    // Prepara query SQL com prepared statement (segurança contra SQL injection)
    $stmt = $conn->prepare(
        "INSERT INTO historico_ia (usuario_id, pergunta, resposta) VALUES (?, ?, ?)"
    );
    
    // Pega ID do usuário da sessão
    $usuario_id = $_SESSION['usuario_id'];
    
    // Vincula parâmetros (i=integer, s=string)
    $stmt->bind_param("iss", $usuario_id, $pergunta, $resposta_ia);
    
    // Executa a inserção
    if (!$stmt->execute()) {
        // Log de erro (não exibe para usuário)
        error_log("Erro ao salvar no banco: " . $stmt->error);
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    // Log de exceções (não exibe para usuário)
    error_log("Exceção ao salvar no banco: " . $e->getMessage());
}

// ====================================
// RETORNO DE SUCESSO
// ====================================
/**
 * Envia resposta JSON de sucesso para o JavaScript
 * O frontend vai receber e exibir a resposta na tela
 */
echo json_encode([
    'sucesso' => true,
    'resposta' => $resposta_ia,
    'timestamp' => date('Y-m-d H:i:s')
], JSON_UNESCAPED_UNICODE);


/*
