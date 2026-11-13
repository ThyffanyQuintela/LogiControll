### gerenciador_logistico/database/script.sql

```sql
CREATE DATABASE IF NOT EXISTS gerenciador_logistico CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gerenciador_logistico;

CREATE TABLE IF NOT EXISTS produtos (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 nome VARCHAR(120) NOT NULL,
 descricao TEXT NOT NULL,
 peso DECIMAL(9,3) NOT NULL,
 valor DECIMAL(11,2) NOT NULL,
 codigo VARCHAR(50) NOT NULL UNIQUE,
 criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS enderecos (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 tipo ENUM('origem','destino') NOT NULL,
 nome VARCHAR(120) NOT NULL,
 rua VARCHAR(150) NOT NULL,
 numero VARCHAR(20) NOT NULL,
 bairro VARCHAR(100) NOT NULL,
 cidade VARCHAR(100) NOT NULL,
 estado CHAR(2) NOT NULL,
 cep VARCHAR(20) NOT NULL,
 criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS transportadores (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 nome VARCHAR(120) NOT NULL,
 documento VARCHAR(20) NOT NULL,
 telefone VARCHAR(30) NOT NULL,
 veiculo VARCHAR(80) NOT NULL,
 placa VARCHAR(15) NOT NULL,
 criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS fretes (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 id_origem INT UNSIGNED NOT NULL,
 id_destino INT UNSIGNED NOT NULL,
 id_transportador INT UNSIGNED NOT NULL,
 data_frete DATE NOT NULL,
 valor_frete DECIMAL(11,2) NOT NULL,
 status ENUM('Pendente','Em transporte','Concluído','Cancelado') NOT NULL DEFAULT 'Pendente',
 criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_frete_data (data_frete),
 FOREIGN KEY (id_origem) REFERENCES enderecos(id) ON DELETE RESTRICT ON UPDATE RESTRICT,
 FOREIGN KEY (id_destino) REFERENCES enderecos(id) ON DELETE RESTRICT ON UPDATE RESTRICT,
 FOREIGN KEY (id_transportador) REFERENCES transportadores(id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS frete_produtos (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 id_frete INT UNSIGNED NOT NULL,
 id_produto INT UNSIGNED NOT NULL,
 quantidade INT UNSIGNED NOT NULL,
 nome_produto VARCHAR(120) NOT NULL,
 codigo_produto VARCHAR(50) NOT NULL,
 peso_unitario DECIMAL(9,3) NOT NULL,
 valor_unitario DECIMAL(11,2) NOT NULL,
 criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uk_frete_produto (id_frete,id_produto),
 FOREIGN KEY (id_frete) REFERENCES fretes(id) ON DELETE CASCADE ON UPDATE RESTRICT,
 FOREIGN KEY (id_produto) REFERENCES produtos(id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB;
```

### gerenciador_logistico/config/conexao.php

```php
<?php
// Ajuste somente se o seu MySQL usar outra configuração.
$host = getenv('DB_HOST') ?: '127.0.0.1';
$porta = getenv('DB_PORT') ?: '3306';
$banco = getenv('DB_NAME') ?: 'gerenciador_logistico';
$usuario = getenv('DB_USER') ?: 'root';
$senha = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
try {
    $pdo = new PDO("mysql:host=$host;port=$porta;dbname=$banco;charset=utf8mb4", $usuario, $senha, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(503);
    exit('Banco indisponível. Verifique o MySQL, importe database/script.sql e confira config/conexao.php.');
}
```

### gerenciador_logistico/includes/funcoes.php

```php
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}
date_default_timezone_set('America/Sao_Paulo');
define('BASE_URL', '/gerenciador_logistico');
require_once __DIR__ . '/../config/conexao.php';

function h($valor): string {
    return htmlspecialchars((string)$valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function texto($valor): string {
    return is_string($valor) ? trim($valor) : '';
}
function consultar(string $sql, array $parametros = []): PDOStatement {
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($parametros);
    return $stmt;
}
function ir(string $caminho): void {
    header('Location: ' . BASE_URL . $caminho, true, 303);
    exit;
}
function mensagem(string $tipo, string $texto): void {
    $_SESSION['mensagens'][] = ['tipo' => $tipo, 'texto' => $texto];
}
function csrf(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return '<input type="hidden" name="csrf" value="' . h($_SESSION['csrf']) . '">';
}
function exigirPost(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        exit('Método não permitido.');
    }
    if (!hash_equals($_SESSION['csrf'] ?? '', texto($_POST['csrf'] ?? '')) || empty($_SESSION['csrf'])) {
        http_response_code(403);
        exit('Sessão inválida. Volte ao formulário e atualize a página.');
    }
}
function inteiro($valor, int $maximo = 4294967295): int {
    $s = is_int($valor) ? (string)$valor : texto($valor);
    if (!preg_match('/^[1-9][0-9]{0,9}$/D', $s) || (float)$s > $maximo) {
        throw new InvalidArgumentException('Informe identificadores e quantidades válidos.');
    }
    return (int)$s;
}
function decimal($valor, int $casas = 2, int $digitos = 9): string {
    $s = str_replace(',', '.', texto($valor));
    if (!preg_match('/^\d{1,' . $digitos . '}(\.\d{1,' . $casas . '})?$/D', $s)) {
        throw new InvalidArgumentException('Informe um valor numérico não negativo, sem separador de milhares.');
    }
    return $s;
}
function dataValida($valor): string {
    $s = texto($valor);
    if (!preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/D', $s)) throw new InvalidArgumentException('Informe uma data válida.');
    $d = DateTime::createFromFormat('!Y-m-d', $s);
    if (!$d || $d->format('Y-m-d') !== $s || $s < '1000-01-01' || $s > '9999-12-31') {
        throw new InvalidArgumentException('Informe uma data válida.');
    }
    return $s;
}
function moeda($valor): string {
    return 'R$ ' . number_format((float)$valor, 2, ',', '.');
}
function dataBr(string $data): string {
    return date('d/m/Y', strtotime($data));
}
function ufs(): array {
    return explode(' ', 'AC AL AP AM BA CE DF ES GO MA MT MS MG PA PB PR PE PI RJ RN RS RO RR SC SP SE TO');
}
function estadosFrete(): array {
    return ['Pendente', 'Em transporte', 'Concluído', 'Cancelado'];
}
function esquema(string $modulo): array {
    // Lista fechada: nomes de tabelas e colunas não vêm da requisição.
    $mapa = [
        'produtos' => ['titulo'=>'Produtos','singular'=>'Produto','campos'=>[
            'codigo'=>['Código','text',50], 'nome'=>['Nome','text',120],
            'descricao'=>['Descrição','textarea',5000,false], 'peso'=>['Peso unitário (kg)','peso',9],
            'valor'=>['Valor unitário (R$)','dinheiro',11]
        ]],
        'enderecos' => ['titulo'=>'Endereços','singular'=>'Endereço','campos'=>[
            'tipo'=>['Tipo','tipo',7], 'nome'=>['Identificação','text',120],
            'rua'=>['Rua','text',150], 'numero'=>['Número','text',20],
            'bairro'=>['Bairro','text',100], 'cidade'=>['Cidade','text',100],
            'estado'=>['Estado','uf',2], 'cep'=>['CEP','text',20]
        ]],
        'transportadores' => ['titulo'=>'Transportadores','singular'=>'Transportador','campos'=>[
            'nome'=>['Nome','text',120], 'documento'=>['CPF ou CNPJ','text',20],
            'telefone'=>['Telefone','text',30], 'veiculo'=>['Veículo','text',80],
            'placa'=>['Placa','text',15]
        ]]
    ];
    if (!isset($mapa[$modulo])) throw new InvalidArgumentException('Módulo inválido.');
    return $mapa[$modulo];
}
```

