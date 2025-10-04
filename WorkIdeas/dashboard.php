<?php
/**
 * ========================================
 * ARQUIVO: dashboard.php
 * DESCRIÇÃO: Página principal do sistema (área logada)
 * ========================================
 * Funcionalidades:
 * - Interface de chat com IA
 * - Histórico de interações
 * - Envio de perguntas para API Gemini
 * - Exibição de respostas da IA
 * ========================================
 */

require_once 'config.php';
verificarLogin();  // Garante que usuário está autenticado

// ====================================
// PROCESSAR LOGOUT
// ====================================
if (isset($_GET['logout'])) {
    logout();
}

// ====================================
// CARREGAR HISTÓRICO DO USUÁRIO
// ====================================
// Busca últimas 20 interações do usuário no banco
$conn = conectarBanco();
$stmt = $conn->prepare("SELECT pergunta, resposta, data_interacao FROM historico_ia WHERE usuario_id = ? ORDER BY data_interacao DESC LIMIT 20");
$stmt->bind_param("i", $_SESSION['usuario_id']);
$stmt->execute();
$resultado = $stmt->get_result();

$historico = [];
while ($row = $resultado->fetch_assoc()) {
    $historico[] = $row;
}

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Work-Ideas</title>
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
            background: #343541;
            color: #fff;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* ========================================
           CABEÇALHO (HEADER)
           ======================================== */
        .header {
            background: #202123;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #444654;
        }
        
        .header-left {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        
        .header-left h1 {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: 0.5px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .header-left p {
            font-size: 12px;
            color: #8e8ea0;
            font-weight: 400;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-name {
            font-size: 14px;
            color: #ececf1;
        }
        
        /* ========================================
           BOTÕES DO HEADER
           ======================================== */
        .btn-logout {
            padding: 8px 16px;
            background: #444654;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-logout:hover {
            background: #565869;
        }
        
        .btn-historico {
            padding: 8px 16px;
            background: #10a37f;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.2s;
        }
        
        .btn-historico:hover {
            background: #0d8a6a;
        }
        
        /* ========================================
           CONTAINER PRINCIPAL
           ======================================== */
        .main-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            max-width: 900px;
            margin: 0 auto;
            width: 100%;
            padding: 20px;
            overflow-y: auto;
        }
        
        /* ========================================
           ÁREA DO CHAT
           ======================================== */
        .chat-area {
            flex: 1;
            overflow-y: auto;
            padding: 20px 0;
            display: flex;
            flex-direction: column;
        }
        
        /* ========================================
           MENSAGEM DE BOAS-VINDAS
           ======================================== */
        .welcome-message {
            text-align: center;
            padding: 60px 20px;
        }
        
        .welcome-message h2 {
            font-size: 32px;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .welcome-message p {
            font-size: 16px;
            color: #b4b4b4;
            margin-bottom: 30px;
        }
        
        /* ========================================
           CARDS DE SUGESTÕES
           ======================================== */
        .suggestions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .suggestion-card {
            background: #444654;
            padding: 15px;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
            border: 1px solid transparent;
        }
        
        .suggestion-card:hover {
            background: #565869;
            border-color: #10a37f;
            transform: translateY(-2px);
        }
        
        .suggestion-card h4 {
            font-size: 14px;
            margin-bottom: 8px;
            color: #10a37f;
        }
        
        .suggestion-card p {
            font-size: 13px;
            color: #b4b4b4;
        }
        
        /* ========================================
           MENSAGENS DO CHAT
           ======================================== */
        .message {
            margin-bottom: 20px;
            padding: 20px;
            border-radius: 10px;
            animation: fadeIn 0.5s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Mensagem do usuário */
        .message.user {
            background: #444654;
            margin-left: 20%;
        }
        
        /* Mensagem da IA */
        .message.assistant {
            background: #343541;
            border: 1px solid #444654;
        }
        
        .message-header {
            font-weight: 600;
            margin-bottom: 10px;
            color: #10a37f;
        }
        
        .message-content {
            line-height: 1.6;
            color: #ececf1;
            white-space: pre-wrap;
        }
        
        /* ========================================
           ÁREA DE INPUT
           ======================================== */
        .input-area {
            position: sticky;
            bottom: 0;
            background: #343541;
            padding: 20px 0;
        }
        
        .input-container {
            background: #40414f;
            border-radius: 12px;
            padding: 12px 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid #565869;
        }
        
        .input-container:focus-within {
            border-color: #10a37f;
        }
        
        #pergunta {
            flex: 1;
            background: transparent;
            border: none;
            color: white;
            font-size: 15px;
            outline: none;
            resize: none;
            max-height: 200px;
            font-family: inherit;
        }
        
        #pergunta::placeholder {
            color: #8e8ea0;
        }
        
        /* ========================================
           BOTÃO DE ENVIAR
           ======================================== */
        .btn-send {
            background: #10a37f;
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }
        
        .btn-send:hover {
            background: #0d8a6a;
        }
        
        .btn-send:disabled {
            background: #565869;
            cursor: not-allowed;
        }
        
        /* ========================================
           LOADING
           ======================================== */
        .loading {
            display: none;
            text-align: center;
            padding: 20px;
            color: #8e8ea0;
        }
        
        .loading.show {
            display: block;
        }
        
        .loading-dots {
            display: inline-block;
        }
        
        .loading-dots span {
            animation: blink 1.4s infinite;
            font-size: 20px;
        }
        
        .loading-dots span:nth-child(2) {
            animation-delay: 0.2s;
        }
        
        .loading-dots span:nth-child(3) {
            animation-delay: 0.4s;
        }
        
        @keyframes blink {
            0%, 80%, 100% { opacity: 0; }
            40% { opacity: 1; }
        }
        
        /* ========================================
           HISTÓRICO
           ======================================== */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.show {
            display: flex;
        }
        
        .modal-content {
            background: #2a2b32;
            border-radius: 12px;
            max-width: 800px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            padding: 30px;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .modal-header h3 {
            font-size: 24px;
        }
        
        .btn-close {
            background: #444654;
            border: none;
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 20px;
        }
        
        /* ========================================
           ITENS DO HISTÓRICO
           ======================================== */
        .historico-item {
            background: #343541;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 3px solid #10a37f;
        }
        
        .historico-item strong {
            color: #10a37f;
            display: block;
            margin-bottom: 5px;
        }
        
        .historico-item p {
            color: #b4b4b4;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .historico-item small {
            color: #8e8ea0;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <!-- ========================================
         CABEÇALHO
         ======================================== -->
    <div class="header">
        <div class="header-left">
            <h1>Work-Ideas</h1>
            <p>🤖 Assistente de Projetos Acadêmicos</p>
        </div>
        <div class="user-info">
            <button class="btn-historico" onclick="mostrarHistorico()">📜 Histórico</button>
            <span class="user-name">👤 <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></span>
            <a href="?logout=1" class="btn-logout">Sair</a>
        </div>
    </div>
    
    <!-- ========================================
         CONTAINER PRINCIPAL
         ======================================== -->
    <div class="main-container">
        <!-- Área do chat onde aparecem as mensagens -->
        <div class="chat-area" id="chatArea">
            <!-- Mensagem de boas-vindas inicial -->
            <div class="welcome-message" id="welcomeMessage">
                <h2>Como vamos iniciar seu projeto?</h2>
                <p>Descreva o tema ou área de interesse do seu trabalho acadêmico e receba sugestões criativas geradas por IA!</p>
                
                <!-- Sugestões rápidas -->
                <div class="suggestions">
                    <div class="suggestion-card" onclick="usarSugestao('Projeto de tecnologia')">
                        <h4>💻 Tecnologia</h4>
                        <p>Ideias para projetos de TI e programação</p>
                    </div>
                    <div class="suggestion-card" onclick="usarSugestao('Projeto de administração')">
                        <h4>📊 Administração</h4>
                        <p>Temas de gestão e negócios</p>
                    </div>
                    <div class="suggestion-card" onclick="usarSugestao('Projeto de engenharia')">
                        <h4>⚙️ Engenharia</h4>
                        <p>Projetos técnicos e inovação</p>
                    </div>
                    <div class="suggestion-card" onclick="usarSugestao('Projeto de educação')">
                        <h4>📚 Educação</h4>
                        <p>Metodologias e pedagogia</p>
                    </div>
                </div>
            </div>
            
            <!-- Indicador de carregamento -->
            <div class="loading" id="loading">
                <div class="loading-dots">
                    <span>●</span>
                    <span>●</span>
                    <span>●</span>
                </div>
                <p>Gerando resposta...</p>
            </div>
        </div>
        
        <!-- ========================================
             ÁREA DE INPUT
             ======================================== -->
        <div class="input-area">
            <div class="input-container">
                <textarea 
                    id="pergunta" 
                    placeholder="Digite sua pergunta sobre projetos acadêmicos..." 
                    rows="1"
                    onkeydown="if(event.key==='Enter' && !event.shiftKey){event.preventDefault();enviarPergunta();}"
                ></textarea>
                <button class="btn-send" onclick="enviarPergunta()" id="btnEnviar">
                    ➤
                </button>
            </div>
        </div>
    </div>
    
    <!-- ========================================
         MODAL DE HISTÓRICO
         ======================================== -->
    <div class="modal" id="modalHistorico">
        <div class="modal-content">
            <div class="modal-header">
                <h3>📜 Histórico de Interações</h3>
                <button class="btn-close" onclick="fecharHistorico()">✕</button>
            </div>
            
            <div id="conteudoHistorico">
                <?php if (count($historico) > 0): ?>
                    <?php foreach ($historico as $item): ?>
                        <div class="historico-item">
                            <strong>Pergunta:</strong>
                            <p><?php echo htmlspecialchars($item['pergunta']); ?></p>
                            <strong>Resposta:</strong>
                            <p><?php echo nl2br(htmlspecialchars(substr($item['resposta'], 0, 200))); ?>...</p>
                            <small><?php echo date('d/m/Y H:i', strtotime($item['data_interacao'])); ?></small>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #8e8ea0;">Nenhuma interação ainda. Comece fazendo uma pergunta!</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- ========================================
         JAVASCRIPT - FUNCIONALIDADES
         ======================================== -->
    <script>
        /**
         * FUNÇÃO: usarSugestao()
         * DESCRIÇÃO: Preenche o input com uma sugestão de pergunta
         * PARÂMETRO: texto - texto da sugestão clicada
         */
        function usarSugestao(texto) {
            document.getElementById('pergunta').value = texto;
            document.getElementById('pergunta').focus();
        }
        
        /**
         * FUNÇÃO: enviarPergunta()
         * DESCRIÇÃO: Envia pergunta para a API via AJAX
         * - Valida entrada
         * - Exibe mensagem do usuário
         * - Chama API Gemini
         * - Exibe resposta da IA
         */
        async function enviarPergunta() {
            const perguntaInput = document.getElementById('pergunta');
            const pergunta = perguntaInput.value.trim();
            
            // Validação: verifica se pergunta não está vazia
            if (!pergunta) {
                alert('Por favor, digite uma pergunta!');
                return;
            }
            
            // Remove mensagem de boas-vindas
            const welcomeMsg = document.getElementById('welcomeMessage');
            if (welcomeMsg) {
                welcomeMsg.style.display = 'none';
            }
            
            // Adiciona mensagem do usuário na tela
            adicionarMensagem('user', pergunta);
            
            // Limpa input e desabilita botão
            perguntaInput.value = '';
            document.getElementById('btnEnviar').disabled = true;
            
            // Mostra loading
            document.getElementById('loading').classList.add('show');
            
            try {
                // Faz requisição AJAX para processar_ia.php
                const formData = new FormData();
                formData.append('pergunta', pergunta);
                
                const response = await fetch('processar_ia.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                // Esconde loading
                document.getElementById('loading').classList.remove('show');
                
                // Verifica se houve sucesso
                if (data.sucesso) {
                    adicionarMensagem('assistant', data.resposta);
                } else {
                    adicionarMensagem('assistant', '❌ Erro: ' + data.erro);
                }
                
            } catch (error) {
                // Esconde loading
                document.getElementById('loading').classList.remove('show');
                adicionarMensagem('assistant', '❌ Erro na comunicação com o servidor. Tente novamente.');
            }
            
            // Reabilita botão
            document.getElementById('btnEnviar').disabled = false;
            perguntaInput.focus();
        }
        
        /**
         * FUNÇÃO: adicionarMensagem()
         * DESCRIÇÃO: Adiciona mensagem visual na área do chat
         * PARÂMETROS:
         *   - tipo: 'user' ou 'assistant'
         *   - conteudo: texto da mensagem
         */
        function adicionarMensagem(tipo, conteudo) {
            const chatArea = document.getElementById('chatArea');
            
            const messageDiv = document.createElement('div');
            messageDiv.className = 'message ' + tipo;
            
            const header = tipo === 'user' ? 'Você' : '🤖 Work-Ideas IA';
            
            messageDiv.innerHTML = `
                <div class="message-header">${header}</div>
                <div class="message-content">${conteudo}</div>
            `;
            
            chatArea.appendChild(messageDiv);
            
            // Scroll automático para última mensagem
            messageDiv.scrollIntoView({ behavior: 'smooth', block: 'end' });
        }
        
        /**
         * FUNÇÃO: mostrarHistorico()
         * DESCRIÇÃO: Abre modal com histórico de conversas
         */
        function mostrarHistorico() {
            document.getElementById('modalHistorico').classList.add('show');
        }
        
        /**
         * FUNÇÃO: fecharHistorico()
         * DESCRIÇÃO: Fecha modal de histórico
         */
        function fecharHistorico() {
            document.getElementById('modalHistorico').classList.remove('show');
        }
        
        // Fecha modal ao clicar fora dele
        window.onclick = function(event) {
            const modal = document.getElementById('modalHistorico');
            if (event.target === modal) {
                fecharHistorico();
            }
        }
    </script>
</body>
</html>