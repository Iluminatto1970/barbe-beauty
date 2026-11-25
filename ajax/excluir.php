<?php
require_once("../sistema/conexao.php");

// Endpoint legado: nao e chamado por nenhuma tela do projeto. A exclusao
// de agendamento acontece no painel autenticado
// (sistema/painel/paginas/agendamentos/excluir.php), com sessao e CSRF.
// Mantemos a rota respondendo 403 para que uma copia antiga no servidor
// nao continue apagando registros por id.
bb_resposta_texto(403, 'Operação não permitida.');
?>
