-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 19-Ago-2025 às 05:43
-- Versão do servidor: 10.4.27-MariaDB
-- versão do PHP: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------

--
-- Estrutura da tabela `acessos`
--

CREATE TABLE `acessos` (
  `id` int(11) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `chave` varchar(50) NOT NULL,
  `grupo` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Extraindo dados da tabela `acessos`
--

INSERT INTO `acessos` (`id`, `nome`, `chave`, `grupo`) VALUES
(1, 'Usuários', 'usuarios', 1),
(2, 'Funcionários', 'funcionarios', 1),
(3, 'Clientes', 'clientes', 1),
(4, 'Clientes Retornos', 'clientes_retorno', 1),
(5, 'Fornecedores', 'fornecedores', 1),
(6, 'Serviços', 'servicos', 2),
(7, 'Cargos', 'cargos', 2),
(8, 'Categoria Serviços', 'cat_servicos', 2),
(9, 'Grupo Acessos', 'grupos', 2),
(10, 'Acessos', 'acessos', 2),
(11, 'Produtos', 'produtos', 3),
(12, 'Categorias', 'cat_produtos', 3),
(13, 'Estoque Baixo', 'estoque', 3),
(14, 'Saídas', 'saidas', 3),
(15, 'Entradas', 'entradas', 3),
(16, 'Vendas', 'vendas', 4),
(17, 'Compras', 'compras', 4),
(18, 'Contas à Pagar', 'pagar', 4),
(19, 'Contas à Receber', 'receber', 4),
(20, 'Agendamentos', 'agendamentos', 5),
(21, 'Serviços Agendamentos', 'servicos_agenda', 5),
(22, 'Home', 'home', 0),
(25, 'Relatório Produtos', 'rel_produtos', 7),
(26, 'Relatório de Entradas', 'rel_entradas', 7),
(27, 'Relatório de Saídas', 'rel_saidas', 7),
(28, 'Relatório de Comissões', 'rel_comissoes', 7),
(29, 'Relatório de Contas', 'rel_contas', 7),
(30, 'Aniversáriantes', 'rel_aniv', 7),
(31, 'Relatório de Lucro', 'rel_lucro', 7),
(32, 'Textos Banner Index', 'textos_index', 8),
(33, 'Comentários / Depoimentos', 'comentarios', 8),
(34, 'Configurações do Sistema', 'configuracoes', 0);

-- --------------------------------------------------------

--
-- Estrutura da tabela `agendamentos`
--

CREATE TABLE `agendamentos` (
  `id` int(11) NOT NULL,
  `funcionario` int(11) NOT NULL,
  `cliente` int(11) NOT NULL,
  `data` date NOT NULL,
  `hora` time NOT NULL,
  `obs` varchar(100) DEFAULT NULL,
  `data_lanc` date NOT NULL,
  `usuario` int(11) NOT NULL,
  `status` varchar(20) NOT NULL,
  `servico` int(11) NOT NULL,
  `servicos` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `cargos`
--

CREATE TABLE `cargos` (
  `id` int(11) NOT NULL,
  `nome` varchar(35) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Extraindo dados da tabela `cargos`
--

INSERT INTO `cargos` (`id`, `nome`) VALUES
(9, 'Administrador'),
(10, 'Gerente'),
(11, 'Recepcionista'),
(12, 'Barbeiro'),
(13, 'Cabeleireira'),
(14, 'Manicure'),
(15, 'Esteticista');

-- --------------------------------------------------------

--
-- Estrutura da tabela `cat_produtos`
--

CREATE TABLE `cat_produtos` (
  `id` int(11) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `segmento` varchar(20) NOT NULL DEFAULT 'barbearia'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Extraindo dados da tabela `cat_produtos`
--

INSERT INTO `cat_produtos` (`id`, `nome`) VALUES
(1, 'Pomadas'),
(2, 'Cremes'),
(3, 'Lâminas e Giletes'),
(4, 'Bebidas'),
(5, 'Gel');

-- --------------------------------------------------------

--
-- Estrutura da tabela `cat_servicos`
--

CREATE TABLE `cat_servicos` (
  `id` int(11) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `segmento` varchar(20) NOT NULL DEFAULT 'barbearia'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Extraindo dados da tabela `cat_servicos`
--

INSERT INTO `cat_servicos` (`id`, `nome`) VALUES
(1, 'Corte');

-- --------------------------------------------------------

--
-- Estrutura da tabela `clientes`
--

CREATE TABLE `clientes` (
  `id` int(11) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `endereco` varchar(100) DEFAULT NULL,
  `data_nasc` date DEFAULT NULL,
  `data_cad` date NOT NULL,
  `cartoes` int(11) NOT NULL,
  `data_retorno` date DEFAULT NULL,
  `ultimo_servico` int(11) NOT NULL DEFAULT 0,
  `alertado` varchar(5) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Extraindo dados da tabela `clientes`
--

INSERT INTO `clientes` (`id`, `nome`, `telefone`, `endereco`, `data_nasc`, `data_cad`, `cartoes`, `data_retorno`, `ultimo_servico`, `alertado`) VALUES
(15, 'Fernando Machado', '(55) 62991-2526', NULL, '0000-00-00', '2025-07-11', 0, NULL, 0, 'Não');

-- --------------------------------------------------------

--
-- Estrutura da tabela `comentarios`
--

CREATE TABLE `comentarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `texto` varchar(500) NOT NULL,
  `foto` varchar(100) DEFAULT NULL,
  `ativo` varchar(5) NOT NULL,
  `segmento` varchar(20) NOT NULL DEFAULT 'barbearia'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Extraindo dados da tabela `comentarios`
--

INSERT INTO `comentarios` (`id`, `nome`, `texto`, `foto`, `ativo`) VALUES
(2, 'Paulo Campos', 'Aqui será o texto do comentário referente a essa mensagem e poderá ser aprovado ou não pelo administrador do site. Aqui será o texto do comentário referente a essa mensagem e poderá ser aprovado ou não pelo administrador do site.', '27-08-2025-21-05-12-01.png', 'Sim'),
(3, 'Marcos Silva', 'Aqui será o texto do comentário referente a essa mensagem e poderá ser aprovado ou não pelo administrador do site.', '27-08-2025-21-07-00-03.png', 'Sim'),
(4, 'Marcos Santos', 'Aqui será o texto do comentário referente a essa mensagem e poderá ser aprovado ou não pelo administrador do site.', '27-08-2025-21-07-09-05.png', 'Sim'),
(15, 'Fabricio Campos', 'Excelente barbearia, sempre bom atendimento, preços assecíveis, Excelente barbearia, sempre bom atendimento, preços assecíveis, Excelente barbearia, sempre bom atendimento, preços assecíveis, ', '27-08-2025-21-07-17-06.png', 'Sim'),
(16, 'Marcelo Silva', 'Excelente barbearia, sempre bom atendimento, preços assecíveis, Excelente barbearia, sempre bom atendimento, preços assecíveis, Excelente barbearia, sempre bom atendimento, preços assecíveis, ', '27-08-2025-21-07-36-07.png', 'Sim'),
(18, 'Priscila Matos', 'Excelente Salão de cabereira, muito bom atendimento.', '27-08-2025-21-08-59-08.png', 'Não');

-- --------------------------------------------------------

--
-- Estrutura da tabela `config`
--

CREATE TABLE `config` (
  `id` int(11) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `telefone_fixo` varchar(20) DEFAULT NULL,
  `telefone_whatsapp` varchar(20) NOT NULL,
  `endereco` varchar(100) DEFAULT NULL,
  `logo` varchar(100) DEFAULT NULL,
  `icone` varchar(100) DEFAULT NULL,
  `logo_rel` varchar(100) DEFAULT NULL,
  `tipo_rel` varchar(10) DEFAULT NULL,
  `instagram` varchar(100) DEFAULT NULL,
  `tipo_comissao` varchar(25) NOT NULL,
  `texto_rodape` varchar(255) DEFAULT NULL,
  `img_banner_index` varchar(100) DEFAULT NULL,
  `texto_sobre` varchar(600) DEFAULT NULL,
  `imagem_sobre` varchar(100) DEFAULT NULL,
  `icone_site` varchar(100) NOT NULL,
  `mapa` varchar(1000) DEFAULT NULL,
  `texto_fidelidade` varchar(255) DEFAULT NULL,
  `quantidade_cartoes` int(11) NOT NULL,
  `texto_agendamento` varchar(30) DEFAULT NULL,
  `msg_agendamento` varchar(5) DEFAULT NULL,
  `segmento` varchar(20) NOT NULL DEFAULT 'barbearia'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Extraindo dados da tabela `config`
--

INSERT INTO `config` (`id`, `nome`, `email`, `telefone_fixo`, `telefone_whatsapp`, `endereco`, `logo`, `icone`, `logo_rel`, `tipo_rel`, `instagram`, `tipo_comissao`, `texto_rodape`, `img_banner_index`, `texto_sobre`, `imagem_sobre`, `icone_site`, `mapa`, `texto_fidelidade`, `quantidade_cartoes`, `texto_agendamento`, `msg_agendamento`, `segmento`) VALUES
(2, 'Barbearia PRO', 'admin@admin', '(33) 3333-3333', '(31) 99999-9999', 'Rua X Número 150 - Bairro Centro', 'logo.png', 'favicon.png', 'logo_rel.jpg', 'PDF', 'https://www.instagram.com/', 'R$', 'Este texto pode ser modificado no painel do sistema, nas opções de configurações, é um texto com tamanho para até 255 caracteres e será apresentado no rodapé de todo o site!', 'banner.jpg', 'Este texto pode ser alterado no painel administrativo nas configurações, aqui voce vai colocar um texto escrevendo sobre sua empresa. Este texto pode ser alterado no painel administrativo nas configurações, aqui voce vai colocar um texto escrevendo sobre.', 'foto_barbearia.jpg', 'favicon.png', '<iframe src=\"https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15111.737337923378!2d-44.43875433848488!3d-18.756469927096727!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0xa862fa2d52b993%3A0xe3bd921aa58bc4a8!2sCentro%2C%20Curvelo%20-%20MG%2C%2035790-000!5e0!3m2!1spt-BR!2sbr!4v1655243351692!5m2!1spt-BR!2sbr\" width=\"600\" height=\"450\" style=\"border:0;\" allowfullscreen=\"\" loading=\"lazy\" referrerpolicy=\"no-referrer-when-downgrade\"></iframe>', 'Parabéns, você completou seus cartões Fidelidade, seu próximo corte será gratuito!!', 10, 'Selecionar Barbeiro', 'Não', 'barbearia');

-- --------------------------------------------------------

--
-- Estrutura da tabela `dias`
--

CREATE TABLE `dias` (
  `id` int(11) NOT NULL,
  `dia` varchar(25) NOT NULL,
  `funcionario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Extraindo dados da tabela `dias`
--

INSERT INTO `dias` (`id`, `dia`, `funcionario`) VALUES
(1, 'Segunda-Feira', 11),
(2, 'Terça-Feira', 11),
(3, 'Quarta-Feira', 11),
(4, 'Quinta-Feira', 11),
(5, 'Sexta-Feira', 11),
(6, 'Sábado', 11),
(8, 'Terça-Feira', 10),
(9, 'Quarta-Feira', 10),
(10, 'Quinta-Feira', 10),
(11, 'Sexta-Feira', 10),
(12, 'Terça-Feira', 6),
(13, 'Quarta-Feira', 6),
(14, 'Quinta-Feira', 6),
(15, 'Sexta-Feira', 6),
(16, 'Sábado', 6),
(17, 'Segunda-Feira', 10);

-- --------------------------------------------------------

--
-- Estrutura da tabela `entradas`
--

CREATE TABLE `entradas` (
  `id` int(11) NOT NULL,
  `produto` int(11) NOT NULL,
  `quantidade` int(11) NOT NULL,
  `motivo` varchar(50) NOT NULL,
  `usuario` int(11) NOT NULL,
  `data` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `fornecedores`
--

CREATE TABLE `fornecedores` (
  `id` int(11) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `endereco` varchar(100) DEFAULT NULL,
  `data_cad` date NOT NULL,
  `tipo_chave` varchar(25) DEFAULT NULL,
  `chave_pix` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Extraindo dados da tabela `fornecedores`
--

INSERT INTO `fornecedores` (`id`, `nome`, `telefone`, `endereco`, `data_cad`, `tipo_chave`, `chave_pix`) VALUES
(1, 'Fornecedor 2', '(55) 55555-5555', 'Rua Guajajaras 140 Centro', '2022-05-31', 'Email', 'fornecedor2@hotmail.com'),
(2, 'Fornecedor 1', '(88) 88888-8888', 'Teste', '2022-05-31', 'CPF', '123111111'),
(4, 'Fornecedor 3', '(31) 78999-9999', 'Rua C', '2022-06-13', 'CNPJ', '11.111.111/1111-11');

-- --------------------------------------------------------

--
-- Estrutura da tabela `grupo_acessos`
--

CREATE TABLE `grupo_acessos` (
  `id` int(11) NOT NULL,
  `nome` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Extraindo dados da tabela `grupo_acessos`
--

INSERT INTO `grupo_acessos` (`id`, `nome`) VALUES
(1, 'Pessoas'),
(2, 'Cadastros'),
(3, 'Produtos'),
(4, 'Financeiro'),
(5, 'Agendamento / Serviços'),
(7, 'Relatórios'),
(8, 'Dados Site');

-- --------------------------------------------------------

--
-- Estrutura da tabela `horarios`
--

CREATE TABLE `horarios` (
  `id` int(11) NOT NULL,
  `horario` time NOT NULL,
  `funcionario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Extraindo dados da tabela `horarios`
--

INSERT INTO `horarios` (`id`, `horario`, `funcionario`) VALUES
(1, '08:00:00', 10),
(2, '09:00:00', 10),
(4, '12:00:00', 10),
(5, '10:00:00', 10),
(6, '13:00:00', 10),
(7, '11:00:00', 10),
(8, '08:00:00', 9),
(9, '09:00:00', 9),
(10, '08:00:00', 6),
(11, '09:00:00', 6),
(12, '10:00:00', 6),
(14, '08:00:00', 11),
(15, '08:30:00', 11),
(16, '09:00:00', 11),
(17, '09:30:00', 11),
(18, '14:00:00', 10),
(19, '15:00:00', 10),
(20, '16:00:00', 10),
(21, '17:00:00', 10),
(22, '11:00:00', 6),
(23, '12:00:00', 6),
(24, '15:00:00', 6),
(25, '16:00:00', 6),
(26, '17:00:00', 6),
(27, '10:00:00', 11),
(28, '12:00:00', 11);

-- --------------------------------------------------------

--
-- Estrutura da tabela `pagar`
--

CREATE TABLE `pagar` (
  `id` int(11) NOT NULL,
  `descricao` varchar(50) DEFAULT NULL,
  `tipo` varchar(35) DEFAULT NULL,
  `valor` decimal(8,2) NOT NULL,
  `data_lanc` date NOT NULL,
  `data_venc` date NOT NULL,
  `data_pgto` date NOT NULL,
  `usuario_lanc` int(11) NOT NULL,
  `usuario_baixa` int(11) NOT NULL,
  `foto` varchar(100) DEFAULT NULL,
  `pessoa` int(11) NOT NULL,
  `pago` varchar(5) NOT NULL,
  `produto` int(11) NOT NULL,
  `quantidade` int(11) NOT NULL,
  `funcionario` int(11) NOT NULL,
  `servico` int(11) NOT NULL,
  `cliente` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `produtos`
--

CREATE TABLE `produtos` (
  `id` int(11) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `categoria` int(11) NOT NULL,
  `valor_compra` decimal(8,2) NOT NULL,
  `valor_venda` decimal(8,2) NOT NULL,
  `estoque` int(11) NOT NULL,
  `foto` varchar(100) DEFAULT NULL,
  `nivel_estoque` int(11) NOT NULL,
  `segmento` varchar(20) NOT NULL DEFAULT 'barbearia'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Extraindo dados da tabela `produtos`
--

INSERT INTO `produtos` (`id`, `nome`, `descricao`, `categoria`, `valor_compra`, `valor_venda`, `estoque`, `foto`, `nivel_estoque`) VALUES
(2, 'Creme Hidratação', 'Creme para hidratar...', 2, '2.00', '35.00', 41, '27-08-2025-20-48-46-creme.png', 5),
(4, 'Loção Pós Barba', 'Loção para barba creme X', 5, '10.00', '15.00', 48, '27-08-2025-20-34-52-oleo_barba.png', 5),
(5, 'Pomada Modeladora', 'Pomada Modeladora para Cabelos', 1, '10.00', '10.00', 8, '27-08-2025-20-39-25-gel.png', 5),
(7, 'Pomada Gel Modeladora', 'Pomada que tem uma ação de gel que mantém seu cabelo modelado mais tempo', 1, '10.00', '30.00', 60, '27-08-2025-20-33-52-pomada.png', 5);

-- --------------------------------------------------------

--
-- Estrutura da tabela `receber`
--

CREATE TABLE `receber` (
  `id` int(11) NOT NULL,
  `descricao` varchar(50) DEFAULT NULL,
  `tipo` varchar(35) DEFAULT NULL,
  `valor` decimal(8,2) NOT NULL,
  `data_lanc` date NOT NULL,
  `data_venc` date NOT NULL,
  `data_pgto` date NOT NULL,
  `usuario_lanc` int(11) NOT NULL,
  `usuario_baixa` int(11) NOT NULL,
  `foto` varchar(100) DEFAULT NULL,
  `pessoa` int(11) NOT NULL,
  `pago` varchar(5) NOT NULL,
  `produto` int(11) NOT NULL,
  `quantidade` int(11) NOT NULL,
  `servico` int(11) NOT NULL,
  `funcionario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `saidas`
--

CREATE TABLE `saidas` (
  `id` int(11) NOT NULL,
  `produto` int(11) NOT NULL,
  `quantidade` int(11) NOT NULL,
  `motivo` varchar(50) NOT NULL,
  `usuario` int(11) NOT NULL,
  `data` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `servicos`
--

CREATE TABLE `servicos` (
  `id` int(11) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `categoria` int(11) NOT NULL,
  `valor` decimal(8,2) NOT NULL,
  `foto` varchar(100) DEFAULT NULL,
  `dias_retorno` int(11) NOT NULL,
  `ativo` varchar(5) NOT NULL,
  `comissao` decimal(8,2) NOT NULL,
  `segmento` varchar(20) NOT NULL DEFAULT 'barbearia'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Extraindo dados da tabela `servicos`
--

INSERT INTO `servicos` (`id`, `nome`, `categoria`, `valor`, `foto`, `dias_retorno`, `ativo`, `comissao`) VALUES
(1, 'Corte', 1, '35.00', '27-08-2025-20-53-30-corte.png', 15, 'Sim', '10.00'),
(2, 'Barba', 1, '30.00', '27-08-2025-20-53-41-barba.png', 7, 'Sim', '10.00'),
(10, 'Corte + Barba', 1, '60.00', '27-08-2025-20-53-48-corte_barba.png', 15, 'Sim', '15.00');

-- --------------------------------------------------------

--
-- Estrutura da tabela `textos_index`
--

CREATE TABLE `textos_index` (
  `id` int(11) NOT NULL,
  `titulo` varchar(25) NOT NULL,
  `descricao` varchar(255) NOT NULL,
  `segmento` varchar(20) NOT NULL DEFAULT 'barbearia'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Extraindo dados da tabela `textos_index`
--

INSERT INTO `textos_index` (`id`, `titulo`, `descricao`, `segmento`) VALUES
(1, 'Cortes Profissionais', 'Descrição relacionado ao texto, você poderá alterar essa descrição e o título do texto acima no painel administrativo, lá terá uma opção para gerenciar os recursos do site.', 'barbearia'),
(2, 'Faça sua Barba', 'Descrição relacionado ao texto, você poderá alterar essa descrição e o título do texto acima no painel administrativo, lá terá uma opção para gerenciar os recursos do site.', 'barbearia');

-- --------------------------------------------------------

--
-- Estrutura da tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `cpf` varchar(20) DEFAULT NULL,
  `senha` varchar(25) NOT NULL,
  `senha_crip` varchar(100) NOT NULL,
  `nivel` varchar(35) NOT NULL,
  `data` date NOT NULL,
  `ativo` varchar(5) NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `endereco` varchar(100) DEFAULT NULL,
  `foto` varchar(100) DEFAULT NULL,
  `atendimento` varchar(5) NOT NULL,
  `tipo_chave` varchar(25) DEFAULT NULL,
  `chave_pix` varchar(50) DEFAULT NULL,
  `segmento` varchar(20) NOT NULL DEFAULT '',
  `precisa_trocar_senha` tinyint(1) NOT NULL DEFAULT 0,
  `demo` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Extraindo dados da tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `cpf`, `senha`, `senha_crip`, `nivel`, `data`, `ativo`, `telefone`, `endereco`, `foto`, `atendimento`, `tipo_chave`, `chave_pix`, `segmento`) VALUES
(6, 'Admin', 'admin@admin', '000.000.000-00', '', '$2y$10$IMyWjAsde9p5XvODcXbfEeb/qHn9edqTIgH.Hk5tRg9zsIBWoAJOa', 'Administrador', '2022-05-25', 'Sim', '(31) 99999-9999', 'Rua xxx, 8 ', '19-08-2025-00-05-26-admin.png', 'Sim', NULL, NULL, ''),
(10, 'João', 'joao@email.com', '999.999.999-99', '', '$2y$10$IMyWjAsde9p5XvODcXbfEeb/qHn9edqTIgH.Hk5tRg9zsIBWoAJOa', 'Barbeiro', '2022-05-30', 'Sim', '(11) 99999-9999', 'Rua 10', 'sem-foto.jpg', 'Sim', '', '', 'barbearia'),
(20, 'Camila Rocha', 'camila@email.com', '111.111.111-11', '', '$2y$10$IMyWjAsde9p5XvODcXbfEeb/qHn9edqTIgH.Hk5tRg9zsIBWoAJOa', 'Cabeleireira', '2026-01-10', 'Sim', '(11) 98888-1010', 'Rua Oscar Freire, 900', 'camila-rocha.jpg', 'Sim', '', '', 'salao'),
(21, 'Bianca Alves', 'bianca@email.com', '222.222.222-22', '', '$2y$10$IMyWjAsde9p5XvODcXbfEeb/qHn9edqTIgH.Hk5tRg9zsIBWoAJOa', 'Manicure', '2026-01-10', 'Sim', '(11) 98888-2020', 'Rua Oscar Freire, 900', 'bianca-alves.jpg', 'Sim', '', '', 'salao'),
(22, 'Renata Lima', 'renata@email.com', '333.333.333-33', '', '$2y$10$IMyWjAsde9p5XvODcXbfEeb/qHn9edqTIgH.Hk5tRg9zsIBWoAJOa', 'Esteticista', '2026-01-10', 'Sim', '(11) 98888-3030', 'Rua Oscar Freire, 900', 'renata-lima.jpg', 'Sim', '', '', 'salao');

-- --------------------------------------------------------

--
-- Estrutura da tabela `usuarios_permissoes`
--

CREATE TABLE `usuarios_permissoes` (
  `id` int(11) NOT NULL,
  `usuario` int(11) NOT NULL,
  `permissao` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices para tabela `acessos`
--
ALTER TABLE `acessos`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `agendamentos`
--
ALTER TABLE `agendamentos`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `cargos`
--
ALTER TABLE `cargos`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `cat_produtos`
--
ALTER TABLE `cat_produtos`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `cat_servicos`
--
ALTER TABLE `cat_servicos`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `comentarios`
--
ALTER TABLE `comentarios`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `config`
--
ALTER TABLE `config`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `dias`
--
ALTER TABLE `dias`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `entradas`
--
ALTER TABLE `entradas`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `fornecedores`
--
ALTER TABLE `fornecedores`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `grupo_acessos`
--
ALTER TABLE `grupo_acessos`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `horarios`
--
ALTER TABLE `horarios`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `pagar`
--
ALTER TABLE `pagar`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `produtos`
--
ALTER TABLE `produtos`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `receber`
--
ALTER TABLE `receber`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `saidas`
--
ALTER TABLE `saidas`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `servicos`
--
ALTER TABLE `servicos`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `textos_index`
--
ALTER TABLE `textos_index`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `usuarios_permissoes`
--
ALTER TABLE `usuarios_permissoes`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `acessos`
--
ALTER TABLE `acessos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT de tabela `agendamentos`
--
ALTER TABLE `agendamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=118;

--
-- AUTO_INCREMENT de tabela `cargos`
--
ALTER TABLE `cargos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de tabela `cat_produtos`
--
ALTER TABLE `cat_produtos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `cat_servicos`
--
ALTER TABLE `cat_servicos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de tabela `comentarios`
--
ALTER TABLE `comentarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de tabela `config`
--
ALTER TABLE `config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `dias`
--
ALTER TABLE `dias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de tabela `entradas`
--
ALTER TABLE `entradas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `fornecedores`
--
ALTER TABLE `fornecedores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `grupo_acessos`
--
ALTER TABLE `grupo_acessos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `horarios`
--
ALTER TABLE `horarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT de tabela `pagar`
--
ALTER TABLE `pagar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT de tabela `produtos`
--
ALTER TABLE `produtos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `receber`
--
ALTER TABLE `receber`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=110;

--
-- AUTO_INCREMENT de tabela `saidas`
--
ALTER TABLE `saidas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `servicos`
--
ALTER TABLE `servicos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `textos_index`
--
ALTER TABLE `textos_index`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de tabela `usuarios_permissoes`
--
ALTER TABLE `usuarios_permissoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=287;

-- --------------------------------------------------------

--
-- Estrutura da tabela `config_salao`
-- Conteudo usado no site/login/painel quando o segmento for "salao"
--

CREATE TABLE `config_salao` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(50) NOT NULL,
  `img_banner_index` varchar(100) DEFAULT NULL,
  `imagem_sobre` varchar(100) DEFAULT NULL,
  `texto_sobre` varchar(600) DEFAULT NULL,
  `texto_rodape` varchar(255) DEFAULT NULL,
  `texto_agendamento` varchar(30) DEFAULT NULL,
  `whatsapp` varchar(20) DEFAULT NULL,
  `endereco` varchar(100) DEFAULT NULL,
  `instagram` varchar(100) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `icone_site` varchar(100) DEFAULT NULL,
  `logo` varchar(100) DEFAULT NULL,
  `logo_rel` varchar(100) DEFAULT NULL,
  `texto_fidelidade` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

INSERT INTO `config_salao` (`id`, `nome`, `img_banner_index`, `imagem_sobre`, `texto_sobre`, `texto_rodape`, `texto_agendamento`, `whatsapp`, `endereco`, `instagram`, `email`, `icone_site`, `logo`, `logo_rel`, `texto_fidelidade`) VALUES
(1, 'Salão Lumière', 'salao-banner.jpg', 'salao-sobre.jpg', 'O Salão Lumière é referência em beleza na região central de São Paulo. Cabelo, coloração, tratamentos e beleza das unhas com profissionais especializados, produtos premium e um atendimento acolhedor. Nosso compromisso é realçar a sua beleza com técnica, cuidado e sofisticação.', 'Salão Lumière - Beleza, cuidado e sofisticação. Agende seu horário e viva uma experiência premium!', 'Escolha seu Profissional', '(11) 98888-7777', 'Rua Oscar Freire, 900 - Jardins - São Paulo/SP', 'https://www.instagram.com/salaolumiere/', 'contato@salaolumiere.com.br', 'salao-favicon.png', 'logo-salao.png', 'logo-salao.png', 'Parabéns! Você completou seu cartão fidelidade. Seu próximo procedimento será por nossa conta!');

ALTER TABLE `config_salao`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Categorias, servicos e produtos do segmento "salao"
--

INSERT INTO `cat_servicos` (`nome`, `segmento`) VALUES
('Cabelo', 'salao'), ('Coloração', 'salao'), ('Unhas', 'salao'), ('Estética', 'salao');

INSERT INTO `cat_produtos` (`nome`, `segmento`) VALUES
('Shampoos', 'salao'), ('Tratamentos', 'salao'), ('Unhas', 'salao');

INSERT INTO `servicos` (`nome`, `categoria`, `valor`, `foto`, `dias_retorno`, `ativo`, `comissao`, `segmento`) VALUES
('Corte Feminino', (SELECT id FROM cat_servicos WHERE nome = 'Cabelo' AND segmento = 'salao'), '80.00', 'salao-corte-feminino.jpg', 45, 'Sim', '50.00', 'salao'),
('Coloração', (SELECT id FROM cat_servicos WHERE nome = 'Coloração' AND segmento = 'salao'), '220.00', 'salao-coloracao.jpg', 60, 'Sim', '50.00', 'salao'),
('Manicure', (SELECT id FROM cat_servicos WHERE nome = 'Unhas' AND segmento = 'salao'), '45.00', 'salao-manicure.jpg', 21, 'Sim', '50.00', 'salao');

INSERT INTO `comentarios` (`nome`, `texto`, `foto`, `ativo`, `segmento`) VALUES
('Ana Beatriz', 'Saí do salão renovada! A coloração ficou exatamente como eu queria e o atendimento é impecável.', 'salao-depoimento-1.jpg', 'Sim', 'salao'),
('Camila Rocha', 'Ambiente sofisticado e profissionais atenciosos. Minha manicure durou semanas perfeita.', 'salao-depoimento-2.jpg', 'Sim', 'salao'),
('Rodrigo Alves', 'Levo minha esposa e aproveito o cuidado com o cabelo também. Atendimento premium do início ao fim.', 'salao-depoimento-3.jpg', 'Sim', 'salao');

INSERT INTO `produtos` (`nome`, `descricao`, `categoria`, `valor_compra`, `valor_venda`, `estoque`, `foto`, `nivel_estoque`, `segmento`) VALUES
('Shampoo Profissional', 'Shampoo de alta performance para manutenção do cabelo no salão.', (SELECT id FROM cat_produtos WHERE nome = 'Shampoos' AND segmento = 'salao'), '25.00', '59.90', 12, 'salao-shampoo.jpg', 3, 'salao'),
('Esmalte Premium', 'Esmalte de longa duração em tom rosé.', (SELECT id FROM cat_produtos WHERE nome = 'Unhas' AND segmento = 'salao'), '8.00', '24.90', 20, 'salao-esmalte.jpg', 5, 'salao');

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- --------------------------------------------------------

--
-- Estrutura do painel SaaS (super admin)
-- O primeiro super admin deve ser criado manualmente (senha com password_hash)
--

CREATE TABLE IF NOT EXISTS `super_admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(80) NOT NULL,
  `email` varchar(120) NOT NULL,
  `senha_crip` varchar(255) NOT NULL,
  `ativo` varchar(5) NOT NULL DEFAULT 'Sim',
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `saas_planos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(60) NOT NULL,
  `valor` decimal(10,2) NOT NULL DEFAULT 0.00,
  `limite_profissionais` int(11) NOT NULL DEFAULT 0,
  `limite_agendamentos` int(11) NOT NULL DEFAULT 0,
  `ativo` varchar(5) NOT NULL DEFAULT 'Sim',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `saas_planos` (`id`, `nome`, `valor`, `limite_profissionais`, `limite_agendamentos`, `ativo`) VALUES
(1, 'Grátis', 0.00, 0, 20, 'Sim'),
(2, 'PRO', 49.00, 0, 0, 'Sim');

CREATE TABLE IF NOT EXISTS `tenants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(40) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(120) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `plano_id` int(11) DEFAULT NULL,
  `status` varchar(12) NOT NULL DEFAULT 'trial',
  `vencimento` date DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `db_name` varchar(64) DEFAULT NULL,
  `site_demo` tinyint(1) NOT NULL DEFAULT 0,
  `boas_vindas_em` datetime DEFAULT NULL,
  `schema_version` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_plano` (`plano_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `tenant_dominios` (
  `hostname` varchar(120) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `tipo` varchar(12) NOT NULL DEFAULT 'subdominio',
  `token_verificacao` varchar(64) DEFAULT NULL,
  `verificado_em` datetime DEFAULT NULL,
  `registrado_em` datetime DEFAULT NULL,
  PRIMARY KEY (`hostname`),
  KEY `idx_tenant` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `saas_pagamentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `referencia` varchar(20) NOT NULL,
  `status` varchar(12) NOT NULL DEFAULT 'pendente',
  `pago_em` datetime DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_tenant` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
