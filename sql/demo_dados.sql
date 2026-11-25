-- ============================================================
-- DADOS FICTICIOS DA DEMONSTRACAO
-- ------------------------------------------------------------
-- Semente do banco DB_DEMO_NAME. Nenhum registro daqui vem do
-- banco de controle nem de cliente real: nomes, telefones,
-- enderecos e valores sao inventados.
--
-- Aplicado por sistema/demo-banco.php na primeira criacao.
-- INSERT IGNORE + ids fixos deixam a carga repetivel.
-- ============================================================

INSERT IGNORE INTO `cargos` (`id`, `nome`) VALUES
(1, 'Administrador'),
(2, 'Barbeiro'),
(3, 'Recepcionista'),
(4, 'Gerente');

INSERT IGNORE INTO `grupo_acessos` (`id`, `nome`) VALUES
(1, 'Pessoas'),
(2, 'Cadastros'),
(3, 'Produtos'),
(4, 'Financeiro'),
(5, 'Agendamento / Serviços'),
(6, 'Vendas'),
(7, 'Relatórios'),
(8, 'Dados Site');

INSERT IGNORE INTO `acessos` (`id`, `nome`, `chave`, `grupo`) VALUES
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

INSERT IGNORE INTO `config` (`id`, `nome`, `email`, `telefone_fixo`, `telefone_whatsapp`, `endereco`, `logo`, `icone`, `logo_rel`, `tipo_rel`, `instagram`, `tipo_comissao`, `texto_rodape`, `img_banner_index`, `texto_sobre`, `imagem_sobre`, `icone_site`, `mapa`, `texto_fidelidade`, `quantidade_cartoes`, `texto_agendamento`, `msg_agendamento`, `segmento`) VALUES
(1, 'Barbearia PRO', 'contato@exemplo.com.br', '(11) 3000-0000', '(11) 90000-0000', 'Rua Exemplo, 100 - Centro - Cidade Exemplo', 'logo.png', 'favicon.png', 'logo_rel.jpg', 'PDF', '', 'Porcentagem', 'Dados fictícios para demonstração. Nenhum registro real é exibido.', 'banner.jpg', 'Esta é a demonstração do Barbearia PRO. Todos os clientes, agendamentos, produtos e valores desta página são fictícios, criados apenas para apresentar o sistema.', 'foto_barbearia.jpg', 'favicon.png', NULL, 'A cada 10 serviços, ganhe 1!', 10, 'Escolha seu Barbeiro', 'Sim', 'barbearia');

INSERT IGNORE INTO `usuarios` (`id`, `nome`, `email`, `cpf`, `senha`, `senha_crip`, `nivel`, `data`, `ativo`, `telefone`, `endereco`, `foto`, `atendimento`, `tipo_chave`, `chave_pix`, `segmento`, `precisa_trocar_senha`, `demo`) VALUES
(1, 'Demonstração', 'demo@exemplo.com.br', '', '', '!', 'Administrador', DATE_SUB(CURDATE(), INTERVAL 120 DAY), 'Sim', '(11) 90000-0000', 'Rua Exemplo, 100 - Centro', 'sem-foto.jpg', 'Não', '', '', '', 0, 1),
(2, 'Rafael Moreira', 'rafael@exemplo.com.br', '000.000.000-01', '', '!', 'Barbeiro', DATE_SUB(CURDATE(), INTERVAL 90 DAY), 'Sim', '(11) 90000-0001', 'Rua Exemplo, 101 - Centro', 'sem-foto.jpg', 'Sim', '', '', 'ambos', 0, 0),
(3, 'Bruno Carvalho', 'bruno@exemplo.com.br', '000.000.000-02', '', '!', 'Barbeiro', DATE_SUB(CURDATE(), INTERVAL 80 DAY), 'Sim', '(11) 90000-0002', 'Rua Exemplo, 102 - Centro', 'sem-foto.jpg', 'Sim', '', '', 'ambos', 0, 0),
(4, 'Camila Rocha', 'camila@exemplo.com.br', '000.000.000-03', '', '!', 'Recepcionista', DATE_SUB(CURDATE(), INTERVAL 60 DAY), 'Sim', '(11) 90000-0003', 'Rua Exemplo, 103 - Centro', 'sem-foto.jpg', 'Não', '', '', '', 0, 0);

INSERT IGNORE INTO `usuarios_permissoes` (`id`, `usuario`, `permissao`) VALUES
(1, 2, 20), (2, 2, 21), (3, 2, 22),
(4, 3, 20), (5, 3, 21), (6, 3, 22),
(7, 4, 1), (8, 4, 3), (9, 4, 20), (10, 4, 22);

INSERT IGNORE INTO `cat_servicos` (`id`, `nome`, `segmento`) VALUES
(1, 'Corte', 'barbearia'),
(2, 'Barba', 'barbearia'),
(3, 'Tratamentos', 'barbearia');

