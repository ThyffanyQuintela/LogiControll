<?php require_once __DIR__ . '/funcoes.php'; ?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($titulo ?? 'Início') ?> | LogiControl</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/css/estilo.css" rel="stylesheet">
</head>
<body>
<?php require __DIR__ . '/menu.php'; ?>
<main class="container py-4">
<?php foreach ($_SESSION['mensagens'] ?? [] as $msg): ?>
<div class="alert alert-<?= $msg['tipo'] === 'success' ? 'success' : 'danger' ?>" role="alert"><?= h($msg['texto']) ?></div>
<?php endforeach; unset($_SESSION['mensagens']); ?>
