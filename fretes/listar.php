<?php
require_once __DIR__ . '/../includes/funcoes.php';
$fretes = consultar('SELECT f.*, o.nome AS origem, d.nome AS destino, t.nome AS transportador FROM fretes f JOIN enderecos o ON o.id=f.id_origem JOIN enderecos d ON d.id=f.id_destino JOIN transportadores t ON t.id=f.id_transportador ORDER BY f.data_frete DESC, f.id DESC')->fetchAll();
$titulo = 'Fretes';
require __DIR__ . '/../includes/header.php';
?>
<div class="cabecalho"><h1>Fretes</h1><a class="btn btn-primary" href="<?= BASE_URL ?>/fretes/novo.php">Gerar frete</a></div>
<div class="card"><div class="card-body table-responsive"><table class="table table-hover mb-0"><thead><tr><th>ID</th><th>Data</th><th>Origem</th><th>Destino</th><th>Transportador</th><th>Valor do frete</th><th>Status</th><th>Ações</th></tr></thead><tbody>
<?php if (!$fretes): ?><tr><td colspan="8" class="text-center py-4">Nenhum frete gerado.</td></tr><?php endif; ?>
<?php foreach ($fretes as $f): ?><tr><td><?= (int)$f['id'] ?></td><td><?= h(dataBr($f['data_frete'])) ?></td><td><?= h($f['origem']) ?></td><td><?= h($f['destino']) ?></td><td><?= h($f['transportador']) ?></td><td><?= h(moeda($f['valor_frete'])) ?></td><td><span class="badge text-bg-light border"><?= h($f['status']) ?></span></td><td><a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL ?>/fretes/detalhes.php?id=<?= (int)$f['id'] ?>">Detalhes</a></td></tr><?php endforeach; ?>
</tbody></table></div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
