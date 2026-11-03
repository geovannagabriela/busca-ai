<?php

// Conexão MySQL compatível com XAMPP e hospedagem.
mysqli_report(MYSQLI_REPORT_OFF);

$emRender = getenv("RENDER") === "true";

$servidor = getenv("DB_HOST");
$usuario = getenv("DB_USER");
$senha = getenv("DB_PASSWORD");
$banco = getenv("DB_NAME");
$porta = (int) (getenv("DB_PORT") ?: 3306);

// No XAMPP, preserva a configuração local existente.
if (!$emRender) {
    $servidor = ($servidor !== false && $servidor !== "") ? $servidor : "localhost";
    $usuario = ($usuario !== false && $usuario !== "") ? $usuario : "root";
    $senha = ($senha !== false) ? $senha : "";
    $banco = ($banco !== false && $banco !== "") ? $banco : "busca_ai_novo";
} else {
    // No Render, nunca tenta usar localhost/root: o MySQL precisa ser remoto.
    $faltando = [];
    foreach (["DB_HOST" => $servidor, "DB_USER" => $usuario, "DB_PASSWORD" => $senha, "DB_NAME" => $banco] as $nome => $valor) {
        if ($valor === false || $valor === "") {
            $faltando[] = $nome;
        }
    }

    if ($faltando) {
        error_log("BUSCA AÍ - variáveis de banco ausentes no Render: " . implode(", ", $faltando));
        http_response_code(503);
        exit("Banco de dados ainda não conectado. A configuração do MySQL remoto está pendente.");
    }
}

$conexao = mysqli_init();
if (!$conexao) {
    error_log("BUSCA AÍ - não foi possível inicializar o cliente MySQL.");
    http_response_code(503);
    exit("Serviço temporariamente indisponível. Tente novamente em instantes.");
}

// DB_SSL_CA é opcional e deve apontar para o arquivo CA disponibilizado pelo provedor MySQL.
$caCertificado = getenv("DB_SSL_CA");
$flags = 0;

if ($caCertificado !== false && $caCertificado !== "") {
    if (!is_readable($caCertificado)) {
        error_log("BUSCA AÍ - arquivo informado em DB_SSL_CA não pode ser lido.");
        http_response_code(503);
        exit("Configuração segura do banco de dados incompleta.");
    }

    $conexao->ssl_set(null, null, $caCertificado, null, null);
    $flags = MYSQLI_CLIENT_SSL;
}

$conectado = false;
try {
    $conectado = @$conexao->real_connect(
        $servidor,
        $usuario,
        $senha,
        $banco,
        $porta,
        null,
        $flags
    );
} catch (Throwable $erro) {
    error_log("BUSCA AÍ - exceção ao conectar no MySQL: " . $erro->getMessage());
}

if (!$conectado) {
    error_log(
        "BUSCA AÍ - falha na conexão com MySQL (" .
        $conexao->connect_errno . "): " . $conexao->connect_error
    );

    http_response_code(503);
    exit("Serviço temporariamente indisponível. Tente novamente em instantes.");
}

$conexao->set_charset("utf8mb4");
