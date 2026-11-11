# BUSCA AÍ

Projeto web de gerenciamento e acompanhamento de devoluções empresariais.

## Tecnologias
- PHP 8.3 com Apache
- MySQL (mysqli)
- HTML, CSS e JavaScript

## Estrutura
O aplicativo completo fica em `BUSCA-AI/`. As páginas HTML, PHP, imagens, CSS e JavaScript são servidas pelo mesmo Apache. Isso mantém login, sessões e formulários no mesmo domínio.

## Executar localmente
Coloque a pasta `BUSCA-AI` em `htdocs` do XAMPP. Configure o MySQL local em `BUSCA-AI/conexao.php` ou use as variáveis de ambiente.

## Deploy no Render
O repositório contém um `Dockerfile` na raiz. Configure o Web Service com:
- Runtime: Docker
- Dockerfile Path: `./Dockerfile`
- Docker Context: `.`
- Branch: `main`
- Porta HTTP: `10000`

O SQL é movido para `/var/lib/busca-ai/schema.sql` durante a criação da imagem e não fica acessível pelo navegador.

## Banco de dados de produção
O container executa PHP/Apache, mas **não contém um servidor MySQL**. É necessário ter um serviço MySQL externo e acessível pelo Render. Configure no serviço Render, em Environment:

- `DB_HOST`: host do MySQL
- `DB_PORT`: porta do MySQL (geralmente 3306)
- `DB_NAME`: banco de dados usado pela aplicação
- `DB_USER`: usuário
- `DB_PASSWORD`: senha
- `DB_SSL_CA`: caminho do certificado CA, somente se o provedor exigir TLS com CA específica

A conexão local continua usando XAMPP quando essas variáveis não estão definidas. Em produção, o código não usa `localhost` nem o usuário root do XAMPP.

### Criar as tabelas
O arquivo `BUSCA-AI/busca_ai_novo.sql` contém o esquema das tabelas. Ele **não contém registros de empresas, usuários, devoluções ou mensagens**. O script cria o banco `busca_ai_novo` e as tabelas. Execute-o uma única vez no servidor MySQL de produção, usando uma conta autorizada, e defina `DB_NAME=busca_ai_novo` no Render.

Não importe esse esquema sobre um banco com dados que você precisa preservar sem antes fazer backup e conferir o destino. O projeto não cria automaticamente o usuário interno do painel.

## Limitação
Sem um MySQL remoto configurado e com o esquema importado, as páginas públicas podem abrir, mas cadastro, login, painel e devoluções não funcionarão.
