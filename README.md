# LogiControl — Gerenciador Logístico

Atividade Prática Orientada de Fundamentos de Programação para Internet do curso Tecnologia em Análise e Desenvolvimento de Sistemas — UNIPAR EAD.

## Descrição
Sistema local para cadastrar produtos, endereços de origem e destino e transportadores, gerar fretes com vários produtos e baixar relatório diário em CSV.

## Tecnologias
- PHP puro 7.4 ou superior; recomendado PHP 8.2 de 64 bits.
- MySQL 5.7/8.0 ou MariaDB 10.4 ou superior, tabelas InnoDB.
- PDO e extensão pdo_mysql habilitados.
- HTML5, CSS3 e JavaScript puro.
- Bootstrap 5.3.3 via CDN.
- XAMPP no Windows; Git e GitHub para versionamento.

## Funcionalidades
- Dashboard com contadores dos quatro cadastros principais.
- Cadastro, listagem, edição e exclusão de produtos, endereços e transportadores.
- Menus presentes em todas as páginas de interface.
- Origem e destino filtrados por tipo de endereço.
- Um transportador por frete e vários produtos com suas quantidades.
- Adição e remoção de linhas de produto com JavaScript.
- Gravação atômica do frete e itens com transação PDO.
- Listagem e detalhes com unidades, peso e valor das mercadorias.
- Relatório por data do frete, com consulta e download CSV para Excel.
- Campos obrigatórios validados no servidor, saída HTML escapada, consultas preparadas e proteção CSRF.

## Instalação no XAMPP
1. Instale e abra o XAMPP no Windows.
2. Copie a pasta `gerenciador_logistico` para `C:\xampp\htdocs\`.
3. No XAMPP Control Panel, inicie Apache e MySQL.
4. Abra `http://localhost/phpmyadmin/`.
5. Na aba Importar, selecione `C:\xampp\htdocs\gerenciador_logistico\database\script.sql` e execute.
6. O script cria o banco `gerenciador_logistico` e as cinco tabelas; não apaga dados existentes.
7. Confira `config/conexao.php`: host `127.0.0.1`, porta `3306`, usuário `root`, senha vazia no XAMPP padrão.
8. Acesse `http://localhost/gerenciador_logistico/`.

Se o MySQL tiver senha ou porta diferente, ajuste a configuração ou as variáveis `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` e `DB_PASS` do ambiente do Apache. Não publique senhas reais no GitHub. A configuração entregue contém apenas os padrões locais do XAMPP.

Se mudar o nome da pasta ou usar subdiretório diferente, ajuste `BASE_URL` em `includes/funcoes.php`. Todos os arquivos devem ser salvos em UTF-8 sem BOM; apenas o download CSV recebe BOM. O Bootstrap exige internet para carregar CSS e menu responsivo. O projeto não depende de Composer, npm, rotas amigáveis ou regras de reescrita.

## Primeiro uso
1. Cadastre dois produtos com códigos diferentes.
2. Cadastre um endereço de tipo origem e outro de tipo destino.
3. Cadastre um transportador.
4. Em Fretes → Gerar frete, selecione os três cadastros e preencha data, valor e status.
5. Selecione o primeiro produto, informe quantidade e use Adicionar produto para o segundo.
6. Salve e confira os detalhes.
7. Em Relatório, escolha a mesma data, consulte e baixe o CSV.