### gerenciador_logistico/includes/header.php

```php
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
```

### gerenciador_logistico/includes/menu.php

```php
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
```

### gerenciador_logistico/includes/footer.php

```php
</main>
<footer class="container border-top py-3 mt-auto text-secondary small">LogiControl • Gerenciador Logístico • UNIPAR EAD</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
```

### gerenciador_logistico/includes/crud.php

```php
<?php
require_once __DIR__ . '/funcoes.php';
$config = esquema($modulo);
$campos = $config['campos'];
$base = '/' . $modulo;

if ($acao === 'salvar') {
    exigirPost();
    $_SESSION['antigos'][$modulo] = $_POST;
    $id = 0;
    try {
        if (texto($_POST['id'] ?? '') !== '') $id = inteiro($_POST['id']);
        $dados = [];
        foreach ($campos as $coluna=>$campo) {
            [$rotulo,$tipo,$limite] = $campo;
            $valor = texto($_POST[$coluna] ?? '');
            if (($campo[3] ?? true) && $valor === '') throw new InvalidArgumentException("Preencha: $rotulo.");
            // O limite em bytes é conservador e dispensa extensões adicionais.
            if (strlen($valor) > $limite && !in_array($tipo, ['peso','dinheiro'], true)) {
                throw new InvalidArgumentException("$rotulo: limite de $limite bytes.");
            }
            if ($tipo === 'dinheiro') $valor = decimal($valor);
            if ($tipo === 'peso') $valor = decimal($valor, 3, 6);
            if ($tipo === 'tipo' && !in_array($valor, ['origem','destino'], true)) throw new InvalidArgumentException('Tipo inválido.');
            if ($tipo === 'uf') {
                $valor = strtoupper($valor);
                if (!in_array($valor, ufs(), true)) throw new InvalidArgumentException('Estado inválido.');
            }
            $dados[$coluna] = $valor;
        }
        $pdo->beginTransaction();
        if ($id) {
            if (!consultar("SELECT id FROM $modulo WHERE id = ? FOR UPDATE", [$id])->fetch()) throw new InvalidArgumentException('Registro não encontrado.');
            // O tipo de endereço vinculado não pode contradizer o frete.
            if ($modulo === 'enderecos') {
                $uso = consultar('SELECT id FROM fretes WHERE (id_origem = ? AND ? <> ?) OR (id_destino = ? AND ? <> ?) LIMIT 1', [$id,$dados['tipo'],'origem',$id,$dados['tipo'],'destino'])->fetch();
                if ($uso) throw new InvalidArgumentException('O tipo deste endereço está vinculado a um frete e não pode ser alterado.');
            }
            $sets = implode(', ', array_map(function($c) { return "$c = ?"; }, array_keys($campos)));
            consultar("UPDATE $modulo SET $sets WHERE id = ?", array_merge(array_values($dados), [$id]));
        } else {
            $colunas = implode(', ', array_keys($campos));
            $marcas = implode(', ', array_fill(0, count($campos), '?'));
            consultar("INSERT INTO $modulo ($colunas) VALUES ($marcas)", array_values($dados));
        }
        $pdo->commit();
        unset($_SESSION['antigos'][$modulo]);
        mensagem('success', 'Registro salvo com sucesso.');
        ir($base . '/listar.php');
    } catch (InvalidArgumentException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        mensagem('danger', $e->getMessage());
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log($e->getMessage());
        mensagem('danger', ($e->errorInfo[1] ?? 0) == 1062 ? 'Este código de produto já está cadastrado.' : 'Não foi possível salvar. Confira os dados e tente novamente.');
    }
    ir($base . '/cadastrar.php' . ($id ? '?id=' . $id : ''));
}

if ($acao === 'excluir') {
    exigirPost();
    try {
        $id = inteiro($_POST['id'] ?? '');
        $stmt = consultar("DELETE FROM $modulo WHERE id = ?", [$id]);
        mensagem($stmt->rowCount() ? 'success' : 'danger', $stmt->rowCount() ? 'Registro excluído.' : 'Registro não encontrado.');
    } catch (InvalidArgumentException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        mensagem('danger', $e->getMessage());
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log($e->getMessage());
        mensagem('danger', 'Não foi possível excluir. Registros vinculados a fretes devem ser preservados.');
    }
    ir($base . '/listar.php');
}

if ($acao === 'formulario') {
    $id = 0;
    $dados = array_fill_keys(array_keys($campos), '');
    try {
        if (isset($_GET['id'])) {
            $id = inteiro($_GET['id']);
            $registro = consultar("SELECT * FROM $modulo WHERE id = ?", [$id])->fetch();
            if (!$registro) throw new InvalidArgumentException('Registro não encontrado.');
            $dados = $registro;
        }
    } catch (InvalidArgumentException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        mensagem('danger', $e->getMessage()); ir($base . '/listar.php');
    }
    $antigos = $_SESSION['antigos'][$modulo] ?? null;
    unset($_SESSION['antigos'][$modulo]);
    if (is_array($antigos) && texto($antigos['id'] ?? '') === ($id ? (string)$id : '')) {
        foreach ($campos as $coluna=>$campo) $dados[$coluna] = texto($antigos[$coluna] ?? '');
    }
    $titulo = ($id ? 'Editar ' : 'Cadastrar ') . $config['singular'];
    require __DIR__ . '/header.php';
    ?>
    <div class="cabecalho"><h1><?= h($titulo) ?></h1><a class="btn btn-outline-secondary" href="<?= h(BASE_URL . $base) ?>/listar.php">Voltar</a></div>
    <div class="card"><div class="card-body">
    <p class="text-secondary">Campos com * são obrigatórios.</p>
    <form method="post" action="<?= h(BASE_URL . $base) ?>/salvar.php" class="row g-3">
    <?= csrf() ?><input type="hidden" name="id" value="<?= $id ?: '' ?>">
    <?php foreach ($campos as $coluna=>$campo): [$rotulo,$tipo,$limite] = $campo; $obrigatorio = $campo[3] ?? true; ?>
    <div class="<?= $tipo === 'textarea' ? 'col-12' : 'col-md-6' ?>">
    <label class="form-label" for="<?= h($coluna) ?>"><?= h($rotulo) ?><?= $obrigatorio ? ' *' : '' ?></label>
    <?php if (in_array($tipo, ['tipo','uf'], true)): ?>
        <select class="form-select" id="<?= h($coluna) ?>" name="<?= h($coluna) ?>" required>
        <option value="">Selecione</option>
        <?php foreach ($tipo === 'uf' ? ufs() : ['origem','destino'] as $opcao): ?>
        <option value="<?= h($opcao) ?>" <?= $dados[$coluna] === $opcao ? 'selected' : '' ?>><?= h(ucfirst($opcao)) ?></option>
        <?php endforeach; ?></select>
    <?php elseif ($tipo === 'textarea'): ?>
        <textarea class="form-control" id="<?= h($coluna) ?>" name="<?= h($coluna) ?>" rows="3" maxlength="<?= $limite ?>"><?= h($dados[$coluna]) ?></textarea>
    <?php else: $numerico = in_array($tipo, ['peso','dinheiro'], true); ?>
        <input class="form-control" type="<?= $numerico ? 'number' : 'text' ?>" id="<?= h($coluna) ?>" name="<?= h($coluna) ?>" value="<?= h($dados[$coluna]) ?>" <?= $obrigatorio ? 'required' : '' ?> <?= $numerico ? 'min="0" max="' . ($tipo === 'peso' ? '999999.999' : '999999999.99') . '" step="' . ($tipo === 'peso' ? '0.001' : '0.01') . '"' : 'maxlength="' . $limite . '"' ?>>
    <?php endif; ?>
    </div>
    <?php endforeach; ?>
    <div class="col-12 acoes"><button class="btn btn-primary">Salvar</button><a class="btn btn-outline-secondary" href="<?= h(BASE_URL . $base) ?>/listar.php">Cancelar</a></div>
    </form></div></div>
    <?php require __DIR__ . '/footer.php'; return;
}

$titulo = $config['titulo'];
$registros = consultar("SELECT * FROM $modulo ORDER BY id DESC")->fetchAll();
require __DIR__ . '/header.php';
?>
<div class="cabecalho"><h1><?= h($titulo) ?></h1><a class="btn btn-primary" href="<?= h(BASE_URL . $base) ?>/cadastrar.php">Cadastrar <?= h(strtolower($config['singular'])) ?></a></div>
<div class="card"><div class="card-body table-responsive">
<table class="table table-hover mb-0"><thead><tr><th>ID</th>
<?php foreach ($campos as $campo): ?><th><?= h($campo[0]) ?></th><?php endforeach; ?>
<th>Ações</th></tr></thead><tbody>
<?php if (!$registros): ?><tr><td colspan="<?= count($campos)+2 ?>" class="text-center py-4">Nenhum registro cadastrado.</td></tr><?php endif; ?>
<?php foreach ($registros as $registro): ?>
<tr><td><?= (int)$registro['id'] ?></td>
<?php foreach ($campos as $coluna=>$campo): ?>
<td><?= h($campo[1] === 'dinheiro' ? moeda($registro[$coluna]) : $registro[$coluna]) ?></td>
<?php endforeach; ?>
<td><div class="acoes"><a class="btn btn-sm btn-outline-primary" href="<?= h(BASE_URL . $base) ?>/editar.php?id=<?= (int)$registro['id'] ?>">Editar</a>
<form method="post" action="<?= h(BASE_URL . $base) ?>/excluir.php" onsubmit="return confirm('Excluir este registro?');"><?= csrf() ?><input type="hidden" name="id" value="<?= (int)$registro['id'] ?>"><button class="btn btn-sm btn-outline-danger">Excluir</button></form>
</div></td></tr>
<?php endforeach; ?>
</tbody></table></div></div>
<?php require __DIR__ . '/footer.php'; ?>
```

