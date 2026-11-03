<?php
require_once "funcoes.php";

$empresa_id = exigir_empresa();

require_once "conexao.php";

$mensagem = "";
$tipo_mensagem = "erro";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $senha_atual = $_POST["senha_atual"] ?? "";
    $nova_senha = $_POST["nova_senha"] ?? "";
    $confirmar_senha = $_POST["confirmar_senha"] ?? "";

    if (!csrf_valido()) {
        $mensagem = "Sessão expirada. Recarregue a página e tente novamente.";

    } elseif (
        !is_string($senha_atual) ||
        !is_string($nova_senha) ||
        !is_string($confirmar_senha)
    ) {
        $mensagem = "Dados inválidos. Confira os campos e tente novamente.";

    } elseif (
        $senha_atual === "" ||
        $nova_senha === "" ||
        $confirmar_senha === ""
    ) {
        $mensagem = "Preencha todos os campos de senha.";

    } elseif ($nova_senha !== $confirmar_senha) {
        $mensagem = "As novas senhas não são iguais.";

    } elseif (strlen($nova_senha) < 8) {
        $mensagem = "A nova senha deve ter pelo menos 8 caracteres.";

    } elseif (strlen($nova_senha) > 72) {
        $mensagem = "A nova senha não pode ultrapassar 72 bytes.";

    } elseif ($nova_senha === $senha_atual) {
        $mensagem = "A nova senha deve ser diferente da senha atual.";

    } else {
        try {
            $stmt = preparar($conexao, "SELECT senha FROM empresas WHERE id = ?");
            $stmt->bind_param("i", $empresa_id);
            executar($stmt);

            $empresa = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$empresa) {
                http_response_code(404);
                exit("Conta empresarial não encontrada.");
            }

            if (!password_verify($senha_atual, $empresa["senha"])) {
                $mensagem = "A senha atual está incorreta.";

            } else {
                $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);

                if ($senha_hash === false) {
                    throw new RuntimeException("Falha ao gerar o hash da senha.");
                }

                $stmt = preparar($conexao, "UPDATE empresas SET senha = ? WHERE id = ?");
                $stmt->bind_param("si", $senha_hash, $empresa_id);
                executar($stmt);
                $stmt->close();

                // Renova o identificador da sessão após a alteração.
                session_regenerate_id(true);

                $mensagem = "Senha alterada com sucesso!";
                $tipo_mensagem = "sucesso";
            }

        } catch (Throwable $erro) {
            error_log("Erro ao alterar senha da empresa: " . $erro->getMessage());

            $mensagem = "Não foi possível alterar a senha agora. Tente novamente.";
        }
    }
}

inicio_documento("BUSCA AÍ - Alterar senha");
cabecalho_pagina(links_empresa(), "dashboard.php");
?>

<main class="alterar-senha">

    <section class="senha-topo">

        <span class="etiqueta">SEGURANÇA</span>

        <h1>Alterar senha</h1>

        <p>
            Atualize a senha de acesso da sua conta empresarial.
        </p>

    </section>

    <section class="senha-card">

        <?php if ($mensagem !== "") { echo aviso($tipo_mensagem, $mensagem); } ?>

        <form action="alterar-senha.php" method="POST">

            <?= csrf_campo() ?>

            <div class="campo">

                <label for="senha-atual">Senha atual</label>

                <div class="campo-senha">

                    <input
                    type="password"
                    id="senha-atual"
                    name="senha_atual"
                    placeholder="Digite sua senha atual"
                    autocomplete="current-password"
                    required>

                    <button type="button" class="mostrar-senha" data-mostrar-senha="senha-atual" aria-label="Mostrar senha" aria-pressed="false">Mostrar</button>

                </div>

            </div>

            <div class="campo">

                <label for="nova-senha">Nova senha</label>

                <div class="campo-senha">

                    <input
                    type="password"
                    id="nova-senha"
                    name="nova_senha"
                    placeholder="Digite a nova senha"
                    autocomplete="new-password"
                    minlength="8"
                    maxlength="72"
                    required>

                    <button type="button" class="mostrar-senha" data-mostrar-senha="nova-senha" aria-label="Mostrar senha" aria-pressed="false">Mostrar</button>

                </div>

            </div>

            <div class="campo">

                <label for="confirmar-senha">Confirmar nova senha</label>

                <div class="campo-senha">

                    <input
                    type="password"
                    id="confirmar-senha"
                    name="confirmar_senha"
                    placeholder="Digite novamente a nova senha"
                    autocomplete="new-password"
                    minlength="8"
                    maxlength="72"
                    required>

                    <button type="button" class="mostrar-senha" data-mostrar-senha="confirmar-senha" aria-label="Mostrar senha" aria-pressed="false">Mostrar</button>

                </div>

            </div>

            <button type="submit">
                Alterar senha
            </button>

        </form>

    </section>

    <a href="perfil.php" class="botao-devolucao">
        Voltar para o perfil
    </a>

</main>

<?php fim_documento(); ?>
