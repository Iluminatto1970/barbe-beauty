<?php
// ============================================================
// PLANO E COTA DE AGENDAMENTOS (SaaS)
// ------------------------------------------------------------
// Plano gratuito: limite de agendamentos por mes definido em
// saas_planos.limite_agendamentos (0 = ilimitado).
// Estabelecimento com assinatura paga (status 'ativo') nao tem
// cota. A instalacao principal (sem tenant) tambem nao tem.
// ============================================================
if (!function_exists('bb_plano_upgrade_preco')) {
    // Preco do plano pago mais barato (usado nas chamadas de upgrade)
    function bb_plano_upgrade_preco($pdo_controle = null)
    {
        global $pdo_controle;
        if (!$pdo_controle) {
            return 49.00;
        }
        try {
            $valor = $pdo_controle->query("SELECT MIN(valor) FROM saas_planos WHERE ativo = 'Sim' AND valor > 0")->fetchColumn();
            if ($valor !== false && $valor !== null && (float) $valor > 0) {
                return (float) $valor;
            }
        } catch (Exception $e) {
            // tabela ausente: usa o padrao
        }
        return 49.00;
    }
}

if (!function_exists('bb_plano_pago_id')) {
    // Id do plano pago mais barato (para registrar a assinatura no tenant)
    function bb_plano_pago_id($pdo_controle = null)
    {
        global $pdo_controle;
        if (!$pdo_controle) {
            return 0;
        }
        try {
            $q = $pdo_controle->query("SELECT id FROM saas_planos WHERE ativo = 'Sim' AND valor > 0 ORDER BY valor ASC LIMIT 1");
            $id = $q ? $q->fetchColumn() : 0;
            return $id ? (int) $id : 0;
        } catch (Exception $e) {
            return 0;
        }
    }
}

if (!function_exists('bb_quota_plano')) {
    // Situacao do plano do estabelecimento atual:
    // plano, limite, usados, restantes, ilimitado, preco (upgrade)
    function bb_quota_plano($pdo_tenant = null)
    {
        global $pdo_controle, $tenant_atual;

        $quota = array(
            'plano'     => 'Grátis',
            'limite'    => 20,
            'usados'    => 0,
            'restantes' => 20,
            'ilimitado' => false,
            'preco'     => bb_plano_upgrade_preco($pdo_controle),
        );

        // Instalacao principal / CLI sem tenant: sem cota
        if (!$tenant_atual || !$pdo_controle) {
            $quota['ilimitado'] = true;
            $quota['restantes'] = null;
            return $quota;
        }

        $status = strtolower(trim((string) (isset($tenant_atual['status']) ? $tenant_atual['status'] : '')));
        if ($status === 'ativo') {
            $quota['ilimitado'] = true;
            $quota['restantes'] = null;
            return $quota;
        }

        // Limite vem do plano contratado (padrao: gratuito)
        if (!empty($tenant_atual['plano_id'])) {
            try {
                $q = $pdo_controle->prepare("SELECT nome, valor, limite_agendamentos FROM saas_planos WHERE id = :id LIMIT 1");
                $q->execute(array(':id' => (int) $tenant_atual['plano_id']));
                $plano = $q->fetch(PDO::FETCH_ASSOC);
                if ($plano) {
                    $quota['plano']  = (string) $plano['nome'];
                    $quota['limite'] = (int) $plano['limite_agendamentos'];
                }
            } catch (Exception $e) {
                // mantem o padrao gratuito
            }
        }

        if ($quota['limite'] <= 0) {
            $quota['ilimitado'] = true;
            $quota['restantes'] = null;
            return $quota;
        }

        // Agendamentos do mes corrente no banco do estabelecimento
        $contar = $pdo_tenant ? $pdo_tenant : (isset($GLOBALS['pdo']) ? $GLOBALS['pdo'] : null);
        if ($contar) {
            try {
                $q = $contar->prepare("SELECT COUNT(*) FROM agendamentos WHERE data >= :ini AND data <= :fim");
                $q->execute(array(':ini' => date('Y-m-01'), ':fim' => date('Y-m-t')));
                $quota['usados'] = (int) $q->fetchColumn();
            } catch (Exception $e) {
                $quota['usados'] = 0;
            }
        }

        $quota['restantes'] = max(0, $quota['limite'] - $quota['usados']);
        return $quota;
    }
}

if (!function_exists('bb_quota_atingida')) {
    function bb_quota_atingida($pdo_tenant = null)
    {
        $quota = bb_quota_plano($pdo_tenant);
        return !$quota['ilimitado'] && $quota['usados'] >= $quota['limite'];
    }
}

if (!function_exists('bb_quota_mensagem')) {
    // Texto padrao para o dono do estabelecimento
    function bb_quota_mensagem($quota = null)
    {
        if ($quota === null) {
            $quota = bb_quota_plano();
        }
        $preco = number_format((float) $quota['preco'], 2, ',', '.');
        return 'Você usou os ' . (int) $quota['limite'] . ' agendamentos gratuitos deste mês. '
            . 'Ative o plano PRO por R$ ' . $preco . '/mês para liberar agendamentos ilimitados.';
    }
}