INSERT IGNORE INTO `servicos` (`id`, `nome`, `categoria`, `valor`, `foto`, `dias_retorno`, `ativo`, `comissao`, `segmento`) VALUES
(1, 'Corte Tradicional', 1, '35.00', '27-08-2025-20-53-30-corte.png', 15, 'Sim', '10.00', 'barbearia'),
(2, 'Barba Completa', 2, '30.00', '27-08-2025-20-53-41-barba.png', 7, 'Sim', '8.00', 'barbearia'),
(3, 'Corte + Barba', 1, '60.00', '27-08-2025-20-53-48-corte_barba.png', 15, 'Sim', '15.00', 'barbearia'),
(4, 'Corte Degradê', 1, '40.00', '27-08-2025-20-54-57-degrade.png', 15, 'Sim', '12.00', 'barbearia'),
(5, 'Barba + Bigode', 2, '35.00', '27-08-2025-20-55-06-barba_bigode.png', 7, 'Sim', '10.00', 'barbearia'),
(6, 'Tratamento Capilar', 3, '80.00', '27-08-2025-21-02-14-tratamento_capilar.png', 30, 'Sim', '20.00', 'barbearia');

INSERT IGNORE INTO `cat_produtos` (`id`, `nome`, `segmento`) VALUES
(1, 'Pomadas', 'barbearia'),
(2, 'Cremes', 'barbearia'),
(3, 'Shampoos', 'barbearia');

INSERT IGNORE INTO `produtos` (`id`, `nome`, `descricao`, `categoria`, `valor_compra`, `valor_venda`, `estoque`, `foto`, `nivel_estoque`, `segmento`) VALUES
(1, 'Pomada Modeladora', 'Pomada de fixação média para modelagem', 1, '15.00', '45.00', 30, '27-08-2025-20-33-52-pomada.png', 10, 'barbearia'),
(2, 'Creme Hidratante Barba', 'Creme nutritivo para hidratação da barba', 2, '12.00', '38.00', 22, '27-08-2025-20-48-46-creme.png', 8, 'barbearia'),
(3, 'Gel Fixador', 'Gel com fixação forte e brilho natural', 1, '8.00', '25.00', 40, '27-08-2025-20-39-25-gel.png', 12, 'barbearia'),
(4, 'Shampoo Anticaspa', 'Shampoo para tratamento anticaspa', 3, '18.00', '55.00', 18, '27-08-2025-20-38-08-shampoo.png', 5, 'barbearia'),
(5, 'Óleo para Barba', 'Óleo natural para hidratação da barba', 2, '25.00', '75.00', 12, '27-08-2025-20-34-52-oleo_barba.png', 5, 'barbearia'),
(6, 'Água Mineral', 'Água mineral 500ml para clientes', 3, '1.00', '3.00', 80, '27-08-2025-20-40-45-agua.png', 15, 'barbearia');

INSERT IGNORE INTO `fornecedores` (`id`, `nome`, `telefone`, `endereco`, `data_cad`, `tipo_chave`, `chave_pix`) VALUES
(1, 'Distribuidora Exemplo', '(11) 3000-0011', 'Rua Exemplo, 200 - Centro', DATE_SUB(CURDATE(), INTERVAL 150 DAY), 'CNPJ', '00.000.000/0001-00'),
(2, 'Cosméticos Exemplo', '(11) 3000-0022', 'Rua Exemplo, 201 - Centro', DATE_SUB(CURDATE(), INTERVAL 120 DAY), 'Email', 'contato@exemplo.com.br');

