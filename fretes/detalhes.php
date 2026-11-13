<?php
require_once __DIR__ . '/../includes/funcoes.php';
try {
    $id = inteiro($_GET['id'] ?? '');
    $f = consultar('SELECT * FROM fretes WHERE id = ?', [$id])->fetch();
    if (!$f) throw new InvalidArgumentException('Frete não encontrado.');
} catch (InvalidArgumentException $e) {
    mensagem('danger', $e->getMessage()); ir('/fretes/listar.php');
}
$origem = consultar('SELECT * FROM enderecos WHERE id = ?', [$f['id_origem']])->fetch();
$destino = consultar('SELECT * FROM enderecos WHERE id = ?', [$f['id_destino']])->fetch();
$t = consultar('SELECT * FROM transportadores WHERE id = ?', [$f['id_transportador']])->fetch();
$itens = consultar('SELECT *, quantidade * valor_unitario AS subtotal, quantidade * peso_unitario AS peso_total FROM frete_produtos WHERE id_frete = ? ORDER BY id', [$id])->fetchAll();
$totais = consultar('SELECT COALESCE(SUM(quantidade),0) AS unidades, COALESCE(SUM(quantidade * valor_unitario),0) AS mercadorias, COALESCE(SUM(quantidade * peso_unitario),0) AS peso FROM frete_produtos WHERE id_frete = ?', [$id])->fetch();
$titulo = 'Frete #' . $id;
require __DIR__ . '/../includes/header.php';
?>
<div class="cabecalho"><h1><?= h($titulo) ?></h1><a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/fretes/listar.php">Voltar</a></div>
<div class="card mb-3"><div class="card-body"><div class="row g-3"><div class="col-md-4"><strong>Data</strong><br><?= h(dataBr($f['data_frete'])) ?></div><div class="col-md-4"><strong>Status</strong><br><?= h($f['status']) ?></div><div class="col-md-4"><strong>Valor do frete</strong><br><?= h(moeda($f['valor_frete'])) ?></div></div></div></div>
<div class="row g-3 mb-3">
<?php foreach (['Origem'=>$origem,'Destino'=>$destino] as $rotulo=>$e): ?>
<div class="col-md-6"><div class="card h-100"><div class="card-body"><h2 class="h5"><?= h($rotulo) ?></h2><strong><?= h($e['nome']) ?></strong><p class="mb-0"><?= h($e['rua'] . ', ' . $e['numero']) ?><br><?= h($e['bairro'] . ' — ' . $e['cidade'] . '/' . $e['estado']) ?><br>CEP: <?= h($e['cep']) ?></p></div></div></div>
<?php endforeach; ?>
</div>
<div class="card mb-3"><div class="card-body"><h2 class="h5">Transportador</h2><strong><?= h($t['nome']) ?></strong><p class="mb-0">CPF/CNPJ: <?= h($t['documento']) ?> · Telefone: <?= h($t['telefone']) ?><br>Veículo: <?= h($t['veiculo']) ?> · Placa: <?= h($t['placa']) ?></p></div></div>
<div class="card mb-3"><div class="card-body"><h2 class="h5">Produtos</h2><div class="table-responsive"><table class="table"><thead><tr><th>Código</th><th>Produto</th><th>Qtd.</th><th>Peso unitário (kg)</th><th>Valor unitário</th><th>Subtotal</th></tr></thead><tbody>
<?php foreach ($itens as $p): ?><tr><td><?= h($p['codigo_produto']) ?></td><td><?= h($p['nome_produto']) ?></td><td><?= (int)$p['quantidade'] ?></td><td><?= h(number_format((float)$p['peso_unitario'],3,',','.')) ?></td><td><?= h(moeda($p['valor_unitario'])) ?></td><td><?= h(moeda($p['subtotal'])) ?></td></tr><?php endforeach; ?>
</tbody></table></div>
<div class="row g-3"><div class="col-md-4"><strong>Total de unidades:</strong> <?= h($totais['unidades']) ?></div><div class="col-md-4"><strong>Peso total:</strong> <?= h(number_format((float)$totais['peso'],3,',','.')) ?> kg</div><div class="col-md-4"><strong>Valor das mercadorias:</strong> <?= h(moeda($totais['mercadorias'])) ?></div></div>
<p class="small text-secondary mt-3 mb-0">Valores e nomes dos produtos registrados no momento da geração. Endereços e transportador refletem os cadastros atuais. O valor do frete é cobrado separadamente das mercadorias.</p>
</div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
