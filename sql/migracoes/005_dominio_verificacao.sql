-- Verificacao de propriedade do dominio proprio.
-- Host tipo 'proprio' em tenant_dominios nasce com verificado_em NULL e
-- so passa a servir quando a verificacao DNS (TXT/CNAME) concluir.
-- Bancos de estabelecimento nao tem tenant_dominios: aqui vira no-op.
SET @bb_tabela := (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tenant_dominios');
SET @bb_col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tenant_dominios' AND COLUMN_NAME = 'token_verificacao');
SET @bb_sql := IF(@bb_tabela > 0 AND @bb_col = 0, 'ALTER TABLE `tenant_dominios` ADD COLUMN `token_verificacao` VARCHAR(64) NULL DEFAULT NULL', 'DO 0');
PREPARE bb_stmt FROM @bb_sql;
EXECUTE bb_stmt;
DEALLOCATE PREPARE bb_stmt;
SET @bb_col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tenant_dominios' AND COLUMN_NAME = 'registrado_em');
SET @bb_sql := IF(@bb_tabela > 0 AND @bb_col = 0, 'ALTER TABLE `tenant_dominios` ADD COLUMN `registrado_em` DATETIME NULL DEFAULT NULL', 'DO 0');
PREPARE bb_stmt FROM @bb_sql;
EXECUTE bb_stmt;
DEALLOCATE PREPARE bb_stmt;
