<?php
// ============================================================
// BANCO DA DEMONSTRACAO
// ------------------------------------------------------------
// A instalacao principal (dominio de controle) e a vitrine do
// sistema, mas nao pode exibir clientes, agendamentos, contatos
// ou financeiro de nenhum estabelecimento real.
//
// Por isso a demonstracao usa um banco proprio:
//   DB_DEMO_NAME (padrao: DB_NAME + "_demo")
//
// O banco e criado a partir da estrutura canonica
// (sql/tenant_schema.sql), recebe as migracoes e e semeado com
// sql/demo_dados.sql. Nada e copiado do banco de controle.
// ============================================================

if (!function_exists('bb_demo_banco_nome')) {
    function bb_demo_banco_nome()
    {
        $nome = getenv('DB_DEMO_NAME');
        if ($nome === false || trim((string) $nome) === '') {
            $nome = bb_banco_controle() . '_demo';
        }
        $nome = trim((string) $nome);

        // Nome de banco e identificador SQL: nunca aceita nada alem de
        // letras, numeros e sublinhado. Tambem nunca aponta para o banco
        // de controle (que tem os dados reais).
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $nome) || $nome === bb_banco_controle()) {
            return '';
        }
        return $nome;
    }
}

if (!function_exists('bb_demo_semear')) {
    // Executa sql/demo_dados.sql (somente dados ficticios).
    function bb_demo_semear($pdo)
    {
        $arquivo = bb_migracoes_raiz() . '/sql/demo_dados.sql';
        $sql = @file_get_contents($arquivo);
        if ($sql === false || trim($sql) === '') {
            throw new RuntimeException('dados da demonstracao indisponiveis');
        }

        $comandos = bb_migracoes_dividir($sql);
        if (count($comandos) === 0) {
            throw new RuntimeException('dados da demonstracao vazios');
        }

        $pdo->beginTransaction();
        try {
            foreach ($comandos as $comando) {
                $pdo->exec($comando);
            }
            $pdo->commit();
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw new RuntimeException('nao foi possivel semear a demonstracao');
        }
    }
}

if (!function_exists('bb_demo_garantir_banco')) {
    // Devolve a conexao com o banco ficticio da demonstracao,
    // criando estrutura e dados na primeira vez.
    // Lanca excecao quando a demo nao pode ser preparada: quem chama
    // deve negar o acesso, nunca cair no banco de controle.
    function bb_demo_garantir_banco($pdo_controle)
    {
        $nome = bb_demo_banco_nome();
        if ($nome === '') {
            throw new RuntimeException('banco de demonstracao invalido');
        }

        try {
            $pdo = bb_pdo_banco($nome);
        } catch (Exception $e) {
            // Primeira execucao: o banco ainda nao existe.
            try {
                $pdo_controle->exec("CREATE DATABASE IF NOT EXISTS `$nome` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
            } catch (Exception $criacao) {
                throw new RuntimeException('banco de demonstracao indisponivel');
            }
            $pdo = bb_pdo_banco($nome);
        }

        // Estrutura e migracoes so quando o banco esta novo ou atrasado:
        // o caminho normal da demo nao repete DDL a cada visita.
        $precisa_estrutura = false;
        try {
            $pdo->query('SELECT 1 FROM config LIMIT 1');
            $precisa_estrutura = bb_migracoes_versao($pdo) < bb_migracoes_versao_final();
        } catch (Exception $e) {
            $precisa_estrutura = true;
        }

        if ($precisa_estrutura) {
            $estrutura = bb_tenant_criar_schema($pdo);
            if (!$estrutura['ok']) {
                throw new RuntimeException('estrutura da demonstracao: ' . $estrutura['erro']);
            }

            $migracao = bb_migracoes_sincronizar($pdo);
            if (!$migracao['ok']) {
                throw new RuntimeException('migracao da demonstracao: ' . $migracao['erro']);
            }
        }

        $total_config = (int) $pdo->query('SELECT COUNT(*) FROM config')->fetchColumn();
        if ($total_config === 0) {
            bb_demo_semear($pdo);
        }

        return $pdo;
    }
}