### gerenciador_logistico/assets/css/estilo.css

```css
:root { --bs-primary: #175cbd; }
body { background:#f3f6fb; color:#24344d; min-height:100vh; display:flex; flex-direction:column; }
.bg-primary { background-color:#175cbd!important; }
.btn-primary { background:#175cbd; border-color:#175cbd; }
.btn-primary:hover { background:#124b9c; border-color:#124b9c; }
.card { border:1px solid #e1e8f2; border-radius:14px; box-shadow:0 4px 16px #14345a08; }
h1 { font-size:1.8rem; font-weight:700; }
.table th { background:#edf3fd; white-space:nowrap; color:#344b6c; }
.table td { vertical-align:middle; }
.table-responsive { border-radius:10px; }
.resumo-numero { font-size:2.5rem; font-weight:700; color:#175cbd; }
.acoes { display:flex; flex-wrap:wrap; gap:.4rem; }
.form-label { font-weight:600; }
.cabecalho { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1rem; margin-bottom:1.5rem; }
.hero { background:#e8f0ff; border-left:4px solid #175cbd; padding:1.5rem; border-radius:12px; }
.item-produto { background:#f7f9fd; border:1px solid #dce5f2; border-radius:10px; padding:1rem; }
@media(max-width:575px) { h1 {font-size:1.5rem} .card-body {padding:1rem} }
```

