-- Credencial MySQL exclusiva de cada estabelecimento.
-- tenants.db_user guarda o nome do usuario criado (bb_t<id>); a senha e
-- derivada da chave do sistema e nunca e gravada. Vazio = ainda usa a
-- credencial compartilhada do banco de controle.
-- Bancos de estabelecimento nao tem a tabela tenants: aqui vira no-op.
SET @bb_tem_tenants := (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tenants');
SET @bb_col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tenants' AND COLUMN_NAME = 'db_user');
SET @bb_sql := IF(@bb_tem_tenants > 0 AND @bb_col = 0, 'ALTER TABLE `tenants` ADD COLUMN `db_user` VARCHAR(64) NULL DEFAULT NULL', 'DO 0');
PREPARE bb_stmt FROM @bb_sql;
EXECUTE bb_stmt;
DEALLOCATE PREPARE bb_stmt;
