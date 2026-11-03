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
            // Tabela usada pelo fluxo interno existente. Confirme no phpMyAdmin
            // que "usuarios_buscaai" existe com as colunas id, nome, email e senha.
            $stmt = preparar(
                $conexao,
                "SELECT id, nome, email, senha
                 FROM usuarios_buscaai
                 WHERE email = ?
                 LIMIT 1"
            );

            $stmt->bind_param("s", $email);
            executar($stmt);

            $usuario = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($usuario && password_verify($senha, $usuario["senha"])) {
                session_regenerate_id(true);

                $_SESSION["busca_ai"] = true;
                $_SESSION["busca_ai_nome"] = $usuario["nome"];

                header("Location: painel-buscaai.php");
                exit;
            }

            $mensagem_erro = "E-mail ou senha inválidos.";
        } catch (Throwable $erro) {
            error_log("Erro no login interno: " . $erro->getMessage());
            $mensagem_erro = "Não foi possível realizar o login. Tente novamente.";
        }
    }
}

inicio_documento("BUSCA AÍ - Acesso interno");
cabecalho_pagina([]);
?>

    <main class="login-container">

        <section class="login-info">

            <span class="etiqueta">
                ACESSO INTERNO
            </span>

            <h1>Gestão da BUSCA AÍ</h1>

            <p>
                Entre para acessar o painel interno e gerenciar as devoluções.
            </p>

            <div class="info-card">
                <span class="numero">1</span>
                <div>
                    <h3>Devoluções</h3>
                    <p>Visualize as devoluções solicitadas.</p>
                </div>
            </div>

            <div class="info-card">
                <span class="numero">2</span>
                <div>
                    <h3>Triagem</h3>
                    <p>Acompanhe os produtos em análise.</p>
                </div>
            </div>

            <div class="info-card">
                <span class="numero">3</span>
                <div>
                    <h3>Recuperação</h3>
                    <p>Gerencie a destinação dos produtos.</p>
                </div>
            </div>

        </section>

        <div class="login-card">

            <h1>BUSCA AÍ</h1>
            <h2>Acesso interno</h2>

            <p>
                Entre para acessar o painel de gerenciamento.
            </p>

            <?php if ($mensagem_erro !== "") { echo aviso("erro", $mensagem_erro); } ?>

            <form action="login-buscaai.php" method="POST">

                <div class="campo">
                    <label for="email">E-mail</label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Digite o e-mail"
                        maxlength="255"
                        autocomplete="username"
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

        </div>

    </main>

<?php fim_documento(); ?>
