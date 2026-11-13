<?php
require_once __DIR__ . '/../includes/funcoes.php';
$data = date('Y-m-d');
$erro = '';
$fretes = [];
if (isset($_GET['data'])) {
    try {
        $data = dataValida($_GET['data']);
        $fretes = consultar('SELECT f.id, f.data_frete, o.nome AS origem, d.nome AS destino, t.nome AS transportador, f.status, f.valor_frete, i.unidades, i.peso, i.mercadorias FROM fretes f JOIN enderecos o ON o.id=f.id_origem JOIN enderecos d ON d.id=f.id_destino JOIN transportadores t ON t.id=f.id_transportador JOIN (SELECT id_frete, SUM(quantidade) AS unidades, SUM(quantidade*peso_unitario) AS peso, SUM(quantidade*valor_unitario) AS mercadorias FROM frete_produtos GROUP BY id_frete) i ON i.id_frete=f.id WHERE f.data_frete = ? ORDER BY f.id', [$data])->fetchAll();
        if (texto($_GET['baixar'] ?? '') === '1') {
            if (!$fretes) {
                $erro = 'Nenhum frete encontrado nesta data. Selecione outro dia.';
            } else {
                header('Content-Type: text/csv; charset=UTF-8');
                header('Content-Disposition: attachment; filename="fretes_' . $data . '.csv"');
                header('Cache-Control: no-store');
                $saida = fopen('php://output', 'w');
                fwrite($saida, "\xEF\xBB\xBF");
                fputcsv($saida, ['Frete','Data','Origem','Destino','Transportador','Status','Valor do frete (R$)','Unidades','Peso total (kg)','Valor das mercadorias (R$)'], ';', '"', '');
                foreach ($fretes as $f) {
                    $linha = [$f['id'],dataBr($f['data_frete']),$f['origem'],$f['destino'],$f['transportador'],$f['status'],number_format((float)$f['valor_frete'],2,',',''),$f['unidades'],number_format((float)$f['peso'],3,',',''),number_format((float)$f['mercadorias'],2,',','')];
                    // Evita que nomes sejam interpretados como fórmulas no Excel.
                    $linha = array_map(function($v) { $v=(string)$v; return preg_match('/^[\s\x00-\x20]*[=+@-]/u',$v) ? "'" . $v : $v; }, $linha);
                    fputcsv($saida, $linha, ';', '"', '');
                }
                fclose($saida);
                exit;
            }
        }
    } catch (InvalidArgumentException $e) { $erro = $e->getMessage(); }
}
$titulo = 'Relatório por dia';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="mb-4">Relatório de fretes por dia</h1>
<?php if ($erro): ?><div class="alert alert-warning" role="alert"><?= h($erro) ?></div><?php endif; ?>
<div class="card mb-4"><div class="card-body"><p>Selecione a data do frete. O CSV inclui todos os status, inclusive cancelados, e uma linha por frete.</p>
<form method="get" class="row g-3 align-items-end"><div class="col-md-5"><label class="form-label" for="data">Data do frete</label><input class="form-control" type="date" id="data" name="data" min="1000-01-01" max="9999-12-31" value="<?= h($data) ?>" required></div><div class="col-md-7 acoes"><button class="btn btn-outline-primary" type="submit">Consultar</button><button class="btn btn-primary" name="baixar" value="1" type="submit">Baixar CSV</button></div></form>
</div></div>
<?php if (isset($_GET['data']) && !$erro): ?>
<div class="card"><div class="card-body"><h2 class="h5"><?= count($fretes) ?> frete(s) em <?= h(dataBr($data)) ?></h2><div class="table-responsive"><table class="table"><thead><tr><th>Frete</th><th>Transportador</th><th>Status</th><th>Valor do frete</th></tr></thead><tbody>
<?php if (!$fretes): ?><tr><td colspan="4">Nenhum frete encontrado nesta data.</td></tr><?php endif; ?>
<?php foreach ($fretes as $f): ?><tr><td><a href="<?= BASE_URL ?>/fretes/detalhes.php?id=<?= (int)$f['id'] ?>">#<?= (int)$f['id'] ?></a></td><td><?= h($f['transportador']) ?></td><td><?= h($f['status']) ?></td><td><?= h(moeda($f['valor_frete'])) ?></td></tr><?php endforeach; ?>
</tbody></table></div></div></div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