### gerenciador_logistico/assets/js/frete.js

```javascript
'use strict';
document.addEventListener('DOMContentLoaded', () => {
    const lista = document.getElementById('itens');
    const modelo = document.getElementById('modelo-item');
    const adicionar = document.getElementById('adicionar-produto');
    if (!lista || !modelo || !adicionar) return;
    const atualizar = () => {
        const linhas = lista.querySelectorAll('.item-produto');
        linhas.forEach((linha, i) => {
            const select = linha.querySelector('select');
            const quantidade = linha.querySelector('input');
            select.id = `produto-${i}`;
            quantidade.id = `quantidade-${i}`;
            linha.querySelector('[data-label-produto]').htmlFor = select.id;
            linha.querySelector('[data-label-quantidade]').htmlFor = quantidade.id;
            linha.querySelector('[data-remover]').disabled = linhas.length === 1;
        });
        adicionar.disabled = linhas.length >= 100;
        document.getElementById('contador-itens').textContent = `${linhas.length} linha(s) de produto`;
    };
    adicionar.addEventListener('click', () => {
        if (lista.children.length >= 100) return;
        lista.appendChild(modelo.content.cloneNode(true));
        atualizar();
        lista.lastElementChild.querySelector('select').focus();
    });
    lista.addEventListener('click', event => {
        const botao = event.target.closest('[data-remover]');
        if (botao && lista.children.length > 1) { botao.closest('.item-produto').remove(); atualizar(); }
    });
    atualizar();
});
```

### gerenciador_logistico/index.php

```php
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
```

### gerenciador_logistico/produtos/listar.php

```php
<?php
$modulo = 'produtos';
$acao = 'listar';
require __DIR__ . '/../includes/crud.php';
```

### gerenciador_logistico/produtos/cadastrar.php

```php
<?php
$modulo = 'produtos';
$acao = 'formulario';
require __DIR__ . '/../includes/crud.php';
```

### gerenciador_logistico/produtos/editar.php

```php
<?php
$modulo = 'produtos';
$acao = 'formulario';
require __DIR__ . '/../includes/crud.php';
```

### gerenciador_logistico/produtos/salvar.php

```php
<?php
$modulo = 'produtos';
$acao = 'salvar';
require __DIR__ . '/../includes/crud.php';
```

### gerenciador_logistico/produtos/excluir.php

```php
<?php
$modulo = 'produtos';
$acao = 'excluir';
require __DIR__ . '/../includes/crud.php';
```

### gerenciador_logistico/enderecos/listar.php

```php
<?php
$modulo = 'enderecos';
$acao = 'listar';
require __DIR__ . '/../includes/crud.php';
```

### gerenciador_logistico/enderecos/cadastrar.php

```php
<?php
$modulo = 'enderecos';
$acao = 'formulario';
require __DIR__ . '/../includes/crud.php';
```

### gerenciador_logistico/enderecos/editar.php

```php
<?php
$modulo = 'enderecos';
$acao = 'formulario';
require __DIR__ . '/../includes/crud.php';
```

### gerenciador_logistico/enderecos/salvar.php

```php
<?php
$modulo = 'enderecos';
$acao = 'salvar';
require __DIR__ . '/../includes/crud.php';
```

### gerenciador_logistico/enderecos/excluir.php

```php
<?php
$modulo = 'enderecos';
$acao = 'excluir';
require __DIR__ . '/../includes/crud.php';
```

### gerenciador_logistico/transportadores/listar.php

```php
<?php
$modulo = 'transportadores';
$acao = 'listar';
require __DIR__ . '/../includes/crud.php';
```

### gerenciador_logistico/transportadores/cadastrar.php

```php
<?php
$modulo = 'transportadores';
$acao = 'formulario';
require __DIR__ . '/../includes/crud.php';
```

### gerenciador_logistico/transportadores/editar.php

```php
<?php
$modulo = 'transportadores';
$acao = 'formulario';
require __DIR__ . '/../includes/crud.php';
```

### gerenciador_logistico/transportadores/salvar.php

```php
<?php
$modulo = 'transportadores';
$acao = 'salvar';
require __DIR__ . '/../includes/crud.php';
```

### gerenciador_logistico/transportadores/excluir.php

```php
<?php
$modulo = 'transportadores';
$acao = 'excluir';
require __DIR__ . '/../includes/crud.php';
```

### gerenciador_logistico/fretes/listar.php

```php
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
```

### gerenciador_logistico/fretes/novo.php

```php
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
```

### gerenciador_logistico/fretes/salvar.php

