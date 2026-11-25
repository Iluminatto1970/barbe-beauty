<?php
// ============================================================
// RECUPERACAO DE SENHA — tokens de uso unico
// ------------------------------------------------------------
// O token vai no link (32 bytes aleatorios em hex). No banco
// fica apenas o SHA-256, entao um vazamento da tabela nao
// permite redefinir senha de ninguem.
//
// Cada pedido invalida os links anteriores do usuario, o token
// expira em 1 hora (24 horas quando gerado pelo suporte) e
// deixa de valer no primeiro uso.
// ============================================================

if (!function_exists('bb_rec_garantir_tabela')) {
    function bb_rec_garantir_tabela($pdo)
    {
        static $ok = false;
        if ($ok) {
            return true;
        }
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS usuarios_recuperacao (
                id INT NOT NULL AUTO_INCREMENT,
                usuario_id INT NOT NULL,
                token_hash CHAR(64) NOT NULL,
                expira_em DATETIME NOT NULL,
                usado_em DATETIME NULL DEFAULT NULL,
                criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                ip VARCHAR(45) NULL DEFAULT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY token_hash (token_hash),
                KEY usuario_id (usuario_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
            $ok = true;
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}

if (!function_exists('bb_rec_criar')) {
    // Cria um token para o usuario. Retorna array('token' => ..., 'expira' => ...)
    function bb_rec_criar($pdo, $usuario_id, $horas = 1)
    {
        $usuario_id = (int) $usuario_id;
        if ($usuario_id <= 0 || !bb_rec_garantir_tabela($pdo)) {
            return false;
        }

        $horas = max(1, (int) $horas);
        $token = bin2hex(random_bytes(32));
        $expira = date('Y-m-d H:i:s', time() + $horas * 3600);

        try {
            // Um link valido por vez: descarta os anteriores e os vencidos
            $pdo->prepare("DELETE FROM usuarios_recuperacao WHERE usuario_id = :u")
                ->execute(array(':u' => $usuario_id));
            $pdo->exec("DELETE FROM usuarios_recuperacao WHERE expira_em < NOW() OR usado_em IS NOT NULL");

            $ip = isset($_SERVER['REMOTE_ADDR']) ? substr((string) $_SERVER['REMOTE_ADDR'], 0, 45) : null;
            $pdo->prepare("INSERT INTO usuarios_recuperacao (usuario_id, token_hash, expira_em, ip) VALUES (:u, :h, :e, :ip)")
                ->execute(array(
                    ':u'  => $usuario_id,
                    ':h'  => hash('sha256', $token),
                    ':e'  => $expira,
                    ':ip' => $ip,
                ));
        } catch (Exception $e) {
            return false;
        }

        return array('token' => $token, 'expira' => $expira);
    }
}

if (!function_exists('bb_rec_buscar')) {
    // Registro valido (nao usado e no prazo) para o token informado
    function bb_rec_buscar($pdo, $token)
    {
        $token = trim((string) $token);
        if (!preg_match('/^[a-f0-9]{64}$/', $token) || !bb_rec_garantir_tabela($pdo)) {
            return false;
        }
        try {
            $q = $pdo->prepare("SELECT r.*, u.nome, u.email
                FROM usuarios_recuperacao r
                INNER JOIN usuarios u ON u.id = r.usuario_id
                WHERE r.token_hash = :h AND r.usado_em IS NULL AND r.expira_em >= NOW()
                LIMIT 1");
            $q->execute(array(':h' => hash('sha256', $token)));
            $linha = $q->fetch(PDO::FETCH_ASSOC);
            return $linha ? $linha : false;
        } catch (Exception $e) {
            return false;
        }
    }
}

if (!function_exists('bb_rec_consumir')) {
    // Marca o token como usado e remove os demais do mesmo usuario
    function bb_rec_consumir($pdo, $registro)
    {
        try {
            $pdo->prepare("UPDATE usuarios_recuperacao SET usado_em = NOW() WHERE id = :id")
                ->execute(array(':id' => (int) $registro['id']));
            $pdo->prepare("DELETE FROM usuarios_recuperacao WHERE usuario_id = :u AND id <> :id")
                ->execute(array(':u' => (int) $registro['usuario_id'], ':id' => (int) $registro['id']));
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}

if (!function_exists('bb_rec_link')) {
    // Link absoluto do formulario de nova senha no host atual
    function bb_rec_link($token)
    {
        $base = isset($GLOBALS['url_sistema']) ? rtrim((string) $GLOBALS['url_sistema'], '/') : '';
        return $base . '/sistema/recuperar-senha.php?token=' . rawurlencode((string) $token);
    }
}
