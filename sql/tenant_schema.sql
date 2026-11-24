-- ============================================================
-- SCHEMA CANONICO DO ESTABELECIMENTO (tenant)
-- ------------------------------------------------------------
-- Este arquivo e a UNICA fonte da estrutura criada quando um
-- novo estabelecimento e provisionado (bb_tenant_provisionar).
-- Nao usa o banco de controle como modelo: a estrutura fica no
-- git, revisavel, e nao depende de dados de um cliente real.
--
-- Regenerar depois de mudancas de schema:
--   mariadb-dump --no-data --skip-add-drop-table --skip-comments \
--     --compact <banco> <tabelas> | sed -e 's/^CREATE TABLE `/CREATE TABLE IF NOT EXISTS `/' \
--     -e 's/ AUTO_INCREMENT=[0-9]*//' >> sql/tenant_schema.sql
--
-- Toda mudanca de estrutura DEVE entrar tambem em
-- sql/migracoes/NNN_descricao.sql (idempotente), para chegar aos
-- bancos que ja existem. Ver app/sistema/migracoes.php.
-- ============================================================
CREATE TABLE IF NOT EXISTS `acessos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(50) NOT NULL,
  `chave` varchar(50) NOT NULL,
  `grupo` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
CREATE TABLE IF NOT EXISTS `agendamentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `funcionario` int(11) NOT NULL,
  `cliente` int(11) NOT NULL,
  `data` date NOT NULL,
  `hora` time NOT NULL,
  `obs` varchar(100) DEFAULT NULL,
  `data_lanc` date NOT NULL,
  `usuario` int(11) NOT NULL,
  `status` varchar(20) NOT NULL,
  `servico` int(11) NOT NULL,
  `servicos` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
CREATE TABLE IF NOT EXISTS `cargos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(35) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
CREATE TABLE IF NOT EXISTS `cat_produtos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(50) NOT NULL,
  `segmento` varchar(20) NOT NULL DEFAULT 'barbearia',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
CREATE TABLE IF NOT EXISTS `cat_servicos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(50) NOT NULL,
  `segmento` varchar(20) NOT NULL DEFAULT 'barbearia',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
CREATE TABLE IF NOT EXISTS `clientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(50) NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `endereco` varchar(100) DEFAULT NULL,
  `data_nasc` date DEFAULT NULL,
  `data_cad` date NOT NULL,
  `cartoes` int(11) NOT NULL,
  `data_retorno` date DEFAULT NULL,
  `ultimo_servico` int(11) NOT NULL DEFAULT 0,
  `alertado` varchar(5) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
CREATE TABLE IF NOT EXISTS `comentarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(50) NOT NULL,
  `texto` varchar(500) NOT NULL,
  `foto` varchar(100) DEFAULT NULL,
  `ativo` varchar(5) NOT NULL,
  `segmento` varchar(20) NOT NULL DEFAULT 'barbearia',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
CREATE TABLE IF NOT EXISTS `config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `segmento` varchar(20) NOT NULL DEFAULT 'barbearia',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
CREATE TABLE IF NOT EXISTS `config_salao` (
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
  `icone_site` varchar(100) DEFAULT NULL,
  `logo` varchar(100) DEFAULT NULL,
  `logo_rel` varchar(100) DEFAULT NULL,
  `texto_fidelidade` varchar(255) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
CREATE TABLE IF NOT EXISTS `dias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dia` varchar(25) NOT NULL,
  `funcionario` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
CREATE TABLE IF NOT EXISTS `entradas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `produto` int(11) NOT NULL,
  `quantidade` int(11) NOT NULL,
  `motivo` varchar(50) NOT NULL,
  `usuario` int(11) NOT NULL,
  `data` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
CREATE TABLE IF NOT EXISTS `fornecedores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(50) NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `endereco` varchar(100) DEFAULT NULL,
  `data_cad` date NOT NULL,
  `tipo_chave` varchar(25) DEFAULT NULL,
  `chave_pix` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
CREATE TABLE IF NOT EXISTS `grupo_acessos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
CREATE TABLE IF NOT EXISTS `horarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `horario` time NOT NULL,
  `funcionario` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
CREATE TABLE IF NOT EXISTS `pagar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `cliente` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
CREATE TABLE IF NOT EXISTS `produtos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(50) NOT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `categoria` int(11) NOT NULL,
  `valor_compra` decimal(8,2) NOT NULL,
  `valor_venda` decimal(8,2) NOT NULL,
  `estoque` int(11) NOT NULL,
  `foto` varchar(100) DEFAULT NULL,
  `nivel_estoque` int(11) NOT NULL,
  `segmento` varchar(20) NOT NULL DEFAULT 'barbearia',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
CREATE TABLE IF NOT EXISTS `receber` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `funcionario` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
CREATE TABLE IF NOT EXISTS `saidas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `produto` int(11) NOT NULL,
  `quantidade` int(11) NOT NULL,
  `motivo` varchar(50) NOT NULL,
  `usuario` int(11) NOT NULL,
  `data` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
CREATE TABLE IF NOT EXISTS `servicos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(50) NOT NULL,
  `categoria` int(11) NOT NULL,
  `valor` decimal(8,2) NOT NULL,
  `foto` varchar(100) DEFAULT NULL,
  `dias_retorno` int(11) NOT NULL,
  `ativo` varchar(5) NOT NULL,
  `comissao` decimal(8,2) NOT NULL,
  `segmento` varchar(20) NOT NULL DEFAULT 'barbearia',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
CREATE TABLE IF NOT EXISTS `textos_index` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(25) NOT NULL,
  `descricao` varchar(255) NOT NULL,
  `segmento` varchar(20) NOT NULL DEFAULT 'barbearia',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
CREATE TABLE IF NOT EXISTS `usuarios_permissoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario` int(11) NOT NULL,
  `permissao` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
