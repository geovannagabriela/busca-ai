<?php
require_once "funcoes.php";

iniciar_sessao();

// Encerra apenas o acesso empresarial; o acesso interno (se existir) é preservado.
unset($_SESSION["empresa_id"], $_SESSION["empresa_nome"]);
session_regenerate_id(true);

if (empty($_SESSION["busca_ai"])) {
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $p = session_get_cookie_params();
        setcookie(session_name(), "", [
            "expires" => time() - 42000,
            "path" => $p["path"],
            "domain" => $p["domain"],
            "secure" => $p["secure"],
            "httponly" => $p["httponly"],
            "samesite" => $p["samesite"] ?? "Lax"
        ]);
    }

    session_destroy();
}

header("Location: login.php");
exit;
