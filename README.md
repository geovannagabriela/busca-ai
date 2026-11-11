# BUSCA AÍ

Projeto web de gerenciamento e acompanhamento de devoluções empresariais.

## Tecnologias
- PHP 8.3 com Apache
- PostgreSQL no Supabase (PDO_PGSQL)
- HTML, CSS e JavaScript

## Estrutura
O aplicativo completo fica em `BUSCA-AI/`. As páginas HTML, PHP, imagens, CSS e JavaScript são servidas pelo Apache no Render. O front-end público publicado na Vercel continua separado e aponta os acessos PHP para o serviço Render.

## Deploy já existente
O serviço Render `busca-ai-php` usa o Dockerfile da raiz, contexto `.`, porta HTTP `10000`. O projeto Vercel `buscaai` continua servindo as páginas públicas. Esta migração altera somente a conexão do backend PHP e o driver de banco.

## Supabase
O banco PostgreSQL do Supabase é independente do MySQL do InfinityFree. A estrutura contém as tabelas:
- `empresas`
- `usuarios_buscaai`
- `devolucoes`
- `historico_devolucoes`
- `mensagens_contato`

O esquema inicial não importa registros de empresas, usuários, devoluções ou mensagens. O banco novo começa vazio; os registros do InfinityFree não são apagados nem modificados.

## Variáveis no Render
No serviço Render `busca-ai-php`, configure as variáveis abaixo usando a connection string **Session pooler** exibida em Supabase → Connect. O Render precisa de endpoint IPv4; por isso, use o pooler de sessão (porta 5432), não o endpoint direto IPv6.

- `DB_HOST`: host do Session pooler
- `DB_PORT`: `5432`
- `DB_NAME`: `postgres`
- `DB_USER`: usuário exibido na connection string do Session pooler (formato `postgres.<project-ref>`)
- `DB_PASSWORD`: senha do banco PostgreSQL definida no painel Supabase
- `DB_SSLMODE`: `require`

Nunca coloque a senha no GitHub, em arquivos públicos ou no chat. Salve-a somente nos Secrets/Environment do Render.

## Segurança e validação
As cinco tabelas estão com Row Level Security habilitado. O backend PHP conecta ao PostgreSQL pelo servidor Render; não exponha a chave de banco nem credenciais no navegador. Antes de considerar a migração concluída, valide cadastro, login, dashboard, solicitação e acompanhamento de devoluções, atualização de status, histórico, perfil, senha e contato.

## Ambiente local
O ambiente XAMPP antigo usa MySQL e não é compatível com esta conexão PostgreSQL sem configurar as mesmas variáveis e instalar `pdo_pgsql`. O banco MySQL do InfinityFree permanece como cópia independente e não é alterado por este deploy.
