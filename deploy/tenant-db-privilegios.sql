-- ============================================================
-- PRIVILEGIOS DO USUARIO DE CONTROLE PARA PROVISIONAR TENANTS
-- ------------------------------------------------------------
-- O usuario de controle (`barbearia`) enxerga os bancos por GRANT em
-- schema, mas nao tem privilegio GLOBAL. Por isso
-- bb_tenant_db_criar_usuario falha e o provisionamento segue com o aviso
-- "usuario MySQL do estabelecimento nao criado".
--
-- Faltam exatamente dois privilegios (medidos na VPS em 2026-11-27,
-- MariaDB 11.8.9):
--
--   1. CREATE USER ON *.*
--      sem ele:  ERROR 1227 (42000) Access denied; you need (at least
--                one of) the CREATE USER privilege(s) for this operation
--      usado por: CREATE USER, ALTER USER (senha derivada) e
--                 REVOKE ALL PRIVILEGES, GRANT OPTION
--
--   2. GRANT OPTION no schema de cada tenant
--      sem ele:  ERROR 1044 (42000) Access denied for user
--                'barbearia'@'localhost' to database 'barbearia_tN'
--      usado por: GRANT <privilegios do painel> ON `barbearia_tN`.*
--
-- O escopo continua fechado: fora de `barbearia_t%` o GRANT segue negado
-- (1044), e nao ha privilegio global de leitura/escrita em nenhum banco.
--
-- Aplicar uma vez, como root do MySQL (socket, sem senha):
--
--   mysql < deploy/tenant-db-privilegios.sql
--
-- Depois provisionar os tenants pendentes:
--
--   php /var/www/beauty/scripts/tenant-db-usuarios.php --aplicar \
--        --fpm-conf=/etc/php/8.4/fpm/pool.d/www.conf --verificar
--
-- Idempotente: rodar de novo so reafirma os mesmos GRANTs.
-- ============================================================

GRANT CREATE USER ON *.* TO 'barbearia'@'localhost';

GRANT ALL PRIVILEGES ON `barbearia\_t%`.* TO 'barbearia'@'localhost' WITH GRANT OPTION;

FLUSH PRIVILEGES;
