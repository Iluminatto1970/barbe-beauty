<?php
// ============================================================
// BANNER DE COTA DO PLANO GRATUITO (painel)
// Aparece apenas para estabelecimento com plano limitado.
// ============================================================
if (!function_exists('bb_quota_plano')) {
    return;
}
$bb_quota = bb_quota_plano($pdo);
if (!empty($bb_quota['ilimitado'])) {
    return;
}
$bb_quota_pct = $bb_quota['limite'] > 0
    ? (int) round($bb_quota['usados'] * 100 / $bb_quota['limite'])
    : 0;
$bb_quota_pct = max(0, min(100, $bb_quota_pct));
$bb_quota_cheio = ($bb_quota['usados'] >= $bb_quota['limite']);
$bb_quota_preco = number_format((float) $bb_quota['preco'], 2, ',', '.');
?>
<div style="margin:0 0 14px;padding:14px 16px;border:1px solid <?php echo $bb_quota_cheio ? '#e0a800' : '#d7dee6'; ?>;border-radius:10px;background:<?php echo $bb_quota_cheio ? '#fff8e1' : '#f7fafc'; ?>;display:flex;gap:16px;align-items:center;flex-wrap:wrap;justify-content:space-between">
  <div style="min-width:240px;flex:1">
    <div style="font-size:13px;color:#4a5568">
      Plano <strong><?php echo bb_e($bb_quota['plano']); ?></strong> ·
      <strong><?php echo (int) $bb_quota['usados']; ?>/<?php echo (int) $bb_quota['limite']; ?></strong>
      agendamentos neste mês
    </div>
    <div style="height:6px;background:#e2e8f0;border-radius:4px;margin-top:8px;overflow:hidden">
      <span style="display:block;height:100%;width:<?php echo $bb_quota_pct; ?>%;background:<?php echo $bb_quota_cheio ? '#e0a800' : '#38a169'; ?>"></span>
    </div>
    <?php if ($bb_quota_cheio) { ?>
      <div style="font-size:12px;color:#975a16;margin-top:8px">
        Limite gratuito atingido: novos agendamentos só entram no plano PRO.
      </div>
    <?php } ?>
  </div>
  <a href="plano.php" style="display:inline-block;padding:9px 16px;border-radius:8px;background:#2d3748;color:#fff;text-decoration:none;font-size:13px;white-space:nowrap">
    Ativar PRO · R$ <?php echo $bb_quota_preco; ?>/mês
  </a>
</div>
