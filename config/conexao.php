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
