<?php
// PoC: a MESMA tela, mas resolvida em UMA query no Postgres.
// O N+1 da versao fiel (1 query da lista + 3 por linha) vira um join
// com LATERAL agregando os servicos. O HTML gerado e identico.

function poc_listar_agendamentos_pg_otimizado(PDO $pdo, $funcionario, $data, $quantidade_cartoes, $usuario_logado = null): string
{
    ob_start();

    if ($data == "") {
        $data = date('Y-m-d');
    }

    if ($funcionario == "") {
        echo '<small>Selecione um Funcionário!</small>';
        return ob_get_clean();
    }

    echo <<<HTML
<small>
HTML;

    $sql = "SELECT a.id, a.funcionario, a.cliente, a.data, a.hora, a.usuario,
                   a.obs, a.status, a.servico, a.servicos,
                   c.nome    AS cliente_nome,
                   c.cartoes AS cliente_cartoes,
                   s.nomes   AS servico_nomes,
                   s.total   AS servico_total
            FROM agendamentos a
            LEFT JOIN clientes c ON c.id = a.cliente
            LEFT JOIN LATERAL (
                SELECT string_agg(sv.nome, ' + ' ORDER BY t.ord) AS nomes,
                       sum(sv.valor)                             AS total
                FROM unnest(string_to_array(coalesce(nullif(trim(a.servicos), ''), a.servico::text), ','))
                     WITH ORDINALITY AS t(sid, ord)
                JOIN servicos sv ON sv.id = t.sid::int
            ) s ON true
            WHERE a.funcionario = :funcionario AND a.data = :data
            ORDER BY a.hora asc";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['funcionario' => $funcionario, 'data' => $data]);
    $linhas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($linhas) > 0) {
        foreach ($linhas as $l) {
            $id = $l['id'];
            $cliente = $l['cliente'];
            $funcionario = $l['funcionario'];
            $servico = $l['servico'];
            $texto_servico = $l['servico_nomes'] ?? 'Não Lançado';
            $valor_serv = $l['servico_total'] !== null ? number_format((float)$l['servico_total'], 2, '.', '') : '';
            $horaF = date("H:i", strtotime($l['hora']));
            $status = $l['status'];
            $nome_cliente = $l['cliente_nome'] ?? 'Sem Cliente';
            $total_cartoes = $l['cliente_cartoes'] ?? 0;

            if ($status == 'Aguardando') {
                $imagem = 'icone-relogio.png';
                $classe_status = 'ocultar';
                $classe_confirmar = '';
                $texto_status = 'Aguardando confirmação';
                $cor_status = '#b7791f';
            } elseif ($status == 'Agendado') {
                $imagem = 'icone-relogio.png';
                $classe_status = '';
                $classe_confirmar = 'ocultar';
                $texto_status = 'Confirmado';
                $cor_status = '#00a085';
            } else {
                $imagem = 'icone-relogio-verde.png';
                $classe_status = 'ocultar';
                $classe_confirmar = 'ocultar';
                $texto_status = $status;
                $cor_status = '#9aa0a6';
            }

            $ocultar_cartoes = ($total_cartoes >= $quantidade_cartoes and $status == 'Agendado') ? '' : 'ocultar';
            $obs = str_replace('"', "**", (string)$l['obs']);

            echo <<<HTML
			<div class="col-xs-12 col-md-4 widget cardTarefas">
        		<div class="r3_counter_box">
				<li class="dropdown head-dpdn2" style="list-style-type: none;">
				<a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
				<button type="button" class="close" title="Excluir agendamento" style="margin-top: -10px">
					<span aria-hidden="true"><big>&times;</big></span>
				</button>
				</a>
		<ul class="dropdown-menu" style="margin-left:-30px;">
		<li>
		<div class="notification_desc2">
		<p>Confirmar Exclusão? <a href="#" onclick="excluir('{$id}', '{$horaF}')"><span class="text-danger">Sim</span></a></p>
		</div>
		</li>
		</ul>
		</li>
		<div class="row">
        		<div class="col-md-3">
        			 <img class="icon-rounded-vermelho" src="img/{$imagem}" width="45px" height="45px">
        		</div>
        		<div class="col-md-9">
        			<h5><strong>{$horaF}</strong> <a href="#" onclick="fecharServico('{$id}', '{$cliente}', '{$servico}', '{$valor_serv}', '{$funcionario}', '{$texto_servico}')" title="Finalizar Serviço" class="{$classe_status}"> <img class="icon-rounded-vermelho" src="img/check-square.png" width="15px" height="15px"></a> <a href="#" onclick="confirmarAgendamento('{$id}')" title="Confirmar agendamento" class="{$classe_confirmar}"> <img class="icon-rounded-vermelho" src="img/icone-relogio-verde.png" width="15px" height="15px"></a></h5>
        		</div>
        		</div>
        		<hr style="margin-top:-2px; margin-bottom: 3px">
					<div align="center"><small style="color:{$cor_status}; font-size:10px; text-transform:uppercase; letter-spacing:.5px; font-weight:600">{$texto_status}</small></div>
                    <div class="stats esc" align="center">
                      <span>
                        <small> <span class="{$ocultar_cartoes}" style=""><img class="icon-rounded-vermelho" src="img/presente.jpg" width="20px" height="20px"></span> {$nome_cliente} (<i><span style="color:#061f9c">{$texto_servico}</span></i>)</small></span>
                    </div>
                </div>
        	</div>
HTML;
        }
    } else {
        echo 'Nenhum horário para essa Data!';
    }

    return ob_get_clean();
}
