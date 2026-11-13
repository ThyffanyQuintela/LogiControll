<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
<div class="container">
<a class="navbar-brand fw-bold" href="<?= BASE_URL ?>/index.php">LogiControl</a>
<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuPrincipal" aria-controls="menuPrincipal" aria-expanded="false" aria-label="Abrir menu"><span class="navbar-toggler-icon"></span></button>
<div class="collapse navbar-collapse" id="menuPrincipal">
<ul class="navbar-nav ms-auto">
<?php $links = ['Início'=>'/index.php','Produtos'=>'/produtos/listar.php','Endereços'=>'/enderecos/listar.php','Transportadores'=>'/transportadores/listar.php','Fretes'=>'/fretes/listar.php','Relatório'=>'/relatorios/frete_dia.php']; ?>
<?php foreach ($links as $rotulo=>$url): $ativo = ($_SERVER['SCRIPT_NAME'] ?? '') === BASE_URL . $url; ?>
<li class="nav-item"><a class="nav-link<?= $ativo ? ' active' : '' ?>" <?= $ativo ? 'aria-current="page"' : '' ?> href="<?= h(BASE_URL . $url) ?>"><?= h($rotulo) ?></a></li>
<?php endforeach; ?>
</ul>
</div>
</div>
</nav>
