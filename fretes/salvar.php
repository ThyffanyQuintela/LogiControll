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
