SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `memorias_em_nuvem`
--
CREATE DATABASE IF NOT EXISTS `memorias_em_nuvem` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `memorias_em_nuvem`;

-- --------------------------------------------------------

--
-- Estrutura para tabela `consultas`
--

CREATE TABLE `consultas` (
  `consulta_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `profissional` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `especialidade` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` datetime NOT NULL,
  `local` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notas` text COLLATE utf8mb4_unicode_ci,
  `status` enum('Agendada','Realizada','Cancelada') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Agendada',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `consultas` (`consulta_id`, `user_id`, `profissional`, `especialidade`, `data`, `local`, `notas`, `status`, `created_at`) VALUES
(1, 4, 'Dr Adão Montenegro', 'cardiologista', '2025-07-22 08:30:00', 'Clinica Montenegro', 'Levar acompannhante', 'Agendada', '2025-07-10 14:11:51');

-- --------------------------------------------------------

--
-- Estrutura para tabela `diary_entries`
--

CREATE TABLE `diary_entries` (
  `entry_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `entry_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `attached_files_path` text COLLATE utf8mb4_unicode_ci,
  `spotify_link` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `youtube_link` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `diary_entries` (`entry_id`, `user_id`, `title`, `content`, `entry_date`, `created_at`, `updated_at`, `attached_files_path`, `spotify_link`, `youtube_link`) VALUES
(1, 1, NULL, 'Music', '2025-07-14', '2025-07-14 11:23:06', '2025-07-14 11:23:06', NULL, 'https://open.spotify.com/track/0qOnSQQF0yzuPWsXrQ9paz', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `exames`
--

CREATE TABLE `exames` (
  `exame_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `tipo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` date NOT NULL,
  `local` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `preparo` text COLLATE utf8mb4_unicode_ci,
  `resultado_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('Agendado','Realizado','Cancelado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Agendado',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `exames` (`exame_id`, `user_id`, `tipo`, `data`, `local`, `preparo`, `resultado_path`, `status`, `created_at`) VALUES
(1, 4, 'hemoglobina', '2025-07-18', 'Laboratorio Lister', 'jejum 8 horas', NULL, 'Agendado', '2025-07-10 14:13:52');

-- --------------------------------------------------------

--
-- Estrutura para tabela `favorite_recipes`
--

CREATE TABLE `favorite_recipes` (
  `recipe_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `financial_goals`
--

CREATE TABLE `financial_goals` (
  `goal_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_amount` decimal(10,2) NOT NULL,
  `saved_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `target_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `financial_transactions`
--

CREATE TABLE `financial_transactions` (
  `transaction_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `type` enum('income','expense') COLLATE utf8mb4_unicode_ci NOT NULL,
  `transaction_date` date NOT NULL,
  `category` enum('mercado','academia','faculdade','casa','transporte','lazer','saude','outros') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'outros',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `financial_transactions` (`transaction_id`, `user_id`, `description`, `amount`, `type`, `transaction_date`, `category`, `created_at`) VALUES
(1, 5, 'Testando o finaceiro', 1500.00, 'income', '2025-07-09', '', '2025-07-09 20:26:33'),
(2, 5, 'Testando o finaceiro 2', 3000.00, 'income', '2025-07-09', '', '2025-07-09 20:26:46'),
(3, 4, 'Aluguel caçamba', 200.00, 'expense', '2025-07-05', 'outros', '2025-07-10 13:11:00');

-- --------------------------------------------------------

--
-- Estrutura para tabela `goals`
--

CREATE TABLE `goals` (
  `goal_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `category` enum('produtividade','saude','pessoal','financeiro','carreira','relacionamento') COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_value` decimal(10,2) DEFAULT NULL,
  `current_value` decimal(10,2) DEFAULT '0.00',
  `unit` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deadline` date NOT NULL,
  `start_date` date DEFAULT NULL,
  `term` enum('curto','medio','longo') COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_completed` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `health_records`
--

CREATE TABLE `health_records` (
  `record_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `mood` enum('zangado','feliz','triste','neutro') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'neutro',
  `water_intake` int(11) DEFAULT NULL,
  `sleep_hours` decimal(3,1) DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `symptoms` text COLLATE utf8mb4_unicode_ci,
  `physical_activity` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `record_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `lembretes`
--

CREATE TABLE `lembretes` (
  `lembrete_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `texto` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` datetime NOT NULL,
  `is_completed` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `notificacoes_enviadas` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin COMMENT 'Registra quais notificações já foram enviadas para este lembrete'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `lembretes` (`lembrete_id`, `usuario_id`, `texto`, `data`, `is_completed`, `created_at`, `notificacoes_enviadas`) VALUES
(1, 1, 'correr', '2025-07-10 09:33:00', 1, '2025-07-10 11:33:10', NULL),
(3, 1, 'ligar pro dentista', '2025-07-10 08:54:00', 1, '2025-07-10 11:48:11', NULL),
(4, 1, 'juarez', '2025-07-11 09:49:00', 1, '2025-07-10 11:52:42', '{\"24h\":\"2025-07-10 09:06:01\"}'),
(5, 1, 'aaaaaaaaaaaa', '2025-07-10 09:07:00', 1, '2025-07-10 11:56:41', '{\"24h\":\"2025-07-10 09:06:02\",\"12h\":\"2025-07-10 09:06:02\",\"6h\":\"2025-07-10 09:06:02\",\"2h\":\"2025-07-10 09:06:02\",\"1h\":\"2025-07-10 09:06:03\",\"30min\":\"2025-07-10 09:06:03\",\"10min\":\"2025-07-10 09:06:03\",\"5min\":\"2025-07-10 09:06:04\",\"expired\":\"2025-07-10 09:08:02\"}'),
(7, 1, 'diego', '2025-07-10 10:05:00', 0, '2025-07-10 12:57:11', '{\"24h\":\"2025-07-10 09:58:02\",\"12h\":\"2025-07-10 09:58:02\",\"6h\":\"2025-07-10 09:58:02\",\"2h\":\"2025-07-10 09:58:02\",\"1h\":\"2025-07-10 09:58:02\",\"30min\":\"2025-07-10 09:58:02\",\"10min\":\"2025-07-10 09:58:02\",\"5min\":\"2025-07-10 10:00:04\",\"expired\":\"2025-07-10 10:06:01\"}'),
(8, 4, 'Responder cotação!', '2025-07-10 13:00:00', 0, '2025-07-10 13:02:39', '{\"24h\":\"2025-07-10 10:03:02\",\"12h\":\"2025-07-10 10:03:02\",\"6h\":\"2025-07-10 10:03:02\",\"2h\":\"2025-07-10 11:00:02\",\"1h\":\"2025-07-10 12:00:03\",\"30min\":\"2025-07-10 12:30:02\",\"10min\":\"2025-07-10 12:50:02\",\"5min\":\"2025-07-10 12:55:02\",\"expired\":\"2025-07-10 13:01:02\"}');

-- --------------------------------------------------------

--
-- Estrutura para tabela `medicamentos`
--

CREATE TABLE `medicamentos` (
  `medicamento_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dosagem` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `frequencia` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_inicio` date NOT NULL,
  `data_termino` date DEFAULT NULL,
  `motivo` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `medicamentos` (`medicamento_id`, `user_id`, `nome`, `dosagem`, `frequencia`, `data_inicio`, `data_termino`, `motivo`, `created_at`) VALUES
(1, 4, 'Vitamina B12', 'uma gota', '1x ao dia', '2025-06-01', '2025-07-31', NULL, '2025-07-10 13:03:55');

-- --------------------------------------------------------

--
-- Estrutura para tabela `metas`
--

CREATE TABLE `metas` (
  `meta_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `titulo` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_limite` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `photos`
--

CREATE TABLE `photos` (
  `photo_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `album_id` int(11) DEFAULT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `thumbnail_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `location` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `upload_date` date NOT NULL,
  `capture_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `photos` (`photo_id`, `user_id`, `album_id`, `image_path`, `thumbnail_path`, `title`, `description`, `location`, `category`, `upload_date`, `capture_date`, `created_at`) VALUES
(1, 5, NULL, '5/686ed0a42cf4f.png', NULL, NULL, NULL, NULL, NULL, '2025-07-09', NULL, '2025-07-09 20:27:16');

-- --------------------------------------------------------

--
-- Estrutura para tabela `photo_albums`
--

CREATE TABLE `photo_albums` (
  `album_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `cover_photo_id` int(11) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `recurring_transactions`
--

CREATE TABLE `recurring_transactions` (
  `recurring_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `type` enum('income','expense') COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `frequency` enum('monthly','weekly','yearly') COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `next_date` date NOT NULL,
  `last_added_date` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `time_capsules`
--

CREATE TABLE `time_capsules` (
  `capsule_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recipient` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `open_date` date NOT NULL,
  `photo_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `audio_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `drawing_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_opened` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `opened_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `user_sessions`
--

CREATE TABLE `user_sessions` (
  `session_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int(11) NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `user_id` int(11) NOT NULL,
  `google_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `usuario` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cpf` varchar(14) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `senha` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `foto_perfil` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `bio` text COLLATE utf8mb4_unicode_ci,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `theme` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'light',
  `language` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pt-BR',
  `timezone` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'America/Sao_Paulo',
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'BRL',
  `failed_login_attempts` int(11) NOT NULL DEFAULT '0',
  `lockout_until` datetime DEFAULT NULL,
  `reset_token` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reset_token_expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `usuarios` (`user_id`, `google_id`, `usuario`, `email`, `cpf`, `senha`, `foto_perfil`, `birth_date`, `bio`, `last_login`, `created_at`, `updated_at`, `theme`, `language`, `timezone`, `currency`, `failed_login_attempts`, `lockout_until`, `reset_token`, `reset_token_expires_at`) VALUES
(1, NULL, 'diego', 'diegoibraim49@gmail.com', '14800261660', '12345678', 'user_1_686d60cfd9cc72.48558913.jpg', NULL, NULL, '2025-07-14 11:50:15', '2025-07-08 18:17:35', '2025-07-14 11:50:15', 'light', 'pt-BR', 'America/Sao_Paulo', 'BRL', 0, NULL, NULL, NULL),
(2, NULL, 'Wellinton', 'nayaraj83@gmail.com', '08745610606', 'npportes', NULL, NULL, NULL, '2025-07-10 14:03:16', '2025-07-08 18:28:49', '2025-07-10 14:03:16', 'light', 'pt-BR', 'America/Sao_Paulo', 'BRL', 0, NULL, NULL, NULL),
(3, NULL, 'Isabella', 'isabellarodriguesdesousa15@gmail.com', '01892217430', 'JYOWMAES2008', NULL, NULL, NULL, NULL, '2025-07-08 22:50:27', '2025-07-08 22:50:27', 'light', 'pt-BR', 'America/Sao_Paulo', 'BRL', 0, NULL, NULL, NULL),
(4, NULL, 'Debinha', '0001070102@senaimgaluno.com.br', '08848780660', 'Summer24', 'user_4_686e519ba6b819.82684874.jpg', NULL, NULL, '2025-07-10 14:02:43', '2025-07-09 11:22:37', '2025-07-10 14:02:43', 'light', 'pt-BR', 'America/Sao_Paulo', 'BRL', 0, NULL, NULL, NULL),
(5, NULL, 'Leandreksu', 'leandreksu@gmail.com', '13880216681', 'leandro2', 'user_5_686f9b075006e7.12439913.png', NULL, NULL, '2025-07-10 14:07:39', '2025-07-09 20:25:47', '2025-07-10 14:07:39', 'light', 'pt-BR', 'America/Sao_Paulo', 'BRL', 0, NULL, NULL, NULL);

--
-- Índices
--

ALTER TABLE `consultas`
  ADD PRIMARY KEY (`consulta_id`),
  ADD KEY `user_id` (`user_id`);

ALTER TABLE `diary_entries`
  ADD PRIMARY KEY (`entry_id`),
  ADD KEY `user_id` (`user_id`);

ALTER TABLE `exames`
  ADD PRIMARY KEY (`exame_id`),
  ADD KEY `user_id` (`user_id`);

ALTER TABLE `favorite_recipes`
  ADD PRIMARY KEY (`recipe_id`),
  ADD KEY `user_id` (`user_id`);

ALTER TABLE `financial_goals`
  ADD PRIMARY KEY (`goal_id`),
  ADD KEY `user_id` (`user_id`);

ALTER TABLE `financial_transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `user_id` (`user_id`);

ALTER TABLE `goals`
  ADD PRIMARY KEY (`goal_id`),
  ADD KEY `user_id` (`user_id`);

ALTER TABLE `health_records`
  ADD PRIMARY KEY (`record_id`),
  ADD UNIQUE KEY `unique_user_date` (`user_id`,`record_date`),
  ADD KEY `user_id` (`user_id`);

ALTER TABLE `lembretes`
  ADD PRIMARY KEY (`lembrete_id`),
  ADD KEY `usuario_id` (`usuario_id`);

ALTER TABLE `medicamentos`
  ADD PRIMARY KEY (`medicamento_id`),
  ADD KEY `user_id` (`user_id`);

ALTER TABLE `metas`
  ADD PRIMARY KEY (`meta_id`),
  ADD KEY `usuario_id` (`usuario_id`);

ALTER TABLE `photos`
  ADD PRIMARY KEY (`photo_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `album_id` (`album_id`);

ALTER TABLE `photo_albums`
  ADD PRIMARY KEY (`album_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `cover_photo_id` (`cover_photo_id`);

ALTER TABLE `recurring_transactions`
  ADD PRIMARY KEY (`recurring_id`),
  ADD KEY `user_id` (`user_id`);

ALTER TABLE `time_capsules`
  ADD PRIMARY KEY (`capsule_id`),
  ADD KEY `user_id` (`user_id`);

ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `user_id` (`user_id`);

ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `usuario` (`usuario`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `cpf` (`cpf`),
  ADD UNIQUE KEY `google_id` (`google_id`),
  ADD UNIQUE KEY `reset_token` (`reset_token`);

--
-- AUTO_INCREMENT
--

ALTER TABLE `consultas`
  MODIFY `consulta_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `diary_entries`
  MODIFY `entry_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `exames`
  MODIFY `exame_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `favorite_recipes`
  MODIFY `recipe_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `financial_goals`
  MODIFY `goal_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `financial_transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `goals`
  MODIFY `goal_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `health_records`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `lembretes`
  MODIFY `lembrete_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

ALTER TABLE `medicamentos`
  MODIFY `medicamento_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `metas`
  MODIFY `meta_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `photos`
  MODIFY `photo_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `photo_albums`
  MODIFY `album_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `recurring_transactions`
  MODIFY `recurring_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `time_capsules`
  MODIFY `capsule_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `usuarios`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Restrições de Chaves Estrangeiras (Foreign Keys)
--

ALTER TABLE `consultas`
  ADD CONSTRAINT `consultas_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`user_id`) ON DELETE CASCADE;

ALTER TABLE `diary_entries`
  ADD CONSTRAINT `diary_entries_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`user_id`) ON DELETE CASCADE;

ALTER TABLE `exames`
  ADD CONSTRAINT `exames_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`user_id`) ON DELETE CASCADE;

ALTER TABLE `favorite_recipes`
  ADD CONSTRAINT `favorite_recipes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`user_id`) ON DELETE CASCADE;

ALTER TABLE `financial_goals`
  ADD CONSTRAINT `financial_goals_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`user_id`) ON DELETE CASCADE;

ALTER TABLE `financial_transactions`
  ADD CONSTRAINT `financial_transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`user_id`) ON DELETE CASCADE;

ALTER TABLE `goals`
  ADD CONSTRAINT `goals_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`user_id`) ON DELETE CASCADE;

ALTER TABLE `health_records`
  ADD CONSTRAINT `health_records_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`user_id`) ON DELETE CASCADE;

ALTER TABLE `lembretes`
  ADD CONSTRAINT `lembretes_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`user_id`) ON DELETE CASCADE;

ALTER TABLE `medicamentos`
  ADD CONSTRAINT `medicamentos_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`user_id`) ON DELETE CASCADE;

ALTER TABLE `metas`
  ADD CONSTRAINT `metas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`user_id`) ON DELETE CASCADE;

ALTER TABLE `photos`
  ADD CONSTRAINT `photos_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `photos_ibfk_2` FOREIGN KEY (`album_id`) REFERENCES `photo_albums` (`album_id`) ON DELETE SET NULL;

ALTER TABLE `photo_albums`
  ADD CONSTRAINT `photo_albums_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `photo_albums_ibfk_2` FOREIGN KEY (`cover_photo_id`) REFERENCES `photos` (`photo_id`) ON DELETE SET NULL;

ALTER TABLE `recurring_transactions`
  ADD CONSTRAINT `recurring_transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`user_id`) ON DELETE CASCADE;

ALTER TABLE `time_capsules`
  ADD CONSTRAINT `time_capsules_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`user_id`) ON DELETE CASCADE;

ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`user_id`) ON DELETE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;