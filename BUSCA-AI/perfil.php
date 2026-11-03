<?php
require_once "funcoes.php";

$empresa_id = exigir_empresa();

require_once "conexao.php";

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
} catch (Throwable $erro) {
    error_log("Erro ao carregar perfil da empresa: " . $erro->getMessage());

    http_response_code(500);
    exit("Não foi possível carregar os dados da empresa.");
}

inicio_documento("BUSCA AÍ - Perfil");
cabecalho_pagina(links_empresa(), "dashboard.php");
?>

<main class="perfil">

    <section class="perfil-topo">

        <span class="etiqueta">MINHA CONTA</span>

        <h1>Perfil da empresa</h1>

        <p>
            Consulte as informações cadastradas da sua empresa.
        </p>

    </section>

    <section class="perfil-card">

        <div class="perfil-titulo">

            <div>
                <h2>Dados da empresa</h2>

                <p>
                    Informações utilizadas na plataforma BUSCA AÍ.
                </p>
            </div>

            <a href="editar-perfil.php" class="botao-perfil">
                Editar dados
            </a>

        </div>

        <div class="dados-empresa">

            <div class="campo-perfil">
                <span>Nome da empresa</span>
                <strong><?= escapar($empresa["nome_empresa"]) ?></strong>
            </div>

            <div class="campo-perfil">
                <span>CNPJ</span>
                <strong><?= escapar($empresa["cnpj"]) ?></strong>
            </div>

            <div class="campo-perfil">
                <span>E-mail corporativo</span>
                <strong><?= escapar($empresa["email"]) ?></strong>
            </div>

            <div class="campo-perfil">
                <span>Telefone corporativo</span>
                <strong><?= escapar($empresa["telefone"]) ?></strong>
            </div>

        </div>

    </section>

    <section class="perfil-card">

        <div class="perfil-titulo">

            <div>
                <h2>Segurança</h2>

                <p>
                    Gerencie as informações de acesso da sua conta.
                </p>
            </div>

            <a href="alterar-senha.php" class="botao-perfil">
                Alterar senha
            </a>

        </div>

        <div class="seguranca">

            <div>
                <span>E-mail de acesso</span>
                <strong><?= escapar($empresa["email"]) ?></strong>
            </div>

            <div>
                <span>Senha</span>
                <strong>••••••••••••</strong>
            </div>

        </div>

    </section>

    <a href="dashboard.php" class="botao-devolucao">
        Voltar para o dashboard
    </a>

</main>

<?php fim_documento(); ?>
