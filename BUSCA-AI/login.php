<?php
require_once "funcoes.php";

iniciar_sessao();

$mensagem_erro = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    require_once "conexao.php";

    $email = trim($_POST["email"] ?? "");
    $senha = $_POST["senha"] ?? "";

    if (
        !is_string($email) ||
        !is_string($senha) ||
        $email === "" ||
        $senha === "" ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        strlen($email) > 255 ||
        strlen($senha) > 72
    ) {
        $mensagem_erro = "E-mail ou senha inválidos.";
    } else {
        try {
            $stmt = preparar(
                $conexao,
                "SELECT id, nome_empresa, email, senha
                 FROM empresas
                 WHERE email = ?
                 LIMIT 1"
            );

            $stmt->bind_param("s", $email);
            executar($stmt);

            $empresa = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($empresa && password_verify($senha, $empresa["senha"])) {
                session_regenerate_id(true);

                $_SESSION["empresa_id"] = (int) $empresa["id"];
                $_SESSION["empresa_nome"] = $empresa["nome_empresa"];

                header("Location: dashboard.php");
                exit;
            }

            $mensagem_erro = "E-mail ou senha inválidos.";
        } catch (Throwable $erro) {
            error_log("Erro no login empresarial: " . $erro->getMessage());
            $mensagem_erro = "Não foi possível realizar o login. Tente novamente.";
        }
    }
}

inicio_documento("BUSCA AÍ - Login");
cabecalho_pagina([
    ["href" => "cadastro.php", "texto" => "Criar conta", "classe" => "pagina-btn"]
]);
?>

    <main class="login-container">

        <section class="login-info">

            <span class="etiqueta">
                ACESSO À CONTA
            </span>

            <h1>Acesse sua conta</h1>

            <p>Entre para acessar as funcionalidades da BUSCA AÍ e acompanhar suas devoluções.</p>

            <div class="info-card">
                <span class="numero">1</span>

                <div>
                    <h3>Seguro</h3>
                    <p>Acesso protegido aos seus dados.</p>
                </div>
            </div>

            <div class="info-card">
                <span class="numero">2</span>

                <div>
                    <h3>Prático</h3>
                    <p>Tudo em um só lugar.</p>
                </div>
            </div>

            <div class="info-card">
                <span class="numero">3</span>

                <div>
                    <h3>Transparente</h3>
                    <p>Acompanhe seu processo.</p>
                </div>
            </div>

        </section>

        <div class="login-card">

            <h1>BUSCA AÍ</h1>
            <h2>Acesse sua conta empresarial:</h2>

            <p>Entre para acompanhar suas devoluções!</p>

            <?php if ($mensagem_erro !== "") { echo aviso("erro", $mensagem_erro); } ?>

            <form action="login.php" method="POST">

                <div class="campo">
                    <label for="email">E-mail corporativo</label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Digite o email"
                        maxlength="255"
                        autocomplete="email"
                        value="<?= escapar($_POST["email"] ?? "") ?>"
                        required
                    >
                </div>

                <div class="campo">
                    <label for="senha">Senha</label>

                    <div class="campo-senha">
                        <input
                            type="password"
                            id="senha"
                            name="senha"
                            placeholder="Digite a senha"
                            autocomplete="current-password"
                            required
                        >
                        <button type="button" class="mostrar-senha" data-mostrar-senha="senha" aria-label="Mostrar senha" aria-pressed="false">Mostrar</button>
                    </div>
                </div>

                <button type="submit">
                    Entrar
                </button>

            </form>

            <p class="cadastro">
                Ainda não possui uma conta?
                <a href="cadastro.php">Cadastre-se</a>
            </p>

        </div>

    </main>

<?php fim_documento("@"); ?>
