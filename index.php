<?php
/**
 * ========================================
 * ARQUIVO: index.php
 * DESCRIÇÃO: Página de login e cadastro
 * ========================================
 * Funcionalidades:
 * - Login de usuários existentes
 * - Cadastro de novos usuários
 * - Validação de dados
 * - Redirecionamento após login
 * ========================================
 */

require_once 'config.php';
iniciarSessao();

// ====================================
// REDIRECIONAMENTO SE JÁ ESTIVER LOGADO
// ====================================
// Se o usuário já está autenticado, vai direto pro dashboard
if (isset($_SESSION['usuario_logado']) && $_SESSION['usuario_logado'] === true) {
    header('Location: dashboard.php');
    exit();
}

// Variáveis para mensagens
$erro = '';
$sucesso = '';

// ====================================
// PROCESSAR LOGIN
// ====================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    // Sanitiza e valida o email
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $senha = $_POST['senha'];
    
    if (!empty($email) && !empty($senha)) {
        $conn = conectarBanco();
        
        // Busca usuário no banco
        $stmt = $conn->prepare("SELECT id, nome, senha FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        if ($resultado->num_rows > 0) {
            $usuario = $resultado->fetch_assoc();
            
            // Verifica se a senha está correta
            if (password_verify($senha, $usuario['senha'])) {
                // Login bem-sucedido! Cria sessão
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nome'] = $usuario['nome'];
                $_SESSION['usuario_logado'] = true;
                
                header('Location: dashboard.php');
                exit();
            } else {
                $erro = 'Senha incorreta!';
            }
        } else {
            $erro = 'Usuário não encontrado. Faça seu cadastro!';
        }
        
        $stmt->close();
        $conn->close();
    } else {
        $erro = 'Preencha todos os campos!';
    }
}

// ====================================
// PROCESSAR CADASTRO
// ====================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastrar'])) {
    // Sanitiza dados de entrada
    $nome = htmlspecialchars(trim($_POST['nome']));
    $email = filter_var($_POST['email_cadastro'], FILTER_SANITIZE_EMAIL);
    $senha = $_POST['senha_cadastro'];
    $confirmar_senha = $_POST['confirmar_senha'];
    
    if (!empty($nome) && !empty($email) && !empty($senha)) {
        // Verifica se as senhas coincidem
        if ($senha === $confirmar_senha) {
            // Valida formato do email
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $conn = conectarBanco();
                
                // Verifica se o email já está cadastrado
                $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $resultado = $stmt->get_result();
                
                if ($resultado->num_rows === 0) {
                    // Email disponível! Cria novo usuário
                    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)");
                    $stmt->bind_param("sss", $nome, $email, $senha_hash);
                    
                    if ($stmt->execute()) {
                        $sucesso = 'Cadastro realizado com sucesso! Faça login.';
                    } else {
                        $erro = 'Erro ao cadastrar. Tente novamente.';
                    }
                } else {
                    $erro = 'Este email já está cadastrado!';
                }
                
                $stmt->close();
                $conn->close();
            } else {
                $erro = 'Email inválido!';
            }
        } else {
            $erro = 'As senhas não coincidem!';
        }
    } else {
        $erro = 'Preencha todos os campos!';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Work-Ideas</title>
    <style>
        /* ========================================
           ESTILOS GLOBAIS
           ======================================== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        /* ========================================
           CONTAINER PRINCIPAL
           ======================================== */
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 450px;
            width: 100%;
            overflow: hidden;
        }
        
        /* ========================================
           CABEÇALHO (COM LOGO)
           ======================================== */
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 32px;
            margin-bottom: 5px;
            font-weight: 700;
            letter-spacing: 1px;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 14px;
            font-weight: 400;
        }
        
        /* ========================================
           ABAS DE NAVEGAÇÃO
           ======================================== */
        .tabs {
            display: flex;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .tab {
            flex: 1;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            background: white;
            border: none;
            font-size: 16px;
            font-weight: 600;
            color: #666;
            transition: all 0.3s;
        }
        
        .tab.active {
            color: #667eea;
            border-bottom: 3px solid #667eea;
        }
        
        .tab:hover {
            background: #f8f9fa;
        }
        
        /* ========================================
           ÁREA DOS FORMULÁRIOS
           ======================================== */
        .form-container {
            padding: 30px;
        }
        
        .form-section {
            display: none;
        }
        
        .form-section.active {
            display: block;
        }
        
        /* ========================================
           CAMPOS DO FORMULÁRIO
           ======================================== */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        /* ========================================
           BOTÕES
           ======================================== */
        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        /* ========================================
           ALERTAS (ERRO E SUCESSO)
           ======================================== */
        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        
        .alert-success {
            background: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }
        
        /* ========================================
           CAIXA DE INFORMAÇÕES
           ======================================== */
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin-top: 20px;
            border-radius: 5px;
            font-size: 13px;
            color: #666;
        }
    </style>
