# LogiControl — Gerenciador Logístico

Projeto desenvolvido para a Atividade Prática Orientada (APO) da disciplina **Fundamentos de Programação para Internet**, do curso de **Tecnologia em Análise e Desenvolvimento de Sistemas — UNIPAR EAD**.

## Sobre o projeto

O LogiControl é um sistema web de gerenciamento logístico executado localmente, desenvolvido para organizar cadastros de produtos, endereços e transportadores, gerar fretes e consultar relatórios por data.

## Tecnologias utilizadas

- PHP e PDO para processamento e acesso ao banco de dados
- MySQL/MariaDB para armazenamento dos dados
- HTML, CSS e JavaScript para a interface
- Bootstrap 5 para componentes visuais e layout responsivo
- XAMPP para execução local
- Git e GitHub para controle de versões

## Funcionalidades

- Cadastro, listagem, edição e exclusão de produtos
- Cadastro, listagem, edição e exclusão de endereços de origem e destino
- Cadastro, listagem, edição e exclusão de transportadores
- Geração de fretes com origem, destino, transportador e um ou mais produtos
- Inclusão de vários produtos e quantidades em um frete
- Consulta de fretes e visualização dos detalhes
- Exibição de quantidades, peso total e valores das mercadorias
- Consulta de fretes por data e exportação de relatório em CSV

## Como executar no XAMPP

1. Instale o XAMPP e inicie os serviços **Apache** e **MySQL** no painel de controle.
2. Coloque os arquivos do projeto na pasta `C:\xampp\htdocs\gerenciador_logistico`.
3. Abra `http://localhost/phpmyadmin/`.
4. Importe o arquivo `database/script.sql` para criar o banco e suas tabelas.
5. Confira as configurações locais do banco em `config/conexao.php`.
6. Acesse o sistema pelo navegador em `http://localhost/gerenciador_logistico/`.

O sistema utiliza o banco `gerenciador_logistico`. A configuração padrão de desenvolvimento utiliza o MySQL local; se necessário, os parâmetros de conexão podem ser ajustados por meio das variáveis de ambiente `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` e `DB_PASS`.

> **Observação:** este projeto foi preparado para execução acadêmica local e não inclui autenticação de usuários.

## Como utilizar

1. Cadastre um ou mais produtos na seção **Produtos**.
2. Cadastre pelo menos um endereço de **origem** e outro de **destino**.
3. Cadastre um transportador.
4. Acesse **Fretes** e escolha **Gerar frete**.
5. Selecione origem, destino, transportador, data e os produtos transportados, informando suas quantidades.
6. Salve o frete e consulte seus detalhes na listagem.
7. Em **Relatório**, selecione uma data para consultar os fretes e baixar o arquivo CSV.

## Organização dos arquivos

```text
gerenciador_logistico/
├── assets/
│   ├── css/
│   └── js/
├── config/
│   └── conexao.php
├── database/
│   └── script.sql
├── docs/
├── enderecos/
├── fretes/
├── includes/
├── produtos/
├── relatorios/
├── transportadores/
├── index.php
└── README.md
```

Os módulos de cadastros estão separados por área. A pasta `includes` reúne funções e componentes compartilhados; `config` contém a conexão com o banco; `assets` reúne CSS e JavaScript; e `database` contém o script SQL para preparação do ambiente.

## Observações

- CPF/CNPJ e CEP não precisam de validação de autenticidade para os objetivos desta atividade.
- O valor do frete é apresentado separadamente do valor das mercadorias.
- A exportação de relatórios é realizada em formato CSV, compatível com programas de planilha.
- A interface utiliza Bootstrap por CDN, podendo precisar de conexão com a internet para carregar os recursos externos.
