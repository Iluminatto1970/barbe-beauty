-- Historico de migracoes aplicadas neste banco.
-- Aplicada automaticamente por app/sistema/migrar.php.
CREATE TABLE IF NOT EXISTS `schema_versoes` (
  `versao` int unsigned NOT NULL,
  `arquivo` varchar(120) NOT NULL,
  `aplicada_em` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`versao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
