-- Segmento do estabelecimento no cadastro do SaaS.
-- tenants.segmento guarda 'barbearia' ou 'salao'; o provisionamento copia o
-- valor para config.segmento do banco do cliente e cria a identidade do tema
-- salao (config_salao) quando for o caso.
-- Bancos de estabelecimento nao tem a tabela tenants: aqui vira no-op.
SET @bb_tem_tenants := (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tenants');
SET @bb_col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tenants' AND COLUMN_NAME = 'segmento');
SET @bb_sql := IF(@bb_tem_tenants > 0 AND @bb_col = 0, 'ALTER TABLE `tenants` ADD COLUMN `segmento` VARCHAR(20) NOT NULL DEFAULT ''barbearia''', 'DO 0');
PREPARE bb_stmt FROM @bb_sql;
EXECUTE bb_stmt;
DEALLOCATE PREPARE bb_stmt;