</head>
<body>
    <!-- ========================================
         CONTAINER PRINCIPAL
         ======================================== -->
    <div class="container">
        <!-- Cabeçalho com logo e descrição -->
        <div class="header">
            <h1>📚 Work-Ideas</h1>
            <p>Sua IA para Sugestão de Projetos</p>
        </div>
        
        <!-- Abas de navegação (Login/Cadastro) -->
        <div class="tabs">
            <button class="tab active" onclick="mudarAba('login')">Login</button>
            <button class="tab" onclick="mudarAba('cadastro')">Cadastro</button>
        </div>
        
        <!-- Container dos formulários -->
        <div class="form-container">
            <!-- ========================================
                 ALERTAS DE ERRO E SUCESSO
                 ======================================== -->
            <?php if ($erro): ?>
                <div class="alert alert-error"><?php echo $erro; ?></div>
            <?php endif; ?>
            
            <?php if ($sucesso): ?>
                <div class="alert alert-success"><?php echo $sucesso; ?></div>
            <?php endif; ?>
            
            <!-- ========================================
                 FORMULÁRIO DE LOGIN
                 ======================================== -->
            <div id="login-form" class="form-section active">
                <form method="POST">
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="senha">Senha:</label>
                        <input type="password" id="senha" name="senha" required>
                    </div>
                    
                    <button type="submit" name="login" class="btn">Entrar</button>
                </form>
                
                <div class="info-box">
                    <strong>Não tem cadastro?</strong> Clique na aba "Cadastro" acima para criar sua conta!
                </div>
            </div>
            
            <!-- ========================================
                 FORMULÁRIO DE CADASTRO
                 ======================================== -->
            <div id="cadastro-form" class="form-section">
                <form method="POST">
                    <div class="form-group">
                        <label for="nome">Nome Completo:</label>
                        <input type="text" id="nome" name="nome" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email_cadastro">Email:</label>
<input type="email" id="email_cadastro" name="email_cadastro" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="senha_cadastro">Senha:</label>
                        <input type="password" id="senha_cadastro" name="senha_cadastro" required minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirmar_senha">Confirmar Senha:</label>
                        <input type="password" id="confirmar_senha" name="confirmar_senha" required minlength="6">
                    </div>
                    
                    <button type="submit" name="cadastrar" class="btn">Cadastrar</button>
                </form>
                
                <div class="info-box">
                    <strong>Já tem cadastro?</strong> Clique na aba "Login" acima para entrar!
                </div>
            </div>
        </div>
    </div>
    
    <!-- ========================================
         JAVASCRIPT - TROCA DE ABAS
         ======================================== -->
    <script>
        /**
         * FUNÇÃO: mudarAba()
         * DESCRIÇÃO: Alterna entre formulários de login e cadastro
         * PARÂMETRO: aba - 'login' ou 'cadastro'
         */
        function mudarAba(aba) {
            // Remove classe active de todas as abas
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove classe active de todos os formulários
            document.querySelectorAll('.form-section').forEach(section => {
                section.classList.remove('active');
            });
            
            // Adiciona classe active na aba clicada
            event.target.classList.add('active');
            
            // Mostra formulário correspondente
            if (aba === 'login') {
                document.getElementById('login-form').classList.add('active');
            } else {
                document.getElementById('cadastro-form').classList.add('active');
            }
        }
    </script>
</body>
</html>