```php
<?php
require_once __DIR__ . '/../includes/funcoes.php';
exigirPost();
$_SESSION['antigos_frete'] = $_POST;
try {
    $origem = inteiro($_POST['id_origem'] ?? '');
    $destino = inteiro($_POST['id_destino'] ?? '');
    $transportador = inteiro($_POST['id_transportador'] ?? '');
    $data = dataValida($_POST['data_frete'] ?? '');
    $valor = decimal($_POST['valor_frete'] ?? '');
    $status = texto($_POST['status'] ?? '');
    if (!in_array($status, estadosFrete(), true)) throw new InvalidArgumentException('Status inválido.');
    $ids = $_POST['produtos'] ?? null;
    $quantidades = $_POST['quantidades'] ?? null;
    if (!is_array($ids) || !is_array($quantidades) || !$ids || count($ids) > 100 || count($ids) !== count($quantidades)) {
        throw new InvalidArgumentException('Informe de 1 a 100 linhas com produto e quantidade.');
    }
    $ids = array_values($ids);
    $quantidades = array_values($quantidades);
    $itens = [];
    foreach ($ids as $i=>$id) {
        $id = inteiro($id);
        $qtd = inteiro($quantidades[$i], 1000000);
        $itens[$id] = ($itens[$id] ?? 0) + $qtd;
        if ($itens[$id] > 1000000) throw new InvalidArgumentException('Limite de 1.000.000 unidades por produto.');
    }
    ksort($itens);
    $pdo->beginTransaction();
    // Bloqueia os cadastros durante a gravação e registra os valores dos produtos.
    if (!consultar('SELECT id FROM enderecos WHERE id = ? AND tipo = ? FOR UPDATE', [$origem,'origem'])->fetch()) throw new InvalidArgumentException('Origem inválida.');
    if (!consultar('SELECT id FROM enderecos WHERE id = ? AND tipo = ? FOR UPDATE', [$destino,'destino'])->fetch()) throw new InvalidArgumentException('Destino inválido.');
    if (!consultar('SELECT id FROM transportadores WHERE id = ? FOR UPDATE', [$transportador])->fetch()) throw new InvalidArgumentException('Transportador inválido.');
    consultar('INSERT INTO fretes (id_origem,id_destino,id_transportador,data_frete,valor_frete,status) VALUES (?,?,?,?,?,?)', [$origem,$destino,$transportador,$data,$valor,$status]);
    $frete = (int)$pdo->lastInsertId();
    foreach ($itens as $id=>$qtd) {
        $p = consultar('SELECT * FROM produtos WHERE id = ? FOR UPDATE', [$id])->fetch();
        if (!$p) throw new InvalidArgumentException('Um produto não existe mais. Atualize o formulário.');
        consultar('INSERT INTO frete_produtos (id_frete,id_produto,quantidade,nome_produto,codigo_produto,peso_unitario,valor_unitario) VALUES (?,?,?,?,?,?,?)', [$frete,$id,$qtd,$p['nome'],$p['codigo'],$p['peso'],$p['valor']]);
    }
    $pdo->commit();
    unset($_SESSION['antigos_frete']);
    mensagem('success', 'Frete gerado com sucesso.');
    ir('/fretes/detalhes.php?id=' . $frete);
} catch (InvalidArgumentException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    mensagem('danger', $e->getMessage());
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log($e->getMessage());
    mensagem('danger', 'Não foi possível gerar o frete. Nenhum item foi salvo. Tente novamente.');
}
ir('/fretes/novo.php');
```

### gerenciador_logistico/fretes/detalhes.php

```php
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
```

### gerenciador_logistico/relatorios/frete_dia.php

```php
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
```

### gerenciador_logistico/README.md

````markdown
# LogiControl — Gerenciador Logístico

Atividade Prática Orientada de Fundamentos de Programação para Internet do curso Tecnologia em Análise e Desenvolvimento de Sistemas — UNIPAR EAD.

## Descrição
Sistema local para cadastrar produtos, endereços de origem e destino e transportadores, gerar fretes com vários produtos e baixar relatório diário em CSV.

## Tecnologias
- PHP puro 7.4 ou superior; recomendado PHP 8.2 de 64 bits.
- MySQL 5.7/8.0 ou MariaDB 10.4 ou superior, tabelas InnoDB.
- PDO e extensão pdo_mysql habilitados.
- HTML5, CSS3 e JavaScript puro.
- Bootstrap 5.3.3 via CDN.
- XAMPP no Windows; Git e GitHub para versionamento.

## Funcionalidades
- Dashboard com contadores dos quatro cadastros principais.
- Cadastro, listagem, edição e exclusão de produtos, endereços e transportadores.
- Menus presentes em todas as páginas de interface.
- Origem e destino filtrados por tipo de endereço.
- Um transportador por frete e vários produtos com suas quantidades.
- Adição e remoção de linhas de produto com JavaScript.
- Gravação atômica do frete e itens com transação PDO.
- Listagem e detalhes com unidades, peso e valor das mercadorias.
- Relatório por data do frete, com consulta e download CSV para Excel.
- Campos obrigatórios validados no servidor, saída HTML escapada, consultas preparadas e proteção CSRF.

## Instalação no XAMPP
1. Instale e abra o XAMPP no Windows.
2. Copie a pasta `gerenciador_logistico` para `C:\xampp\htdocs\`.
3. No XAMPP Control Panel, inicie Apache e MySQL.
4. Abra `http://localhost/phpmyadmin/`.
5. Na aba Importar, selecione `C:\xampp\htdocs\gerenciador_logistico\database\script.sql` e execute.
6. O script cria o banco `gerenciador_logistico` e as cinco tabelas; não apaga dados existentes.
7. Confira `config/conexao.php`: host `127.0.0.1`, porta `3306`, usuário `root`, senha vazia no XAMPP padrão.
8. Acesse `http://localhost/gerenciador_logistico/`.

Se o MySQL tiver senha ou porta diferente, ajuste a configuração ou as variáveis `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` e `DB_PASS` do ambiente do Apache. Não publique senhas reais no GitHub. A configuração entregue contém apenas os padrões locais do XAMPP.

Se mudar o nome da pasta ou usar subdiretório diferente, ajuste `BASE_URL` em `includes/funcoes.php`. Todos os arquivos devem ser salvos em UTF-8 sem BOM; apenas o download CSV recebe BOM. O Bootstrap exige internet para carregar CSS e menu responsivo. O projeto não depende de Composer, npm, rotas amigáveis ou regras de reescrita.

## Primeiro uso
1. Cadastre dois produtos com códigos diferentes.
2. Cadastre um endereço de tipo origem e outro de tipo destino.
3. Cadastre um transportador.
4. Em Fretes → Gerar frete, selecione os três cadastros e preencha data, valor e status.
5. Selecione o primeiro produto, informe quantidade e use Adicionar produto para o segundo.
6. Salve e confira os detalhes.
7. Em Relatório, escolha a mesma data, consulte e baixe o CSV.

