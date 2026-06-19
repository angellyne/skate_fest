-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 19/06/2026 às 13:43
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `skate_fest_competition`
--

DELIMITER $$
--
-- Procedimentos
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_estatisticas` (OUT `total` INT, OUT `media` DECIMAL(3,1), OUT `maior` DECIMAL(3,1))   BEGIN
    SELECT COUNT(*) INTO total FROM skatistas_competicao;
    SELECT IFNULL(ROUND(AVG(media_geral), 1), 0) INTO media FROM skatistas_competicao;
    SELECT IFNULL(ROUND(MAX(media_geral), 1), 0) INTO maior FROM skatistas_competicao;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `resetar_competicao` ()   BEGIN
    DELETE FROM skatistas_competicao;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `dicas`
--

CREATE TABLE `dicas` (
  `id` int(11) NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `conteudo` text NOT NULL,
  `categoria` varchar(50) NOT NULL,
  `imagem_url` varchar(500) DEFAULT NULL,
  `autor` varchar(100) DEFAULT 'Equipe SkateFest',
  `visualizacoes` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `dicas`
--

INSERT INTO `dicas` (`id`, `titulo`, `conteudo`, `categoria`, `imagem_url`, `autor`, `visualizacoes`, `created_at`, `updated_at`) VALUES
(1, 'Como dar seu primeiro Ollie', 'Mantenha os pés na posição correta: pé traseiro no tail e dianteiro no meio do shape. Dê um pop forte com o pé traseiro e deslize o dianteiro para cima. A chave é o timing e a prática constante.', 'Manobras', NULL, 'Pro Skater', 0, '2026-05-18 10:25:54', NULL),
(2, 'Manutenção do seu shape', 'Verifique regularmente se há rachaduras, troque a lixa quando estiver gasta e mantenha os rolamentos limpos e lubrificados. Um shape bem cuidado dura muito mais!', 'Equipamento', NULL, 'Equipe SkateFest', 0, '2026-05-18 10:25:54', NULL),
(3, 'Como escolher seu primeiro skate', 'Para iniciantes, recomenda-se shapes mais largos (8.0\" ou mais), rolamentos ABEC 5 ou 7 e rodas macias (78a-87a) para melhor aderência e estabilidade.', 'Equipamento', NULL, 'Especialista', 0, '2026-05-18 10:25:54', NULL),
(4, 'Dicas para evoluir no skate', 'Pratique regularmente, grave seus vídeos para analisar erros, ande com skatistas melhores e nunca desista após as quedas. A persistência é a chave!', 'Treino', NULL, 'Coach', 0, '2026-05-18 10:25:54', NULL),
(5, 'Proteção é essencial', 'Sempre use capacete, joelheiras e cotoveleiras. Quedas são inevitáveis, mas os equipamentos de proteção evitam lesões graves e te mantem skatando por mais tempo.', 'Segurança', NULL, 'Equipe SkateFest', 0, '2026-05-18 10:25:54', NULL),
(6, '5 manobras essenciais para iniciantes', '1. Ollie - A base de tudo\n2. Pop Shove-it\n3. Kickflip\n4. Heelflip\n5. 50-50 grind\nDomine essas e você já tem um bom repertório!', 'Manobras', NULL, 'Pro Skater', 0, '2026-05-18 10:25:54', NULL),
(7, 'Como limpar seus rolamentos', 'Remova os rolamentos, use removedor específico, seque bem e aplique lubrificante próprio. Nunca use WD-40!', 'Equipamento', NULL, 'Especialista em Manutenção', 0, '2026-05-18 10:25:54', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `eventos`
--

CREATE TABLE `eventos` (
  `id` int(11) NOT NULL,
  `nome_evento` varchar(100) NOT NULL,
  `data_evento` date NOT NULL,
  `hora_evento` time DEFAULT NULL,
  `cidade` varchar(100) NOT NULL,
  `estado` char(2) NOT NULL,
  `local_evento` varchar(200) NOT NULL,
  `descricao` text DEFAULT NULL,
  `capacidade_maxima` int(11) DEFAULT 100,
  `inscritos` int(11) DEFAULT 0,
  `status` enum('agendado','andamento','finalizado','cancelado') DEFAULT 'agendado',
  `representante_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `eventos`
--

