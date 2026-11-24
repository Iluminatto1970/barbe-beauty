-- Agenda publica cadastra o cliente apenas com nome e telefone.
-- Nascimento e ultimo servico precisam ser opcionais.
ALTER TABLE `clientes` MODIFY `data_nasc` date DEFAULT NULL;
ALTER TABLE `clientes` MODIFY `ultimo_servico` int(11) NOT NULL DEFAULT 0;
UPDATE `clientes` SET `data_nasc` = NULL WHERE `data_nasc` = '0000-00-00';
