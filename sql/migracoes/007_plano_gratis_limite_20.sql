-- Plano gratuito passa a liberar 20 agendamentos por mes (antes 30).
-- A tabela saas_planos existe apenas no banco de controle; nos bancos de
-- estabelecimento o UPDATE e ignorado (DO 0), para a migracao nao falhar.
SET @bb_tem_planos := (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'saas_planos');
SET @bb_sql := IF(@bb_tem_planos > 0, 'UPDATE saas_planos SET limite_agendamentos = 20 WHERE valor = 0 AND limite_agendamentos = 30', 'DO 0');
PREPARE bb_stmt FROM @bb_sql;
EXECUTE bb_stmt;
DEALLOCATE PREPARE bb_stmt;
