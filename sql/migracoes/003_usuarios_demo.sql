-- Conta de demonstracao no painel.
-- A conta demo enxerga o sistema como Administrador, mas nao escreve
-- (salvar, excluir, inserir, baixar, assinar plano). A coluna marca quem e demo.
SET @bb_col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'demo');
SET @bb_sql := IF(@bb_col = 0, 'ALTER TABLE `usuarios` ADD COLUMN `demo` TINYINT(1) NOT NULL DEFAULT 0', 'SET @bb_col := @bb_col');
PREPARE bb_stmt FROM @bb_sql;
EXECUTE bb_stmt;
DEALLOCATE PREPARE bb_stmt;