## Regras e limites
- CPF, CNPJ e CEP são armazenados como texto, sem validação de autenticidade ou chamadas a APIs.
- Descrição do produto é opcional; demais campos dos cadastros são obrigatórios.
- Valores e pesos não negativos; quantidades inteiras positivas.
- Código do produto é único. Não existe controle de estoque.
- Até 100 linhas por frete; produtos repetidos são agrupados e têm as quantidades somadas.
- Até 1.000.000 de unidades por produto no mesmo frete.
- Peso unitário: até 999.999,999 kg; valores monetários: até R$ 999.999.999,99.
- Os limites dos textos são verificados em bytes no PHP; caracteres acentuados podem ocupar mais de um byte.
- Exclusões de cadastros utilizados em fretes são bloqueadas por chaves estrangeiras.
- O tipo de endereço utilizado em um frete não pode ser alterado.
- Itens guardam nome, código, peso e valor do produto na geração, preservando seus totais após edição do cadastro.
- Endereços e transportador exibem os dados cadastrais atuais.
- O preço das mercadorias e o valor do serviço de frete aparecem separadamente.
- Status é escolhido na geração. Edição e exclusão de fretes não fazem parte do escopo solicitado.
- O relatório utiliza `data_frete`, não a data de criação, e inclui todos os status.
- CSV: separador ponto e vírgula, BOM UTF-8, decimais com vírgula, uma linha por frete e proteção contra fórmulas em nomes.
- Uma data sem fretes apresenta mensagem e não inicia download vazio.
- Não há autenticação. O sistema se destina ao uso acadêmico local.

## Estrutura
```text
gerenciador_logistico/
  config/conexao.php
  includes/
    funcoes.php
    crud.php
    header.php
    menu.php
    footer.php
  produtos/
    listar.php
    cadastrar.php
    editar.php
    salvar.php
    excluir.php
  enderecos/
    listar.php
    cadastrar.php
    editar.php
    salvar.php
    excluir.php
  transportadores/
    listar.php
    cadastrar.php
    editar.php
    salvar.php
    excluir.php
  fretes/
    listar.php
    novo.php
    salvar.php
    detalhes.php
  relatorios/frete_dia.php
  assets/css/estilo.css
  assets/js/frete.js
  database/script.sql
  docs/commits.md
  docs/roteiro_video.md
  docs/telas_figma.md
  index.php
  README.md
  .gitignore
```

## Organização do código
`config/conexao.php` cria a conexão PDO. `includes/funcoes.php` reúne consultas, validações, sessão, mensagens e esquema dos cadastros. `includes/crud.php` executa os três CRUDs com uma lista fechada de tabelas e colunas. Os arquivos de cada módulo definem o módulo e a ação e carregam essa implementação compartilhada. `fretes/salvar.php` valida referências e salva o frete e seus itens na mesma transação. `assets/js/frete.js` controla as linhas. `relatorios/frete_dia.php` consulta e exporta o relatório antes de enviar qualquer HTML.

## Verificação local de entrega
- Cadastre, edite e exclua um registro livre de cada módulo.
- Tente salvar produto com código repetido e campos obrigatórios vazios.
- Gere um frete com dois produtos e quantidades diferentes.
- Confira peso, unidades e valor das mercadorias nos detalhes.
- Repita um produto e confira o agrupamento das quantidades.
- Tente excluir um cadastro vinculado e alterar o tipo de endereço utilizado.
- Edite preço de um produto e confira que o frete antigo mantém seus valores.
- Consulte uma data com fretes e abra o CSV no Excel.
- Consulte uma data sem fretes.
- Confira menu e formulários em largura de celular.

Os arquivos foram inspecionados e o JavaScript passou pela verificação de sintaxe do Node.js. PHP e MySQL não estavam disponíveis no ambiente de geração; o teste integrado no XAMPP não foi executado.

## Versionamento e entrega
Mensagens e comandos estão em `docs/commits.md`. Publique o repositório como público e envie seu link no ambiente virtual. Não foram criados repositório remoto nem commits em seu nome.

O roteiro de vídeo de oito minutos está em `docs/roteiro_video.md`. Apareça no início, informe os dados acadêmicos, demonstre a aplicação e publique no YouTube como Não Listado. Envie o link no ambiente virtual.

As especificações de todas as telas estão em `docs/telas_figma.md`. Monte o protótipo, compare com a aplicação e exporte as telas em PDF para a entrega da etapa de análise. O PDF de telas e o vídeo são entregas a produzir a partir desses materiais.

## Autoria
[SEU NOME] – UNIPAR EAD
````

### gerenciador_logistico/.gitignore

```gitignore
# Arquivos locais e credenciais
.env
.env.*
!.env.example
config/conexao.local.php
*.log
*.zip
*.bak
*.sql.gz
.vscode/
.idea/
.DS_Store
Thumbs.db
```

### gerenciador_logistico/docs/commits.md

````markdown
# Versionamento

## Dez mensagens de commit
1. `chore: iniciar estrutura de pastas do projeto`
2. `feat: criar banco de dados e relacionamentos`
3. `feat: configurar conexão PDO e validações compartilhadas`
4. `feat: adicionar navegação responsiva e painel inicial`
5. `feat: implementar cadastro e manutenção de produtos`
6. `feat: implementar cadastro e manutenção de endereços`
7. `feat: implementar cadastro e manutenção de transportadores`
8. `feat: gerar fretes com múltiplos produtos e transação`
9. `feat: adicionar detalhes e relatório diário em CSV`
10. `docs: documentar instalação roteiro de vídeo e telas`

## Comandos para o projeto completo
Execute na pasta `gerenciador_logistico`. Substitua a URL pelo endereço real do repositório público, já criado e vazio no GitHub.

```bash
git init
git add .
git commit -m "feat: entregar gerenciador logístico completo"
git branch -M main
git remote add origin "[URL DO SEU REPOSITÓRIO]"
git push -u origin main
```

## Durante o desenvolvimento
Execute `git add` e `git commit` após cada alteração real, usando a mensagem correspondente à etapa. Não recrie artificialmente etapas que já foram concluídas.

```bash
git add .
git commit -m "docs: atualizar documentação do projeto"
git push
```
````

### gerenciador_logistico/docs/roteiro_video.md

````markdown
# Roteiro do vídeo — 8 minutos

- **00:00–00:45 — Apresentação; câmera ligada.**
  “Olá, meu nome é [SEU NOME], meu RA é [SEU RA]. Sou aluno da UNIPAR EAD, do curso Tecnologia em Análise e Desenvolvimento de Sistemas, polo [SEU POLO]. Vou apresentar o LogiControl, desenvolvido para a Atividade Prática Orientada da disciplina Fundamentos de Programação para Internet.”
- **00:45–01:20 — Objetivo e início; compartilhar a tela.**
  “Este sistema organiza produtos, endereços e transportadores e relaciona esses cadastros em fretes. Utilizei PHP puro, MySQL, HTML, CSS, JavaScript e Bootstrap. A execução é local com Apache e MySQL do XAMPP. A página inicial mostra os totais e o menu permite acessar cada função.”
