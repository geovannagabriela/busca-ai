<?php



function iniciar_sessao(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            "httponly" => true,
            "samesite" => "Lax"
        ]);
        session_start();
    }
}

function escapar($valor): string
{
    return htmlspecialchars((string) ($valor ?? ""), ENT_QUOTES, "UTF-8");
}


function exigir_empresa(): int
{
    iniciar_sessao();

    $id = filter_var($_SESSION["empresa_id"] ?? null, FILTER_VALIDATE_INT);

    if (!$id || $id < 1) {
        unset($_SESSION["empresa_id"], $_SESSION["empresa_nome"]);
        header("Location: login.php");
        exit;
    }

    return (int) $id;
}


function empresa_logada(): ?int
{
    iniciar_sessao();

    $id = filter_var($_SESSION["empresa_id"] ?? null, FILTER_VALIDATE_INT);

    return ($id && $id > 0) ? (int) $id : null;
}


function exigir_interno(): void
{
    iniciar_sessao();

    if (($_SESSION["busca_ai"] ?? false) !== true) {
        header("Location: login-buscaai.php");
        exit;
    }
}



function csrf_token(): string
{
    iniciar_sessao();

    if (empty($_SESSION["csrf_token"]) || !is_string($_SESSION["csrf_token"])) {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    }

    return $_SESSION["csrf_token"];
}

function csrf_campo(): string
{
    return '<input type="hidden" name="csrf_token" value="' .
        escapar(csrf_token()) . '">';
}

function csrf_valido(): bool
{
    iniciar_sessao();

    $enviado = $_POST["csrf_token"] ?? "";
    $esperado = $_SESSION["csrf_token"] ?? "";

    return is_string($enviado)
        && is_string($esperado)
        && $esperado !== ""
        && hash_equals($esperado, $enviado);
}


function preparar(mysqli $conexao, string $sql): mysqli_stmt
{
    $stmt = $conexao->prepare($sql);

    if (!$stmt) {
        throw new RuntimeException(
            "Falha ao preparar consulta (" . $conexao->errno . "): " . $conexao->error
        );
    }

    return $stmt;
}

function executar(mysqli_stmt $stmt): void
{
    if (!$stmt->execute()) {
        throw new RuntimeException(
            "Falha ao executar consulta (" . $stmt->errno . "): " . $stmt->error
        );
    }
}



function formatar_data($data, string $formato = "d/m/Y", string $vazio = "Data indisponível"): string
{
    if (empty($data)) {
        return $vazio;
    }

    $timestamp = strtotime((string) $data);

    return $timestamp === false ? $vazio : date($formato, $timestamp);
}

function classe_status(string $status): string
{
    $mapa = [
        "Solicitação" => "status-solicitacao",
        "Em coleta" => "status-coleta",
        "Triagem" => "status-triagem",
        "Destinação" => "status-destinacao",
        "Recuperação de valor" => "status-recuperacao"
    ];

    return $mapa[trim($status)] ?? "status-outro";
}

function etiqueta_status(string $status): string
{
    return '<span class="status ' . classe_status($status) . '">' .
        escapar($status) . '</span>';
}


function aviso(string $tipo, string $texto): string
{
    $tipo = $tipo === "sucesso" ? "sucesso" : "erro";
    $papel = $tipo === "erro" ? "alert" : "status";

    return '<div class="mensagem-' . $tipo . '" role="' . $papel . '">' .
        escapar($texto) . '</div>';
}





function links_publicos(): array
{
    return [
        ["href" => "inicio.html", "texto" => "Início"],
        ["href" => "como-funciona.html", "texto" => "Como funciona"],
        ["href" => "sobre.html", "texto" => "Sobre nós"],
        ["href" => "contato.php", "texto" => "Contato"],
        ["href" => "login.php", "texto" => "Entrar"],
    ];
}
function links_empresa(): array
{
    return [
        ["href" => "dashboard.php", "texto" => "Dashboard"],
        ["href" => "devolucoes.php", "texto" => "Devoluções"],
        ["href" => "contato.php", "texto" => "Contato"],
        ["href" => "perfil.php", "texto" => "Perfil"],
        ["href" => "logout.php", "texto" => "Sair"]
    ];
}

function links_interno(): array
{
    return [
        ["href" => "painel-buscaai.php", "texto" => "Painel"],
        ["href" => "mensagens-contato.php", "texto" => "Mensagens de contato"],
        ["href" => "logout-buscaai.php", "texto" => "Sair"]
    ];
}

function inicio_documento(string $titulo): void
{
    echo '<!DOCTYPE html>' . "\n";
    echo '<html lang="pt-BR">' . "\n";
    echo '<head>' . "\n";
    echo '    <meta charset="UTF-8">' . "\n";
    echo '    <meta name="viewport" content="width=device-width, initial-scale=1.0">' . "\n";
    echo '    <title>' . escapar($titulo) . '</title>' . "\n";
    echo '    <script>document.documentElement.classList.add("js");</script>' . "\n";
    echo '    <link rel="stylesheet" href="style.css">' . "\n";
    echo '    <script src="script.js" defer></script>' . "\n";
    echo '</head>' . "\n";
    echo '<body>' . "\n";
}

function cabecalho_pagina(array $links, string $logo_href = "inicio.html"): void
{
    $atual = basename($_SERVER["SCRIPT_NAME"] ?? "");

    echo '<header class="cabecalho">' . "\n";
    echo '    <a href="' . escapar($logo_href) . '" class="logo">BUSCA AÍ</a>' . "\n";

    if (!empty($links)) {
        echo '    <button type="button" class="menu-toggle" aria-expanded="false" ' .
            'aria-controls="menu-principal" aria-label="Abrir menu">' .
            '<span class="menu-toggle-barras" aria-hidden="true"></span></button>' . "\n";
        echo '    <nav id="menu-principal" aria-label="Navegação principal">' . "\n";

        foreach ($links as $link) {
            $classes = [];

            if (!empty($link["classe"])) {
                $classes[] = $link["classe"];
            }

            $atributos = "";

            if (basename($link["href"]) === $atual) {
                $classes[] = "pagina-atual";
                $atributos = ' aria-current="page"';
            }

            $classe = $classes ? ' class="' . escapar(implode(" ", $classes)) . '"' : "";

            echo '        <a href="' . escapar($link["href"]) . '"' . $classe . $atributos . '>' .
                escapar($link["texto"]) . '</a>' . "\n";
        }

        echo '    </nav>' . "\n";
    }

    echo '</header>' . "\n";
}

function fim_documento(string $simbolo = "©"): void
{
    echo '<footer class="rodape">' . "\n";
    echo '    <span>Transformando devoluções em valor.</span>' . "\n";
    echo '    <span>' . escapar($simbolo) . ' 2026 BUSCA AÍ. Todos os direitos reservados.</span>' . "\n";
    echo '</footer>' . "\n";
    echo '</body>' . "\n";
    echo '</html>' . "\n";
}