INSERT INTO `eventos` (`id`, `nome_evento`, `data_evento`, `hora_evento`, `cidade`, `estado`, `local_evento`, `descricao`, `capacidade_maxima`, `inscritos`, `status`, `representante_id`, `created_at`, `updated_at`) VALUES
(1, 'SkateFest SP 2024', '2024-12-15', '09:00:00', 'São Paulo', 'SP', 'Parque Ibirapuera', 'O maior evento de skate do Brasil!', 200, 0, 'agendado', 1, '2026-05-18 10:25:54', NULL),
(2, 'Rio Street Challenge', '2024-11-20', '10:00:00', 'Rio de Janeiro', 'RJ', 'Praia de Copacabana', 'Competição de street na orla', 150, 0, 'agendado', 1, '2026-05-18 10:25:54', NULL),
(3, 'Curitiba Bowl Series', '2024-10-10', '14:00:00', 'Curitiba', 'PR', 'Passeio Público', 'Competição no bowl mais famoso do sul', 100, 0, 'finalizado', 1, '2026-05-18 10:25:54', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `produtos`
--

CREATE TABLE `produtos` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `preco` decimal(10,2) NOT NULL,
  `categoria` varchar(50) NOT NULL,
  `imagem` varchar(500) DEFAULT NULL,
  `estoque` int(11) DEFAULT 0,
  `destaque` tinyint(4) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `produtos`
--

INSERT INTO `produtos` (`id`, `nome`, `descricao`, `preco`, `categoria`, `imagem`, `estoque`, `destaque`, `created_at`) VALUES
(1, 'Black Sheep Shape Pro', 'Shape de alta performance com tecnologia de carbono', 249.90, 'Shapes', NULL, 50, 1, '2026-05-18 10:25:54'),
(2, 'Redz Rolamentos', 'Rolamentos ABEC 7 com lubrificação profissional', 189.90, 'Rolamentos', NULL, 100, 1, '2026-05-18 10:25:54'),
(3, 'Santa Cruz Classic', 'Shape clássico Santa Cruz edição limitada', 299.90, 'Shapes', NULL, 30, 1, '2026-05-18 10:25:54'),
(4, 'Element Complete', 'Skate completo da Element para iniciantes', 279.90, 'Completos', NULL, 40, 1, '2026-05-18 10:25:54'),
(5, 'DC Shoes Legacy', 'Tênis profissional com amortecimento exclusivo', 399.90, 'Calçados', NULL, 60, 1, '2026-05-18 10:25:54'),
(6, 'Vans Old Skool', 'Clássico do skate, conforto e estilo', 349.90, 'Calçados', NULL, 80, 1, '2026-05-18 10:25:54'),
(7, 'Independent Trucks', 'Os melhores trucks do mercado', 199.90, 'Trucks', NULL, 45, 0, '2026-05-18 10:25:54'),
(8, 'Bones Wheels', 'Rodas 54mm 99a para street', 159.90, 'Rodas', NULL, 70, 0, '2026-05-18 10:25:54');

-- --------------------------------------------------------

--
-- Estrutura para tabela `representantes`
--

CREATE TABLE `representantes` (
  `id` int(11) NOT NULL,
  `nome_representante` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `ativo` tinyint(4) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `representantes`
--

INSERT INTO `representantes` (`id`, `nome_representante`, `email`, `senha`, `telefone`, `ativo`, `created_at`, `updated_at`) VALUES
(1, 'Administrador', 'admin@skatefest.com', 'admin123', '(11) 99999-9999', 1, '2026-05-18 10:25:54', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `skatistas_competicao`
--

CREATE TABLE `skatistas_competicao` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `pais` varchar(50) NOT NULL DEFAULT 'Brasil',
  `idade` int(11) NOT NULL,
  `kickflip` decimal(3,1) DEFAULT 0.0,
  `heelflip` decimal(3,1) DEFAULT 0.0,
  `tre_flip` decimal(3,1) DEFAULT 0.0,
  `varial` decimal(3,1) DEFAULT 0.0,
  `laser` decimal(3,1) DEFAULT 0.0,
  `media_geral` decimal(3,1) DEFAULT 0.0,
  `pontuacao_total` decimal(5,1) GENERATED ALWAYS AS (`kickflip` + `heelflip` + `tre_flip` + `varial` + `laser`) STORED,
  `data_participacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Acionadores `skatistas_competicao`
--
DELIMITER $$
CREATE TRIGGER `atualizar_media_skater` BEFORE UPDATE ON `skatistas_competicao` FOR EACH ROW BEGIN
    SET NEW.media_geral = (NEW.kickflip + NEW.heelflip + NEW.tre_flip + NEW.varial + NEW.laser) / 5;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `calcular_media_skater` BEFORE INSERT ON `skatistas_competicao` FOR EACH ROW BEGIN
    SET NEW.media_geral = (NEW.kickflip + NEW.heelflip + NEW.tre_flip + NEW.varial + NEW.laser) / 5;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `skatistas_eventos`
--

CREATE TABLE `skatistas_eventos` (
  `id` int(11) NOT NULL,
  `nome_skatista` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `telefone` varchar(20) NOT NULL,
  `idade` int(11) NOT NULL,
  `categoria` enum('Mirim','Iniciante','Amador','Profissional','Master') NOT NULL,
  `nome_evento` varchar(100) NOT NULL,
  `evento_id` int(11) DEFAULT NULL,
  `data_inscricao` timestamp NOT NULL DEFAULT current_timestamp(),
  `checkin_realizado` tinyint(4) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `skatistas_eventos`
--

INSERT INTO `skatistas_eventos` (`id`, `nome_skatista`, `email`, `telefone`, `idade`, `categoria`, `nome_evento`, `evento_id`, `data_inscricao`, `checkin_realizado`, `created_at`) VALUES
(1, 'ANGELLYNE MARCIEL MARQUES', 'angellyne.marques@escola.pr.gov.br', '45920020219', 16, 'Iniciante', 'SkateFest SP 2024', NULL, '2026-05-18 10:51:01', 0, '2026-05-18 10:51:01'),
(2, 'ANGELLYNE MARCIEL MARQUES', 'angellyne.marques@escola.pr.gov.br', '45920020219', 14, 'Iniciante', 'SkateFest SP 2024', NULL, '2026-05-18 12:25:20', 0, '2026-05-18 12:25:20');

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `vw_estatisticas_gerais`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `vw_estatisticas_gerais` (
`total_participantes` bigint(21)
,`total_eventos` bigint(21)
,`total_inscricoes` bigint(21)
,`media_geral_competicoes` decimal(4,1)
,`lider_ranking` varchar(100)
,`maior_nota` decimal(3,1)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `vw_ranking`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `vw_ranking` (
`id` int(11)
,`nome` varchar(100)
,`pais` varchar(50)
,`idade` int(11)
,`media_geral` decimal(3,1)
,`rank_position` bigint(21)
);

-- --------------------------------------------------------

--
-- Estrutura para view `vw_estatisticas_gerais`
--
DROP TABLE IF EXISTS `vw_estatisticas_gerais`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_estatisticas_gerais`  AS SELECT (select count(0) from `skatistas_competicao`) AS `total_participantes`, (select count(0) from `eventos`) AS `total_eventos`, (select count(0) from `skatistas_eventos`) AS `total_inscricoes`, (select round(avg(`skatistas_competicao`.`media_geral`),1) from `skatistas_competicao` where `skatistas_competicao`.`media_geral` > 0) AS `media_geral_competicoes`, (select `skatistas_competicao`.`nome` from `skatistas_competicao` order by `skatistas_competicao`.`media_geral` desc limit 1) AS `lider_ranking`, (select max(`skatistas_competicao`.`media_geral`) from `skatistas_competicao`) AS `maior_nota` ;

-- --------------------------------------------------------

--
-- Estrutura para view `vw_ranking`
--
DROP TABLE IF EXISTS `vw_ranking`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_ranking`  AS SELECT `skatistas_competicao`.`id` AS `id`, `skatistas_competicao`.`nome` AS `nome`, `skatistas_competicao`.`pais` AS `pais`, `skatistas_competicao`.`idade` AS `idade`, `skatistas_competicao`.`media_geral` AS `media_geral`, rank() over ( order by `skatistas_competicao`.`media_geral` desc) AS `rank_position` FROM `skatistas_competicao` WHERE `skatistas_competicao`.`media_geral` > 0 ;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `dicas`
--
ALTER TABLE `dicas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_categoria` (`categoria`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Índices de tabela `eventos`
--
ALTER TABLE `eventos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_data_evento` (`data_evento`),
  ADD KEY `idx_cidade_estado` (`cidade`,`estado`),
  ADD KEY `idx_representante` (`representante_id`),
  ADD KEY `idx_status` (`status`);

--
-- Índices de tabela `produtos`
--
ALTER TABLE `produtos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_categoria` (`categoria`),
  ADD KEY `idx_destaque` (`destaque`);

--
-- Índices de tabela `representantes`
--
ALTER TABLE `representantes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_ativo` (`ativo`);

--
-- Índices de tabela `skatistas_competicao`
--
ALTER TABLE `skatistas_competicao`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_media` (`media_geral`),
  ADD KEY `idx_pais` (`pais`),
  ADD KEY `idx_idade` (`idade`);

--
-- Índices de tabela `skatistas_eventos`
--
ALTER TABLE `skatistas_eventos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_evento` (`nome_evento`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_categoria` (`categoria`),
  ADD KEY `idx_evento_id` (`evento_id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `dicas`
--
ALTER TABLE `dicas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `eventos`
--
ALTER TABLE `eventos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `produtos`
--
ALTER TABLE `produtos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `representantes`
--
ALTER TABLE `representantes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `skatistas_competicao`
--
ALTER TABLE `skatistas_competicao`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `skatistas_eventos`
--
ALTER TABLE `skatistas_eventos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `eventos`
--
ALTER TABLE `eventos`
  ADD CONSTRAINT `eventos_ibfk_1` FOREIGN KEY (`representante_id`) REFERENCES `representantes` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `skatistas_eventos`
--
ALTER TABLE `skatistas_eventos`
  ADD CONSTRAINT `skatistas_eventos_ibfk_1` FOREIGN KEY (`evento_id`) REFERENCES `eventos` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
