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
