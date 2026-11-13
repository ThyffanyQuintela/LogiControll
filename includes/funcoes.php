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