- **01:20–02:20 — Cadastrar um produto.**
  Abrir Produtos → Cadastrar. Preencher código `DEMO-VIDEO`, nome `Caixa organizadora`, descrição `Produto utilizado na apresentação`, peso `2.500` e valor `35.90`. Salvar e mostrar a mensagem e a linha na tabela.
  “Os campos obrigatórios são conferidos no PHP. O código identifica o produto e não pode ser repetido. Nesta listagem também posso editar e excluir um registro que não esteja vinculado a fretes.”
- **02:20–03:00 — Endereços e transportador.**
  Mostrar uma origem, um destino e um transportador já cadastrados. Deixar um segundo produto pronto antes da gravação.
  “O endereço possui um tipo: origem ou destino. O transportador possui nome, documento, telefone, veículo e placa. CPF, CNPJ e CEP não têm validação de autenticidade, conforme o escopo da atividade.”
- **03:00–04:25 — Gerar frete com dois produtos.**
  Abrir Fretes → Gerar frete. Selecionar origem, destino e transportador. Informar a data do dia da gravação, frete `120.00` e status `Pendente`. Adicionar o produto demonstrado, quantidade `3`, e um segundo produto, quantidade `2`. Mostrar adicionar/remover linha sem apagar todos os itens e salvar.
  “Um único transportador leva vários produtos neste mesmo frete. O JavaScript adiciona e remove as linhas. Ao salvar, o PHP valida os dados e utiliza uma transação: o frete e os itens são gravados juntos ou a operação é desfeita.”
- **04:25–05:10 — Detalhes e relatório.**
  Mostrar origem, destino, transportador, produtos e totais. Abrir Relatório e selecionar a mesma data. Consultar, baixar o CSV e abrir no Excel.
  “Os detalhes mostram as unidades, o peso e o valor das mercadorias. O preço do serviço de frete aparece separado. O relatório considera a data do frete e permite baixar um CSV. Quando não há registros, o sistema informa isso na tela.”
- **05:10–06:10 — Comparação com o Figma.**
  Abrir o protótipo produzido a partir de `docs/telas_figma.md`, lado a lado com a aplicação. Comparar início, produtos e geração de frete.
  “No Figma, planejei o menu no topo, os cartões da página inicial, as tabelas e os formulários. Aqui é possível comparar a posição dos elementos, a cor azul e a organização dos campos. Na tela de frete, a área de produtos permite adicionar várias linhas. As telas foram exportadas em PDF para a etapa de análise.”
  Caso existam diferenças reais, apontá-las: “Neste ponto, a implementação apresenta esta diferença em relação ao protótipo: [DESCREVA A DIFERENÇA OBSERVADA].” Não declarar fidelidade que não tenha conferido.
- **06:10–07:35 — Mostrar rapidamente o código.**
  No editor, abrir os arquivos citados e destacar as funções.
  “Em config/conexao.php fica a conexão PDO. Em includes estão o cabeçalho, menu, rodapé e as funções compartilhadas. A função consultar usa prepare e execute; a função h protege a saída HTML. O arquivo crud.php compartilha os cadastros de produtos, endereços e transportadores. Cada pasta contém os arquivos para listar, cadastrar, editar, salvar e excluir. Em fretes/novo.php está o formulário; em assets/js/frete.js estão os eventos que alteram as linhas. O arquivo fretes/salvar.php começa a transação, grava o frete e percorre os produtos. Em relatorios/frete_dia.php o fputcsv gera o arquivo com BOM UTF-8. Em database/script.sql estão as cinco tabelas e suas chaves estrangeiras.”
- **07:35–08:00 — Repositório e encerramento.**
  Mostrar o repositório público real e o README.
  “O repositório contém o código e o README com a instalação no XAMPP. Os arquivos de documentação incluem as telas e o roteiro. Este foi o LogiControl, apresentado para a disciplina Fundamentos de Programação para Internet da UNIPAR EAD. Obrigado.”

## Antes de gravar
- Preencher nome, RA e polo na fala; ensaiar mantendo duração entre 5 e 10 minutos.
- Executar a aplicação e conferir todos os dados da demonstração.
- Ter ao menos dois produtos, uma origem, um destino e um transportador.
- Montar o Figma e exportar o PDF antes de declarar que essa etapa foi concluída.
- Manter o editor, Figma, navegador e repositório abertos.
- Usar dados fictícios, sem expor documentos pessoais ou senhas.
- Publicar no YouTube como Não Listado e enviar o link no ambiente virtual.
````

### gerenciador_logistico/docs/telas_figma.md

````markdown
# Especificação das telas — LogiControl

## Padrão visual
- Frame desktop: 1440 × 900 px; altura livre para páginas longas.
- Frame celular: 390 × 844 px; rolagem vertical.
- Fundo: `#F3F6FB`; azul principal: `#175CBD`; texto: `#24344D`.
- Fonte: Arial ou fonte sem serifa; texto 16 px, títulos 28–30 px.
- Barra superior: x=0, y=0, largura total, altura aproximada 64 px.
- Marca LogiControl à esquerda; menu à direita: Início, Produtos, Endereços, Transportadores, Fretes, Relatório.
- Conteúdo desktop: x=150, y=96, largura aproximada 1140 px.
- Cartões brancos com borda suave, raio 14 px e espaçamento interno de 24 px.
- Títulos à esquerda; ação principal azul à direita; botões secundários contornados.
- Mensagens de sucesso verdes e erros vermelhos acima do título; avisos em amarelo.
- Rodapé ao final da página: LogiControl • Gerenciador Logístico • UNIPAR EAD.
- Mobile: margens 16 px, menu recolhível, campos e cartões empilhados, tabelas com rolagem horizontal.

## 1. Início — index.php
- Topo: barra padrão.
- x=150, y=96: bloco azul-claro com título “Gestão logística em um só lugar” e descrição curta.
- y≈250: quatro cartões em uma linha, cada um com título, contador grande azul e link: Produtos, Endereços, Transportadores e Fretes.
- y≈450: cartão “Próximas ações”, texto e botões Gerar frete e Relatório por dia.
- Mobile: cartões em uma coluna; botões com quebra de linha.

