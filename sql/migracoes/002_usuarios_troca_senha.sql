-- Coluna de troca obrigatoria de senha (sunset do MD5 legado).
-- Faltava no schema canonico, o que quebrava o painel de clientes novos.
SET @bb_col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'precisa_trocar_senha');
SET @bb_sql := IF(@bb_col = 0, 'ALTER TABLE `usuarios` ADD COLUMN `precisa_trocar_senha` TINYINT(1) NOT NULL DEFAULT 0', 'SET @bb_col := @bb_col');
PREPARE bb_stmt FROM @bb_sql;
EXECUTE bb_stmt;
DEALLOCATE PREPARE bb_stmt;
