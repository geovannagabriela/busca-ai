<?php

require_once __DIR__ . "/funcoes.php";
require_once __DIR__ . "/conexao.php";

$empresa_id = empresa_logada();

$mensagem_sucesso = "";
$mensagem_erro = "";

$nome = "";
$email = "";
$assunto = "";
$mensagem = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nome = $_POST["nome"] ?? "";
    $email = $_POST["email"] ?? "";
    $assunto = $_POST["assunto"] ?? "";
    $mensagem = $_POST["mensagem"] ?? "";

    if (!csrf_valido()) {

        $mensagem_erro =
            "Sessão expirada. Recarregue a página e tente novamente.";

    } elseif (
        !is_string($nome) ||
        !is_string($email) ||
        !is_string($assunto) ||
        !is_string($mensagem)
    ) {

        $mensagem_erro = "Os dados enviados são inválidos.";

    } else {

        $nome = trim($nome);
        $email = trim($email);
        $assunto = trim($assunto);
        $mensagem = trim($mensagem);

        if (
            $nome === "" ||
            $email === "" ||
            $assunto === "" ||
            $mensagem === ""
        ) {

            $mensagem_erro =
                "Preencha todos os campos antes de enviar.";

        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $mensagem_erro =
                "Informe um endereço de e-mail válido.";

        } elseif (
            strlen($nome) > 255 ||
            strlen($email) > 255 ||
            strlen($assunto) > 255 ||
            strlen($mensagem) > 10000
        ) {

            $mensagem_erro =
                "Um ou mais campos ultrapassam o tamanho permitido.";

        } else {

            $stmt = null;

            try {

                $sql = "
                    INSERT INTO mensagens_contato
                        (empresa_id, nome, email, assunto, mensagem)
                    VALUES (?, ?, ?, ?, ?)
                ";

                $stmt = preparar($conexao, $sql);

                $stmt->bind_param(
                    "issss",
                    $empresa_id,
                    $nome,
                    $email,
                    $assunto,
                    $mensagem
                );

                executar($stmt);

                $mensagem_sucesso =
                    "Mensagem enviada com sucesso!";

                $nome = "";
                $email = "";
                $assunto = "";
                $mensagem = "";

            } catch (Throwable $erro) {

                error_log(
                    "Erro ao salvar mensagem de contato: " .
                    $erro->getMessage()
                );

                $mensagem_erro =
                    "Não foi possível enviar sua mensagem agora. Tente novamente.";

            } finally {

                if ($stmt instanceof mysqli_stmt) {
                    $stmt->close();
                }
            }
        }
    }
}

inicio_documento("BUSCA AÍ - Contato");

if ($empresa_id !== null) {
    cabecalho_pagina(links_empresa(), "dashboard.php");
} else {
    cabecalho_pagina(links_publicos());
}

?>

<main class="contato">

    <section class="contato-topo">

        <span class="etiqueta">CONTATO</span>

        <h1>Fale com a BUSCA AÍ.</h1>

        <p>
            Tem alguma dúvida ou precisa de ajuda?
            Envie uma mensagem para nossa equipe.
        </p>

    </section>

    <section class="contato-conteudo">

        <div class="contato-info">

            <h2>Entre em contato</h2>

            <p>
                Estamos aqui para ajudar. Escolha uma das opções
                abaixo ou envie sua mensagem pelo formulário.
            </p>

            <div class="contato-card">
                <span>01</span>

                <div>
                    <h3>Atendimento</h3>
                    <p>Fale com nossa equipe para tirar suas dúvidas.</p>
                </div>
            </div>

            <br>

            <div class="contato-card">
                <span>02</span>

                <div>
                    <h3>E-mail</h3>
                    <p>contato@buscaai.com.br</p>
                </div>
            </div>

            <br>

            <div class="contato-card">
                <span>03</span>

                <div>
                    <h3>WhatsApp</h3>
                    <p>Entre em contato pelo nosso atendimento.</p>
                </div>
            </div>

        </div>

        <div class="contato-formulario">

            <?php
            if ($mensagem_sucesso !== "") {
                echo aviso("sucesso", $mensagem_sucesso);
            }

            if ($mensagem_erro !== "") {
                echo aviso("erro", $mensagem_erro);
            }
            ?>

            <form action="contato.php" method="POST">

                <?= csrf_campo() ?>

                <div class="campo">

                    <label for="nome">Nome completo</label>

                    <input
                        type="text"
                        id="nome"
                        name="nome"
                        placeholder="Digite seu nome"
                        maxlength="255"
                        value="<?= escapar($nome) ?>"
                        required
                    >

                </div>

                <div class="campo">

                    <label for="email">E-mail</label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Digite seu e-mail"
                        maxlength="255"
                        value="<?= escapar($email) ?>"
                        required
                    >

                </div>

                <div class="campo">

                    <label for="assunto">Assunto</label>

                    <input
                        type="text"
                        id="assunto"
                        name="assunto"
                        placeholder="Digite o assunto"
                        maxlength="255"
                        value="<?= escapar($assunto) ?>"
                        required
                    >

                </div>

                <div class="campo">

                    <label for="mensagem">Mensagem</label>

                    <textarea
                        id="mensagem"
                        name="mensagem"
                        placeholder="Digite sua mensagem"
                        maxlength="10000"
                        required
                    ><?= escapar($mensagem) ?></textarea>

                </div>

                <button type="submit">Enviar mensagem</button>

            </form>

        </div>

    </section>

</main>

<?php fim_documento(); ?>