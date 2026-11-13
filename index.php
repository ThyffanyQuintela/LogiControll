<?php
require_once __DIR__ . '/includes/funcoes.php';
$titulo = 'Início';
$totais = [];
foreach (['produtos','enderecos','transportadores','fretes'] as $tabela) {
    $totais[$tabela] = consultar("SELECT COUNT(*) FROM $tabela")->fetchColumn();
}
require __DIR__ . '/includes/header.php';
?>
<div class="hero mb-4"><h1>Gestão logística em um só lugar</h1><p class="mb-0">Organize cadastros, gere fretes com vários produtos e acompanhe os envios.</p></div>
<div class="row g-3 mb-4">
<?php foreach (['produtos'=>'Produtos','enderecos'=>'Endereços','transportadores'=>'Transportadores','fretes'=>'Fretes'] as $chave=>$nome): ?>
<div class="col-sm-6 col-xl-3"><div class="card h-100"><div class="card-body"><h2 class="h6"><?= h($nome) ?></h2><div class="resumo-numero"><?= (int)$totais[$chave] ?></div><a href="<?= BASE_URL ?>/<?= h($chave) ?>/listar.php">Ver <?= h(strtolower($nome)) ?></a></div></div></div>
<?php endforeach; ?>
</div>
<div class="card"><div class="card-body"><h2 class="h5">Próximas ações</h2><p>Cadastre os produtos, os endereços de origem e destino e o transportador antes de gerar um frete.</p><div class="acoes"><a class="btn btn-primary" href="<?= BASE_URL ?>/fretes/novo.php">Gerar frete</a><a class="btn btn-outline-primary" href="<?= BASE_URL ?>/relatorios/frete_dia.php">Relatório por dia</a></div></div></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
