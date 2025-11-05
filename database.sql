-- ========================================
-- Projeto: Work-Ideas
-- ========================================
DROP DATABASE IF EXISTS projeto_ia_academico;

-- Cria banco com charset UTF-8 (suporta acentuação)
CREATE DATABASE projeto_ia_academico 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

-- Seleciona o banco criado
USE projeto_ia_academico;

-- ====================================
-- TABELA: usuarios
-- ====================================
-- Armazena dados dos usuários cadastrados
-- ====================================
CREATE TABLE usuarios (
    -- Identificador único (chave primária)
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Nome completo do usuário
    nome VARCHAR(100) NOT NULL,
    
    -- Email (único, usado para login)
    email VARCHAR(100) NOT NULL UNIQUE,
    
    -- Senha criptografada (hash bcrypt)
    senha VARCHAR(255) NOT NULL,
    
    -- Data de cadastro automática
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Índice para busca rápida por email
    INDEX idx_email (email)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Tabela de usuários do sistema';

-- ====================================
-- TABELA: historico_ia
-- ====================================
-- Registra todas interações com a IA Gemini
-- ====================================
CREATE TABLE historico_ia (
    -- Identificador único da interação
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- ID do usuário (relacionamento com tabela usuarios)
    usuario_id INT NOT NULL,
    
    -- Pergunta feita pelo usuário
    pergunta TEXT NOT NULL,
    
    -- Resposta gerada pela IA
    resposta TEXT NOT NULL,
    
    -- Data e hora da interação
    data_interacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Chave estrangeira: se usuário for deletado, histórico também
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    
    -- Índice composto para busca rápida por usuário e data
    INDEX idx_usuario_data (usuario_id, data_interacao)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Histórico de conversas com IA';

-- ====================================
-- DADOS DE EXEMPLO (OPCIONAL)
-- ====================================
-- **Descomente** as linhas abaixo para criar usuário de teste
-- Senha padrão: 123456 (hash bcrypt)
-- ====================================

/*
INSERT INTO usuarios (nome, email, senha) VALUES 
('Usuário Teste', 'teste@workideas.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
*/

-- ====================================
-- CONSULTAS PARA VERIFICAÇÃO
-- ====================================

-- Ver todos os usuários cadastrados
-- SELECT id, nome, email, data_cadastro FROM usuarios;

-- Ver total de interações por usuário
-- SELECT u.nome, COUNT(h.id) as total_perguntas 
-- FROM usuarios u 
-- LEFT JOIN historico_ia h ON u.id = h.usuario_id 
-- GROUP BY u.id;

-- Ver últimas 10 interações
-- SELECT u.nome, h.pergunta, h.data_interacao 
-- FROM historico_ia h 
-- JOIN usuarios u ON h.usuario_id = u.id 
-- ORDER BY h.data_interacao DESC 
-- LIMIT 10;

-- ====================================
-- INFORMAÇÕES DO BANCO
-- ====================================
-- Exibe informações sobre as tabelas criadas
SHOW TABLES;

-- Exibe estrutura da tabela usuarios
DESCRIBE usuarios;

-- Exibe estrutura da tabela historico_ia
DESCRIBE historico_ia;

-- ====================================
-- MENSAGEM DE SUCESSO
-- ====================================
SELECT 
    '✅ Banco de dados criado com sucesso!' as STATUS,
    'projeto_ia_academico' as BANCO,
    '2 tabelas criadas: usuarios, historico_ia' as TABELAS,
    'Pronto para uso!' as MENSAGEM;

-- ====================================
-- FIM DO SCRIPT
-- ====================================