<?php
require_once "funcoes.php";

$empresa_id = exigir_empresa();

require_once "conexao.php";

$mensagem = "";
$empresa = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nome_empresa = trim((string) ($_POST["empresa"] ?? ""));
    $email = trim((string) ($_POST["email"] ?? ""));
    $telefone = trim((string) ($_POST["telefone"] ?? ""));

    if (!csrf_valido()) {
        $mensagem = "Sessão expirada. Recarregue a página e tente novamente.";

    } elseif ($nome_empresa === "" || $email === "" || $telefone === "") {
        $mensagem = "Preencha todos os campos obrigatórios.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagem = "Informe um endereço de e-mail válido.";

    } elseif (
        strlen($nome_empresa) > 255 ||
        strlen($email) > 255 ||
        strlen($telefone) > 30
    ) {
        $mensagem = "Um ou mais campos ultrapassam o tamanho permitido.";

    } else {
        try {
            $stmt = preparar(
                $conexao,
                "UPDATE empresas
                 SET nome_empresa = ?, email = ?, telefone = ?
                 WHERE id = ?"
            );

            $stmt->bind_param("sssi", $nome_empresa, $email, $telefone, $empresa_id);

            if (!$stmt->execute()) {
                $codigo = $stmt->errno;
                $stmt->close();

                if ($codigo === 1062) {
                    $mensagem = "Não foi possível atualizar os dados. Verifique se o e-mail já está sendo utilizado e tente novamente.";
                } else {
                    throw new RuntimeException("Falha ao atualizar perfil (" . $codigo . ").");
                }
            } else {
                $stmt->close();

                // Mantém o nome exibido no dashboard coerente com o cadastro.
                $_SESSION["empresa_nome"] = $nome_empresa;

                header("Location: perfil.php");
                exit;
            }
        } catch (Throwable $erro) {
            error_log("Erro ao atualizar perfil da empresa: " . $erro->getMessage());

            $mensagem = "Não foi possível atualizar os dados. Verifique se o e-mail já está sendo utilizado e tente novamente.";
        }
    }
}

try {
    $stmt = preparar(
        $conexao,
        "SELECT nome_empresa, cnpj, email, telefone
         FROM empresas
         WHERE id = ?"
    );
    $stmt->bind_param("i", $empresa_id);
    executar($stmt);

    $empresa = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$empresa) {
        http_response_code(404);
        exit("Empresa não encontrada.");
    }

    // Mantém os valores enviados no formulário caso a validação falhe.
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $empresa["nome_empresa"] = $_POST["empresa"] ?? $empresa["nome_empresa"];
        $empresa["email"] = $_POST["email"] ?? $empresa["email"];
        $empresa["telefone"] = $_POST["telefone"] ?? $empresa["telefone"];
    }
} catch (Throwable $erro) {
    error_log("Erro ao carregar dados para edição do perfil: " . $erro->getMessage());

    http_response_code(500);
    exit("Não foi possível carregar os dados da empresa.");
}

inicio_documento("BUSCA AÍ - Editar perfil");
cabecalho_pagina(links_empresa(), "dashboard.php");
?>

<main class="editar-perfil">

    <section class="editar-topo">

        <span class="etiqueta">MINHA CONTA</span>

        <h1>Editar perfil</h1>

        <p>
            Altere os dados cadastrados da sua empresa.
        </p>

    </section>

    <section class="editar-card">

        <?php if ($mensagem !== "") { echo aviso("erro", $mensagem); } ?>

        <form action="editar-perfil.php" method="POST">

            <?= csrf_campo() ?>

            <div class="campo">

                <label for="empresa">Nome da empresa</label>

                <input
                    type="text"
                    id="empresa"
                    name="empresa"
                    value="<?= escapar($empresa["nome_empresa"]) ?>"
                    maxlength="255"
                    required>

            </div>

            <div class="campo">

                <label for="cnpj">CNPJ</label>

                <input
                    type="text"
                    id="cnpj"
                    value="<?= escapar($empresa["cnpj"]) ?>"
                    readonly>

            </div>

            <div class="campo">

                <label for="email">E-mail corporativo</label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= escapar($empresa["email"]) ?>"
                    maxlength="255"
                    required>

            </div>

            <div class="campo">

                <label for="telefone">Telefone corporativo</label>

                <input
                    type="tel"
                    id="telefone"
                    name="telefone"
                    value="<?= escapar($empresa["telefone"]) ?>"
                    maxlength="30"
                    required>

            </div>

            <button type="submit">
                Salvar alterações
            </button>

        </form>

    </section>

    <a href="perfil.php" class="botao-devolucao">
        Voltar para o perfil
    </a>

</main>

<?php fim_documento(); ?>