## 2. Produtos — produtos/listar.php
- Cabeçalho do conteúdo: Produtos à esquerda; Cadastrar produto à direita.
- Abaixo: cartão com tabela ID | Código | Nome | Descrição | Peso unitário (kg) | Valor unitário (R$) | Ações.
- Cada linha: botões Editar e Excluir; exclusão abre confirmação nativa do navegador.
- Sem dados: linha central “Nenhum registro cadastrado”.

## 3. Cadastrar produto — produtos/cadastrar.php
- Cabeçalho: Cadastrar Produto à esquerda e Voltar à direita.
- Cartão abaixo com indicação de campos obrigatórios.
- Linha 1: Código* à esquerda e Nome* à direita.
- Linha 2: Descrição opcional ocupando a largura inteira, com área de texto.
- Linha 3: Peso unitário (kg)* à esquerda e Valor unitário (R$)* à direita.
- Rodapé do formulário: Salvar e Cancelar alinhados à esquerda.

## 4. Editar produto — produtos/editar.php?id=ID
- Mesma composição do cadastro, título Editar Produto.
- Campos preenchidos com os valores do registro; ID mantido como campo oculto.
- Botões Salvar, Cancelar e Voltar.

## 5. Endereços — enderecos/listar.php
- Cabeçalho: Endereços e Cadastrar endereço.
- Cartão com tabela ID | Tipo | Identificação | Rua | Número | Bairro | Cidade | Estado | CEP | Ações.
- Editar e Excluir em cada linha; estado vazio e mensagens acima do conteúdo.

## 6. Cadastrar endereço — enderecos/cadastrar.php
- Cabeçalho: Cadastrar Endereço e Voltar.
- Formulário em duas colunas.
- Linha 1: Tipo* (select Origem/Destino) | Identificação*.
- Linha 2: Rua* | Número*; aceitar número textual como S/N.
- Linha 3: Bairro* | Cidade*.
- Linha 4: Estado* (select de UFs) | CEP*.
- Parte inferior: Salvar e Cancelar.

## 7. Editar endereço — enderecos/editar.php?id=ID
- Mesma composição do cadastro com os valores preenchidos.
- Título Editar Endereço e ID oculto.
- Caso altere o tipo de endereço já utilizado, mensagem de erro acima do formulário.

## 8. Transportadores — transportadores/listar.php
- Cabeçalho: Transportadores e Cadastrar transportador.
- Tabela ID | Nome | CPF ou CNPJ | Telefone | Veículo | Placa | Ações.
- Editar e Excluir por linha; mensagem para exclusão bloqueada por vínculo.

## 9. Cadastrar transportador — transportadores/cadastrar.php
- Cabeçalho: Cadastrar Transportador e Voltar.
- Linha 1: Nome* | CPF ou CNPJ*.
- Linha 2: Telefone* | Veículo*.
- Linha 3: Placa* na primeira coluna.
- Parte inferior: Salvar e Cancelar.

## 10. Editar transportador — transportadores/editar.php?id=ID
- Mesma composição do cadastro, com título Editar Transportador.
- Campos preenchidos; ID oculto; botões Salvar e Cancelar.

## 11. Fretes — fretes/listar.php
- Cabeçalho: Fretes à esquerda e Gerar frete à direita.
- Tabela ID | Data | Origem | Destino | Transportador | Valor do frete | Status | Detalhes.
- Status com selo claro. Detalhes como botão contornado.
- Estado vazio: “Nenhum frete gerado”.

## 12. Gerar frete — fretes/novo.php
- Cabeçalho: Gerar frete e Voltar.
- Cartão com seção Dados do frete.
- Linha 1 em três colunas: Origem* | Destino* | Transportador*.
- Linha 2 em três colunas: Data do frete* | Valor do frete (R$)* | Status*.
- Abaixo: título Produtos transportados e instrução sobre agrupamento e valor do frete separado.
- Linha dinâmica dentro de bloco cinza-claro: Produto* com cerca de 58% da largura | Quantidade* com 25% | Remover com 17%.
- Abaixo das linhas: Adicionar produto à esquerda e contador de linhas ao lado.
- Rodapé do cartão: Salvar frete.
- Variante com cadastros ausentes: aviso amarelo antes do formulário; campos desabilitados.
- Mobile: campos, seletor, quantidade e Remover empilhados.

## 13. Detalhes do frete — fretes/detalhes.php?id=ID
- Cabeçalho: Frete #ID e Voltar.
- Primeiro cartão, três colunas: Data | Status | Valor do frete.
- Segunda faixa: cartão Origem à esquerda e Destino à direita; identificação e endereço completo.
- Terceira faixa: cartão Transportador, largura total, com documento, telefone, veículo e placa.
- Quarta faixa: cartão Produtos com tabela Código | Produto | Qtd. | Peso unitário (kg) | Valor unitário | Subtotal.
- Abaixo da tabela: Total de unidades | Peso total | Valor das mercadorias.
- Nota discreta sobre valores dos produtos na geração e dados cadastrais atuais.

## 14. Relatório — relatorios/frete_dia.php
- Título: Relatório de fretes por dia.
- Cartão com descrição e linha de formulário: Data do frete (largura aproximada 40%) | Consultar | Baixar CSV.
- Após consulta: cartão com total de fretes e data; tabela Frete | Transportador | Status | Valor do frete.
- ID de cada frete com link para detalhes.
- Variante sem registros: “Nenhum frete encontrado nesta data”.
- Variante de download sem registros: aviso amarelo, sem baixar arquivo vazio.

## Estados adicionais
- Salvar com sucesso: alerta verde e retorno à listagem ou aos detalhes do frete.
- Erro de validação: alerta vermelho; formulário preserva os dados digitados.
- Excluir: caixa de confirmação do navegador, com Cancelar e OK.
- Cadastro vinculado: alerta vermelho ao tentar excluir, preservando o registro.
- ID inexistente: retorno à listagem com mensagem.

## Frames a exportar
Crie os 14 frames principais acima e, se desejar, variantes mobile e de erro. Nomeie os frames com número e título. Exporte as telas selecionadas em PDF para a etapa de análise. Esta especificação descreve o projeto entregue e não representa uma inspeção do protótipo Figma anterior.
````
