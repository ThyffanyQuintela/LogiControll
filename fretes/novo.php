<?php
require_once __DIR__ . '/../includes/funcoes.php';
$origens = consultar('SELECT id, nome, cidade, estado FROM enderecos WHERE tipo = ? ORDER BY nome', ['origem'])->fetchAll();
$destinos = consultar('SELECT id, nome, cidade, estado FROM enderecos WHERE tipo = ? ORDER BY nome', ['destino'])->fetchAll();
$transportadores = consultar('SELECT id, nome FROM transportadores ORDER BY nome')->fetchAll();
$produtos = consultar('SELECT id, nome, codigo FROM produtos ORDER BY nome')->fetchAll();
$antigos = $_SESSION['antigos_frete'] ?? [];
unset($_SESSION['antigos_frete']);
$disponivel = $origens && $destinos && $transportadores && $produtos;
$ids = isset($antigos['produtos']) && is_array($antigos['produtos']) ? array_values($antigos['produtos']) : [''];
$qtds = isset($antigos['quantidades']) && is_array($antigos['quantidades']) ? array_values($antigos['quantidades']) : ['1'];
$ids = array_slice($ids ?: [''], 0, 100);
$titulo = 'Gerar frete';
require __DIR__ . '/../includes/header.php';
function linhaProduto(array $produtos, $id = '', $qtd = '1'): void { ?>
<div class="item-produto mb-3"><div class="row g-3 align-items-end">
<div class="col-md-7"><label data-label-produto class="form-label">Produto *</label><select class="form-select" name="produtos[]" required><option value="">Selecione</option>
<?php foreach ($produtos as $p): ?><option value="<?= (int)$p['id'] ?>" <?= texto($id) === (string)$p['id'] ? 'selected' : '' ?>><?= h($p['codigo'] . ' — ' . $p['nome']) ?></option><?php endforeach; ?>
</select></div>
<div class="col-md-3"><label data-label-quantidade class="form-label">Quantidade *</label><input class="form-control" type="number" name="quantidades[]" min="1" max="1000000" step="1" value="<?= h(texto($qtd)) ?>" required></div>
<div class="col-md-2"><button type="button" data-remover class="btn btn-outline-danger w-100">Remover</button></div>
</div></div>
<?php } ?>
<div class="cabecalho"><h1>Gerar frete</h1><a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/fretes/listar.php">Voltar</a></div>
<?php if (!$disponivel): ?><div class="alert alert-warning">Cadastre pelo menos um produto, uma origem, um destino e um transportador para gerar fretes.</div><?php endif; ?>
<form method="post" action="<?= BASE_URL ?>/fretes/salvar.php" class="card"><div class="card-body">
<?= csrf() ?><fieldset <?= !$disponivel ? 'disabled' : '' ?>><legend class="h5">Dados do frete</legend><div class="row g-3 mb-4">
<?php foreach (['id_origem'=>['Origem',$origens],'id_destino'=>['Destino',$destinos],'id_transportador'=>['Transportador',$transportadores]] as $campo=>$config): ?>
<div class="col-md-4"><label class="form-label" for="<?= h($campo) ?>"><?= h($config[0]) ?> *</label><select class="form-select" name="<?= h($campo) ?>" id="<?= h($campo) ?>" required><option value="">Selecione</option>
<?php foreach ($config[1] as $op): ?><option value="<?= (int)$op['id'] ?>" <?= texto($antigos[$campo] ?? '') === (string)$op['id'] ? 'selected' : '' ?>><?= h($op['nome'] . (isset($op['cidade']) ? ' — ' . $op['cidade'] . '/' . $op['estado'] : '')) ?></option><?php endforeach; ?>
</select></div>
<?php endforeach; ?>
<div class="col-md-4"><label class="form-label" for="data_frete">Data do frete *</label><input type="date" class="form-control" id="data_frete" name="data_frete" min="1000-01-01" max="9999-12-31" value="<?= h(texto($antigos['data_frete'] ?? date('Y-m-d'))) ?>" required></div>
<div class="col-md-4"><label class="form-label" for="valor_frete">Valor do frete (R$) *</label><input type="number" class="form-control" id="valor_frete" name="valor_frete" min="0" max="999999999.99" step="0.01" value="<?= h(texto($antigos['valor_frete'] ?? '0.00')) ?>" required></div>
<div class="col-md-4"><label class="form-label" for="status">Status *</label><select class="form-select" id="status" name="status" required><?php foreach (estadosFrete() as $status): ?><option <?= texto($antigos['status'] ?? 'Pendente') === $status ? 'selected' : '' ?>><?= h($status) ?></option><?php endforeach; ?></select></div>
</div>
<h2 class="h5">Produtos transportados</h2><p class="text-secondary">Até 100 linhas. Produtos repetidos terão suas quantidades somadas. O valor do frete é separado do valor das mercadorias.</p>
<div id="itens"><?php foreach ($ids as $i=>$id) linhaProduto($produtos, $id, $qtds[$i] ?? '1'); ?></div>
<template id="modelo-item"><?php linhaProduto($produtos); ?></template>
<div class="acoes mb-4"><button class="btn btn-outline-primary" id="adicionar-produto" type="button">Adicionar produto</button><span id="contador-itens" class="align-self-center text-secondary" aria-live="polite"></span></div>
<button class="btn btn-primary">Salvar frete</button>
</fieldset></div></form>
<script src="<?= BASE_URL ?>/assets/js/frete.js" defer></script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