INSERT IGNORE INTO `clientes` (`id`, `nome`, `telefone`, `endereco`, `data_nasc`, `data_cad`, `cartoes`, `data_retorno`, `ultimo_servico`, `alertado`) VALUES
(1, 'Lucas Andrade', '(11) 90000-0101', 'Rua Exemplo, 301 - Centro', '1990-02-14', DATE_SUB(CURDATE(), INTERVAL 100 DAY), 6, DATE_ADD(CURDATE(), INTERVAL 10 DAY), 1, 'Não'),
(2, 'Rafael Nunes', '(11) 90000-0102', 'Rua Exemplo, 302 - Centro', '1988-06-03', DATE_SUB(CURDATE(), INTERVAL 95 DAY), 4, DATE_ADD(CURDATE(), INTERVAL 7 DAY), 2, 'Não'),
(3, 'Diego Martins', '(11) 90000-0103', 'Rua Exemplo, 303 - Centro', '1992-11-21', DATE_SUB(CURDATE(), INTERVAL 80 DAY), 2, DATE_ADD(CURDATE(), INTERVAL 3 DAY), 3, 'Não'),
(4, 'Thiago Alves', '(11) 90000-0104', 'Rua Exemplo, 304 - Centro', '1985-04-09', DATE_SUB(CURDATE(), INTERVAL 70 DAY), 8, DATE_ADD(CURDATE(), INTERVAL 12 DAY), 1, 'Não'),
(5, 'Marcelo Pires', '(11) 90000-0105', 'Rua Exemplo, 305 - Centro', '1994-08-30', DATE_SUB(CURDATE(), INTERVAL 55 DAY), 1, DATE_ADD(CURDATE(), INTERVAL 15 DAY), 4, 'Sim'),
(6, 'Felipe Rocha', '(11) 90000-0106', 'Rua Exemplo, 306 - Centro', '1991-12-12', DATE_SUB(CURDATE(), INTERVAL 40 DAY), 3, DATE_ADD(CURDATE(), INTERVAL 5 DAY), 2, 'Não'),
(7, 'André Lima', '(11) 90000-0107', 'Rua Exemplo, 307 - Centro', '1987-01-25', DATE_SUB(CURDATE(), INTERVAL 25 DAY), 0, DATE_ADD(CURDATE(), INTERVAL 9 DAY), 0, 'Não'),
(8, 'Gustavo Freitas', '(11) 90000-0108', 'Rua Exemplo, 308 - Centro', '1996-05-18', DATE_SUB(CURDATE(), INTERVAL 12 DAY), 2, DATE_ADD(CURDATE(), INTERVAL 20 DAY), 5, 'Não');

INSERT IGNORE INTO `dias` (`id`, `dia`, `funcionario`) VALUES
(1, 'Terça-Feira', 2), (2, 'Quarta-Feira', 2), (3, 'Quinta-Feira', 2), (4, 'Sexta-Feira', 2), (5, 'Sábado', 2),
(6, 'Terça-Feira', 3), (7, 'Quarta-Feira', 3), (8, 'Quinta-Feira', 3), (9, 'Sexta-Feira', 3), (10, 'Sábado', 3);

INSERT IGNORE INTO `horarios` (`id`, `horario`, `funcionario`) VALUES
(1, '09:00:00', 2), (2, '10:00:00', 2), (3, '11:00:00', 2), (4, '13:00:00', 2), (5, '14:00:00', 2), (6, '15:00:00', 2), (7, '16:00:00', 2), (8, '17:00:00', 2),
(9, '09:00:00', 3), (10, '10:00:00', 3), (11, '11:00:00', 3), (12, '13:00:00', 3), (13, '14:00:00', 3), (14, '15:00:00', 3), (15, '16:00:00', 3), (16, '17:00:00', 3);

INSERT IGNORE INTO `agendamentos` (`id`, `funcionario`, `cliente`, `data`, `hora`, `obs`, `data_lanc`, `usuario`, `status`, `servico`, `servicos`) VALUES
(1, 2, 1, DATE_SUB(CURDATE(), INTERVAL 3 DAY), '09:00:00', 'Cliente fictício', DATE_SUB(CURDATE(), INTERVAL 4 DAY), 1, 'Finalizado', 1, '1'),
(2, 3, 2, DATE_SUB(CURDATE(), INTERVAL 2 DAY), '10:00:00', NULL, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 1, 'Finalizado', 2, '2'),
(3, 2, 3, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '14:00:00', 'Primeira visita', DATE_SUB(CURDATE(), INTERVAL 2 DAY), 1, 'Finalizado', 3, '3'),
(4, 3, 4, CURDATE(), '09:00:00', NULL, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 1, 'Agendado', 1, '1'),
(5, 2, 5, CURDATE(), '11:00:00', 'Cliente fictício', DATE_SUB(CURDATE(), INTERVAL 1 DAY), 1, 'Agendado', 3, '3'),
(6, 2, 6, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '10:00:00', NULL, CURDATE(), 1, 'Agendado', 2, '2'),
(7, 3, 7, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '15:00:00', NULL, CURDATE(), 1, 'Agendado', 1, '1'),
(8, 3, 8, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '16:00:00', 'Retorno', CURDATE(), 1, 'Agendado', 5, '5'),
(9, 2, 2, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '13:00:00', NULL, CURDATE(), 1, 'Agendado', 6, '6');

