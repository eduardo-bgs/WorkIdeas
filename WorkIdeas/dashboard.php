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
           ESTILIZAÇÃO DA SCROLLBAR (BARRA DE ROLAGEM)
           SCROLLBAR GRANDE COM GRADIENTE VIBRANTE
           ======================================== */
        /* Para navegadores WebKit (Chrome, Safari, Edge) */
        ::-webkit-scrollbar {
            width: 16px;
            height: 16px;
        }
        
        ::-webkit-scrollbar-track {
            background: #2a2b32;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            border-radius: 10px;
            border: 3px solid #2a2b32;
            transition: all 0.3s ease;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, #f093fb 0%, #764ba2 50%, #667eea 100%);
            border: 2px solid #2a2b32;
        }
        
        /* Para Firefox */
        * {
            scrollbar-width: auto;
            scrollbar-color: #667eea #2a2b32;
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
           CARROSSEL DE SUGESTÕES
           LAYOUT TRIANGULAR EM DESKTOP
           ======================================== */
        .carousel-container {
            position: relative;
            margin-top: 40px;
            padding: 0 80px;
            max-width: 100%;
        }
        
        .carousel-wrapper {
            overflow: hidden;
            border-radius: 15px;
            min-height: 350px;
            display: flex;
            align-items: center;
        }
        
        .suggestions {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
            align-items: center;
            width: 100%;
            transition: opacity 0.5s ease;
            padding: 20px 0;
        }
        
        /* Layout triangular para desktop */
        @media (min-width: 1024px) {
            .suggestions {
                max-width: 800px;
                margin: 0 auto;
            }
            
            .suggestion-card:nth-child(3n + 1),
            .suggestion-card:nth-child(3n + 2) {
                flex: 0 0 calc(50% - 10px);
            }
            
            .suggestion-card:nth-child(3n) {
                flex: 0 0 350px;
                margin: 0 auto;
            }
        }
        
        /* Layout para tablet */
        @media (min-width: 768px) and (max-width: 1023px) {
            .suggestion-card {
                flex: 0 0 calc(50% - 10px);
            }
        }
        
        /* Layout para mobile */
        @media (max-width: 767px) {
            .carousel-container {
                padding: 0 50px;
            }
            
            .suggestion-card {
                flex: 0 0 100%;
            }
        }
        
        .suggestion-card {
            background: #444654;
            padding: 25px;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            min-height: 130px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .suggestion-card:hover {
            background: #565869;
            border-color: #10a37f;
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 8px 20px rgba(16, 163, 127, 0.3);
        }
        
        .suggestion-card h4 {
            font-size: 18px;
            margin-bottom: 10px;
            color: #10a37f;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .suggestion-card p {
            font-size: 14px;
            color: #b4b4b4;
            line-height: 1.5;
        }
        
        /* Botões de navegação do carrossel */
        .carousel-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            z-index: 10;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        .carousel-btn:hover {
            transform: translateY(-50%) scale(1.15);
            box-shadow: 0 6px 25px rgba(102, 126, 234, 0.7);
        }
        
        .carousel-btn:active {
            transform: translateY(-50%) scale(1.05);
        }
        
        .carousel-btn.prev {
            left: 10px;
        }
        
        .carousel-btn.next {
            right: 10px;
        }
        
        .carousel-btn:disabled {
            opacity: 0.3;
            cursor: not-allowed;
            transform: translateY(-50%);
        }
        
        /* Indicadores de navegação (dots) */
        .carousel-indicators {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 25px;
        }
        
        .indicator-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #444654;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .indicator-dot.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            width: 35px;
            border-radius: 6px;
        }
        
        .indicator-dot:hover {
            border-color: #667eea;
            transform: scale(1.2);
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
           LOADING (CARREGAMENTO)
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
           MODAL DE HISTÓRICO
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
                
                <!-- Carrossel de sugestões -->
                <div class="carousel-container">
                    <button class="carousel-btn prev" id="prevBtn" onclick="moveCarousel(-1)">‹</button>
                    
                    <div class="carousel-wrapper">
                        <div class="suggestions" id="carouselSuggestions">
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
                            <div class="suggestion-card" onclick="usarSugestao('Projeto de saúde')">
                                <h4>🏥 Saúde</h4>
                                <p>Medicina, enfermagem e bem-estar</p>
                            </div>
                            <div class="suggestion-card" onclick="usarSugestao('Projeto de meio ambiente')">
                                <h4>🌱 Meio Ambiente</h4>
                                <p>Sustentabilidade e ecologia</p>
                            </div>
                            <div class="suggestion-card" onclick="usarSugestao('Projeto de marketing')">
                                <h4>📱 Marketing</h4>
                                <p>Marketing digital e comunicação</p>
                            </div>
                            <div class="suggestion-card" onclick="usarSugestao('Projeto de direito')">
                                <h4>⚖️ Direito</h4>
                                <p>Temas jurídicos e legislação</p>
                            </div>
                            <div class="suggestion-card" onclick="usarSugestao('Projeto de psicologia')">
                                <h4>🧠 Psicologia</h4>
                                <p>Comportamento e saúde mental</p>
                            </div>
                        </div>
                    </div>
                    
                    <button class="carousel-btn next" id="nextBtn" onclick="moveCarousel(1)">›</button>
                </div>
                
                <!-- Indicadores do carrossel -->
                <div class="carousel-indicators" id="carouselIndicators"></div>
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
        // ========================================
        // VARIÁVEIS GLOBAIS DO CARROSSEL
        // ========================================
        let currentPage = 0;
        let cardsPerPage = 3;
        let totalCards = 9;
        let totalPages = Math.ceil(totalCards / cardsPerPage);
        
        /**
         * FUNÇÃO: initCarousel()
         * DESCRIÇÃO: Inicializa o carrossel ao carregar a página
         */
        function initCarousel() {
            updateCardsPerPage();
            createIndicators();
            showPage(currentPage);
            
            // Atualiza ao redimensionar janela
            window.addEventListener('resize', () => {
                updateCardsPerPage();
                createIndicators();
                showPage(currentPage);
            });
        }
        
        /**
         * FUNÇÃO: updateCardsPerPage()
         * DESCRIÇÃO: Define quantos cards mostrar por página baseado no tamanho da tela
         */
        function updateCardsPerPage() {
            if (window.innerWidth < 768) {
                cardsPerPage = 1;
            } else if (window.innerWidth < 1024) {
                cardsPerPage = 2;
            } else {
                cardsPerPage = 3;
            }
            totalPages = Math.ceil(totalCards / cardsPerPage);
        }
        
        /**
         * FUNÇÃO: createIndicators()
         * DESCRIÇÃO: Cria os indicadores (dots) do carrossel
         */
        function createIndicators() {
            const indicatorsContainer = document.getElementById('carouselIndicators');
            indicatorsContainer.innerHTML = '';
            
            for (let i = 0; i < totalPages; i++) {
                const dot = document.createElement('div');
                dot.className = 'indicator-dot';
                if (i === currentPage) dot.classList.add('active');
                dot.onclick = () => goToPage(i);
                indicatorsContainer.appendChild(dot);
            }
        }
        
        /**
         * FUNÇÃO: showPage()
         * DESCRIÇÃO: Mostra a página específica do carrossel
         */
        function showPage(pageIndex) {
            const cards = document.querySelectorAll('.suggestion-card');
            const startIndex = pageIndex * cardsPerPage;
            const endIndex = startIndex + cardsPerPage;
            
            cards.forEach((card, index) => {
                if (index >= startIndex && index < endIndex) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
            
            updateButtons();
            updateIndicators();
        }
        
        /**
         * FUNÇÃO: moveCarousel()
         * DESCRIÇÃO: Move o carrossel para frente ou trás
         */
        function moveCarousel(direction) {
            const carousel = document.getElementById('carouselSuggestions');
            
            // Adiciona animação de fade
            carousel.style.opacity = '0';
            
            setTimeout(() => {
                currentPage += direction;
                
                // Limita o índice
                if (currentPage < 0) currentPage = 0;
                if (currentPage >= totalPages) currentPage = totalPages - 1;
                
                showPage(currentPage);
                carousel.style.opacity = '1';
            }, 200);
        }
        
        /**
         * FUNÇÃO: goToPage()
         * DESCRIÇÃO: Vai para uma página específica
         */
        function goToPage(pageIndex) {
            const carousel = document.getElementById('carouselSuggestions');
            carousel.style.opacity = '0';
            
            setTimeout(() => {
                currentPage = pageIndex;
                showPage(currentPage);
                carousel.style.opacity = '1';
            }, 200);
        }
        
        /**
         * FUNÇÃO: updateButtons()
         * DESCRIÇÃO: Atualiza estado dos botões de navegação
         */
        function updateButtons() {
            document.getElementById('prevBtn').disabled = currentPage === 0;
            document.getElementById('nextBtn').disabled = currentPage === totalPages - 1;
        }
        
        /**
         * FUNÇÃO: updateIndicators()
         * DESCRIÇÃO: Atualiza indicadores ativos
         */
        function updateIndicators() {
            document.querySelectorAll('.indicator-dot').forEach((dot, index) => {
                dot.classList.toggle('active', index === currentPage);
            });
        }
        
        // Inicializa carrossel quando página carregar
        window.addEventListener('DOMContentLoaded', initCarousel);
        
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
                    adicionarMensagem('assistant', 'Erro: ' + data.erro);
                }
                
            } catch (error) {
                // Esconde loading
                document.getElementById('loading').classList.remove('show');
                adicionarMensagem('assistant', 'Erro na comunicação com o servidor. Tente novamente.');
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