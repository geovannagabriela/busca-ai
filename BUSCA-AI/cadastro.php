<?php
require_once "funcoes.php";

$mensagem_erro = "";

$nome_empresa = "";
$email = "";
$cnpj = "";
$telefone = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    require_once "conexao.php";

    $nome_empresa = trim($_POST["nome"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $cnpj = preg_replace('/\D/', '', $_POST["cnpj"] ?? "");
    $telefone = trim($_POST["telefone"] ?? "");
    $senha = $_POST["senha"] ?? "";
    $confirmar_senha = $_POST["confirmar-senha"] ?? "";

    if (
        $nome_empresa === "" ||
        $email === "" ||
        $cnpj === "" ||
        $telefone === "" ||
        $senha === "" ||
        $confirmar_senha === ""
    ) {
        $mensagem_erro = "Preencha todos os campos.";
    } elseif (
        strlen($nome_empresa) > 255 ||
        strlen($email) > 255 ||
        strlen($telefone) > 30
    ) {
        $mensagem_erro = "Um ou mais campos excedem o tamanho permitido.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagem_erro = "Digite um e-mail válido.";
    } elseif (strlen($cnpj) !== 14) {
        $mensagem_erro = "Digite um CNPJ com 14 números.";
    } elseif (strlen($senha) < 8) {
        $mensagem_erro = "A senha deve ter pelo menos 8 caracteres.";
    } elseif (strlen($senha) > 72) {
        $mensagem_erro = "A senha deve ter no máximo 72 bytes.";
    } elseif ($senha !== $confirmar_senha) {
        $mensagem_erro = "As senhas não são iguais.";
    } else {
        try {
            // Verifica se o e-mail ou CNPJ já está cadastrado.
            $sql = "SELECT id FROM empresas WHERE email = ? OR cnpj = ? LIMIT 1";
            $stmt = preparar($conexao, $sql);
            $stmt->bind_param("ss", $email, $cnpj);
            executar($stmt);

            $resultado = $stmt->get_result();
            $duplicado = $resultado->num_rows > 0;
            $stmt->close();

            if ($duplicado) {
                $mensagem_erro = "Já existe uma empresa cadastrada com esse e-mail ou CNPJ.";
            } else {
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

                if ($senha_hash === false) {
                    throw new RuntimeException("Falha ao proteger senha.");
                }

                $sql = "INSERT INTO empresas
                        (nome_empresa, email, cnpj, telefone, senha)
                        VALUES (?, ?, ?, ?, ?)";

                $stmt = preparar($conexao, $sql);

                $stmt->bind_param(
                    "sssss",
                    $nome_empresa,
                    $email,
                    $cnpj,
                    $telefone,
                    $senha_hash
                );

                if ($stmt->execute()) {
                    $stmt->close();
                    header("Location: login.php");
                    exit;
                }

                // Trata duplicidade mesmo se dois cadastros ocorrerem juntos.
                if ($stmt->errno === 1062) {
                    $mensagem_erro = "Já existe uma empresa cadastrada com esse e-mail ou CNPJ.";
                } else {
                    throw new RuntimeException("Falha ao cadastrar empresa.");
                }

                $stmt->close();
            }
        } catch (Throwable $erro) {
            error_log("Erro no cadastro de empresa: " . $erro->getMessage());

            if ($mensagem_erro === "") {
                $mensagem_erro = "Não foi possível realizar o cadastro. Tente novamente.";
            }
        }
    }
}

inicio_documento("BUSCA AÍ - Cadastro");
cabecalho_pagina([
    ["href" => "login.php", "texto" => "Já tenho uma conta", "classe" => "pagina-btn"]
]);
?>

    <main class="login-container">

        <section class="login-info">
            <span class="etiqueta">
                CRIE UMA CONTA
            </span>

            <h1>Crie uma conta para sua empresa</h1>

            <p>Cadastre-se na BUSCA AÍ para acessar nossas funcionalidades e acompanhar suas devoluções de sua empresa.</p>

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
            <h2>Crie a conta de sua empresa:</h2>

            <p>Preencha seus dados para começar!</p>

            <?php if ($mensagem_erro !== "") { echo aviso("erro", $mensagem_erro); } ?>

            <form action="cadastro.php" method="POST">

                <div class="campo">
                    <label for="nome">Nome da empresa</label>
                    <input
                        type="text"
                        id="nome"
                        name="nome"
                        placeholder="Digite o nome"
                        maxlength="255"
                        value="<?= escapar($nome_empresa) ?>"
                        autocomplete="organization"
                        required
                    >
                </div>

                <div class="campo">
                    <label for="email">E-mail corporativo</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Digite o email"
                        maxlength="255"
                        value="<?= escapar($email) ?>"
                        autocomplete="email"
                        required
                    >
                </div>

                <div class="campo">
                    <label for="cnpj">CNPJ da empresa</label>
                    <input
                        type="text"
                        id="cnpj"
                        name="cnpj"
                        placeholder="Digite o CNPJ da empresa"
                        maxlength="18"
                        value="<?= escapar($cnpj) ?>"
                        inputmode="numeric"
                        required
                    >
                </div>

                <div class="campo">
                    <label for="telefone">Telefone corporativo</label>
                    <input
                        type="tel"
                        id="telefone"
                        name="telefone"
                        placeholder="(34) 99999-9999"
                        maxlength="30"
                        value="<?= escapar($telefone) ?>"
                        autocomplete="tel"
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
                        minlength="8"
                        maxlength="72"
                        autocomplete="new-password"
                        required
                    >
                        <button type="button" class="mostrar-senha" data-mostrar-senha="senha" aria-label="Mostrar senha" aria-pressed="false">Mostrar</button>
                    </div>
                </div>

                <div class="campo">
                    <label for="confirmar-senha">Confirmar Senha</label>
                    <div class="campo-senha">
                        <input
                        type="password"
                        id="confirmar-senha"
                        name="confirmar-senha"
                        placeholder="Digite sua senha novamente"
                        minlength="8"
                        maxlength="72"
                        autocomplete="new-password"
                        required
                    >
                        <button type="button" class="mostrar-senha" data-mostrar-senha="confirmar-senha" aria-label="Mostrar senha" aria-pressed="false">Mostrar</button>
                    </div>
                </div>

                <button type="submit">
                    Criar conta
                </button>

            </form>

            <p class="cadastro">
                Já possui uma conta?
                <a href="login.php">Entrar</a>
            </p>

        </div>

    </main>

<?php fim_documento("@"); ?>