## Regras e limites
- CPF, CNPJ e CEP são armazenados como texto, sem validação de autenticidade ou chamadas a APIs.
- Descrição do produto é opcional; demais campos dos cadastros são obrigatórios.
- Valores e pesos não negativos; quantidades inteiras positivas.
- Código do produto é único. Não existe controle de estoque.
- Até 100 linhas por frete; produtos repetidos são agrupados e têm as quantidades somadas.
- Até 1.000.000 de unidades por produto no mesmo frete.
- Peso unitário: até 999.999,999 kg; valores monetários: até R$ 999.999.999,99.
- Os limites dos textos são verificados em bytes no PHP; caracteres acentuados podem ocupar mais de um byte.
- Exclusões de cadastros utilizados em fretes são bloqueadas por chaves estrangeiras.
- O tipo de endereço utilizado em um frete não pode ser alterado.
- Itens guardam nome, código, peso e valor do produto na geração, preservando seus totais após edição do cadastro.
- Endereços e transportador exibem os dados cadastrais atuais.
- O preço das mercadorias e o valor do serviço de frete aparecem separadamente.
- Status é escolhido na geração. Edição e exclusão de fretes não fazem parte do escopo solicitado.
- O relatório utiliza `data_frete`, não a data de criação, e inclui todos os status.
- CSV: separador ponto e vírgula, BOM UTF-8, decimais com vírgula, uma linha por frete e proteção contra fórmulas em nomes.
- Uma data sem fretes apresenta mensagem e não inicia download vazio.
- Não há autenticação. O sistema se destina ao uso acadêmico local.

## Estrutura
```text
gerenciador_logistico/
  config/conexao.php
  includes/
    funcoes.php
    crud.php
    header.php
    menu.php
    footer.php
  produtos/
    listar.php
    cadastrar.php
    editar.php
    salvar.php
    excluir.php
  enderecos/
    listar.php
    cadastrar.php
    editar.php
    salvar.php
    excluir.php
  transportadores/
    listar.php
    cadastrar.php
    editar.php
    salvar.php
    excluir.php
  fretes/
    listar.php
    novo.php
    salvar.php
    detalhes.php
  relatorios/frete_dia.php
  assets/css/estilo.css
  assets/js/frete.js
  database/script.sql
  docs/commits.md
  docs/roteiro_video.md
  docs/telas_figma.md
  index.php
  README.md
  .gitignore
```

## Organização do código
`config/conexao.php` cria a conexão PDO. `includes/funcoes.php` reúne consultas, validações, sessão, mensagens e esquema dos cadastros. `includes/crud.php` executa os três CRUDs com uma lista fechada de tabelas e colunas. Os arquivos de cada módulo definem o módulo e a ação e carregam essa implementação compartilhada. `fretes/salvar.php` valida referências e salva o frete e seus itens na mesma transação. `assets/js/frete.js` controla as linhas. `relatorios/frete_dia.php` consulta e exporta o relatório antes de enviar qualquer HTML.

## Verificação local de entrega
- Cadastre, edite e exclua um registro livre de cada módulo.
- Tente salvar produto com código repetido e campos obrigatórios vazios.
- Gere um frete com dois produtos e quantidades diferentes.
- Confira peso, unidades e valor das mercadorias nos detalhes.
- Repita um produto e confira o agrupamento das quantidades.
- Tente excluir um cadastro vinculado e alterar o tipo de endereço utilizado.
- Edite preço de um produto e confira que o frete antigo mantém seus valores.
- Consulte uma data com fretes e abra o CSV no Excel.
- Consulte uma data sem fretes.
- Confira menu e formulários em largura de celular.

Os arquivos foram inspecionados e o JavaScript passou pela verificação de sintaxe do Node.js. PHP e MySQL não estavam disponíveis no ambiente de geração; o teste integrado no XAMPP não foi executado.

## Versionamento e entrega
Mensagens e comandos estão em `docs/commits.md`. Publique o repositório como público e envie seu link no ambiente virtual. Não foram criados repositório remoto nem commits em seu nome.

O roteiro de vídeo de oito minutos está em `docs/roteiro_video.md`. Apareça no início, informe os dados acadêmicos, demonstre a aplicação e publique no YouTube como Não Listado. Envie o link no ambiente virtual.

As especificações de todas as telas estão em `docs/telas_figma.md`. Monte o protótipo, compare com a aplicação e exporte as telas em PDF para a entrega da etapa de análise. O PDF de telas e o vídeo são entregas a produzir a partir desses materiais.

## Autoria
[SEU NOME] – UNIPAR EAD