INSERT IGNORE INTO `receber` (`id`, `descricao`, `tipo`, `valor`, `data_lanc`, `data_venc`, `data_pgto`, `usuario_lanc`, `usuario_baixa`, `foto`, `pessoa`, `pago`, `produto`, `quantidade`, `servico`, `funcionario`) VALUES
(1, 'Corte - Lucas Andrade', 'Serviço', '35.00', DATE_SUB(CURDATE(), INTERVAL 3 DAY), DATE_SUB(CURDATE(), INTERVAL 3 DAY), DATE_SUB(CURDATE(), INTERVAL 3 DAY), 1, 1, NULL, 1, 'Sim', 0, 0, 1, 2),
(2, 'Barba - Rafael Nunes', 'Serviço', '30.00', DATE_SUB(CURDATE(), INTERVAL 2 DAY), DATE_SUB(CURDATE(), INTERVAL 2 DAY), DATE_SUB(CURDATE(), INTERVAL 2 DAY), 1, 1, NULL, 2, 'Sim', 0, 0, 2, 3),
(3, 'Corte + Barba - Diego Martins', 'Serviço', '60.00', DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_SUB(CURDATE(), INTERVAL 1 DAY), 1, 1, NULL, 3, 'Sim', 0, 0, 3, 2),
(4, 'Pomada - Thiago Alves', 'Produto', '45.00', CURDATE(), CURDATE(), CURDATE(), 1, 1, NULL, 4, 'Sim', 1, 1, 0, 0),
(5, 'Corte - Felipe Rocha', 'Serviço', '35.00', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 5 DAY), CURDATE(), 1, 0, NULL, 6, 'Não', 0, 0, 1, 3);

INSERT IGNORE INTO `pagar` (`id`, `descricao`, `tipo`, `valor`, `data_lanc`, `data_venc`, `data_pgto`, `usuario_lanc`, `usuario_baixa`, `foto`, `pessoa`, `pago`, `produto`, `quantidade`, `funcionario`, `servico`, `cliente`) VALUES
(1, 'Aluguel do espaço', 'Aluguel', '2500.00', DATE_SUB(CURDATE(), INTERVAL 10 DAY), DATE_ADD(CURDATE(), INTERVAL 5 DAY), CURDATE(), 1, 1, NULL, 0, 'Sim', 0, 0, 0, 0, 0),
(2, 'Energia elétrica', 'Conta', '320.00', DATE_SUB(CURDATE(), INTERVAL 8 DAY), DATE_ADD(CURDATE(), INTERVAL 7 DAY), CURDATE(), 1, 0, NULL, 0, 'Não', 0, 0, 0, 0, 0),
(3, 'Reposição de produtos', 'Compra', '680.00', DATE_SUB(CURDATE(), INTERVAL 5 DAY), DATE_ADD(CURDATE(), INTERVAL 2 DAY), CURDATE(), 1, 1, NULL, 1, 'Sim', 1, 20, 0, 0, 0),
(4, 'Comissão - Rafael Moreira', 'Comissão', '180.00', DATE_SUB(CURDATE(), INTERVAL 2 DAY), DATE_ADD(CURDATE(), INTERVAL 3 DAY), CURDATE(), 1, 0, NULL, 2, 'Não', 0, 0, 2, 0, 0);

INSERT IGNORE INTO `entradas` (`id`, `produto`, `quantidade`, `motivo`, `usuario`, `data`) VALUES
(1, 1, 20, 'Compra de estoque', 1, DATE_SUB(CURDATE(), INTERVAL 5 DAY)),
(2, 2, 15, 'Reposição de estoque', 1, DATE_SUB(CURDATE(), INTERVAL 4 DAY)),
(3, 4, 10, 'Compra mensal', 1, DATE_SUB(CURDATE(), INTERVAL 2 DAY));

INSERT IGNORE INTO `saidas` (`id`, `produto`, `quantidade`, `motivo`, `usuario`, `data`) VALUES
(1, 1, 1, 'Venda para cliente', 1, DATE_SUB(CURDATE(), INTERVAL 2 DAY)),
(2, 3, 2, 'Uso interno', 1, DATE_SUB(CURDATE(), INTERVAL 1 DAY)),
(3, 6, 3, 'Consumo interno', 1, CURDATE());

INSERT IGNORE INTO `textos_index` (`id`, `titulo`, `descricao`, `segmento`) VALUES
(1, 'Cortes Profissionais', 'Cortes clássicos e modernos feitos por profissionais experientes, com atenção a cada detalhe.', 'barbearia'),
(2, 'Barba Perfeita', 'Barba feita na navalha, com toalha quente e produtos de qualidade para um acabamento impecável.', 'barbearia'),
(3, 'Ambiente Confortável', 'Um espaço pensado para você relaxar e sair com o visual renovado.', 'barbearia'),
(4, 'Atendimento Premium', 'Atendimento personalizado, com horário marcado e sem espera.', 'barbearia');

INSERT IGNORE INTO `comentarios` (`id`, `nome`, `texto`, `foto`, `ativo`, `segmento`) VALUES
(1, 'Lucas Andrade', 'Atendimento excelente e corte impecável. Recomendo!', NULL, 'Sim', 'barbearia'),
(2, 'Rafael Nunes', 'Ambiente agradável e profissionais muito atenciosos.', NULL, 'Sim', 'barbearia'),
(3, 'Diego Martins', 'Sempre saio satisfeito. Horário marcado e sem espera.', NULL, 'Sim', 'barbearia');
