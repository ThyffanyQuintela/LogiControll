# Especificação das telas — LogiControl

## Padrão visual
- Frame desktop: 1440 × 900 px; altura livre para páginas longas.
- Frame celular: 390 × 844 px; rolagem vertical.
- Fundo: `#F3F6FB`; azul principal: `#175CBD`; texto: `#24344D`.
- Fonte: Arial ou fonte sem serifa; texto 16 px, títulos 28–30 px.
- Barra superior: x=0, y=0, largura total, altura aproximada 64 px.
- Marca LogiControl à esquerda; menu à direita: Início, Produtos, Endereços, Transportadores, Fretes, Relatório.
- Conteúdo desktop: x=150, y=96, largura aproximada 1140 px.
- Cartões brancos com borda suave, raio 14 px e espaçamento interno de 24 px.
- Títulos à esquerda; ação principal azul à direita; botões secundários contornados.
- Mensagens de sucesso verdes e erros vermelhos acima do título; avisos em amarelo.
- Rodapé ao final da página: LogiControl • Gerenciador Logístico • UNIPAR EAD.
- Mobile: margens 16 px, menu recolhível, campos e cartões empilhados, tabelas com rolagem horizontal.

## 1. Início — index.php
- Topo: barra padrão.
- x=150, y=96: bloco azul-claro com título “Gestão logística em um só lugar” e descrição curta.
- y≈250: quatro cartões em uma linha, cada um com título, contador grande azul e link: Produtos, Endereços, Transportadores e Fretes.
- y≈450: cartão “Próximas ações”, texto e botões Gerar frete e Relatório por dia.
- Mobile: cartões em uma coluna; botões com quebra de linha.

## 2. Produtos — produtos/listar.php
- Cabeçalho do conteúdo: Produtos à esquerda; Cadastrar produto à direita.
- Abaixo: cartão com tabela ID | Código | Nome | Descrição | Peso unitário (kg) | Valor unitário (R$) | Ações.
- Cada linha: botões Editar e Excluir; exclusão abre confirmação nativa do navegador.
- Sem dados: linha central “Nenhum registro cadastrado”.

## 3. Cadastrar produto — produtos/cadastrar.php
- Cabeçalho: Cadastrar Produto à esquerda e Voltar à direita.
- Cartão abaixo com indicação de campos obrigatórios.
- Linha 1: Código* à esquerda e Nome* à direita.
- Linha 2: Descrição opcional ocupando a largura inteira, com área de texto.
- Linha 3: Peso unitário (kg)* à esquerda e Valor unitário (R$)* à direita.
- Rodapé do formulário: Salvar e Cancelar alinhados à esquerda.

## 4. Editar produto — produtos/editar.php?id=ID
- Mesma composição do cadastro, título Editar Produto.
- Campos preenchidos com os valores do registro; ID mantido como campo oculto.
- Botões Salvar, Cancelar e Voltar.

## 5. Endereços — enderecos/listar.php
- Cabeçalho: Endereços e Cadastrar endereço.
- Cartão com tabela ID | Tipo | Identificação | Rua | Número | Bairro | Cidade | Estado | CEP | Ações.
- Editar e Excluir em cada linha; estado vazio e mensagens acima do conteúdo.

## 6. Cadastrar endereço — enderecos/cadastrar.php
- Cabeçalho: Cadastrar Endereço e Voltar.
- Formulário em duas colunas.
- Linha 1: Tipo* (select Origem/Destino) | Identificação*.
- Linha 2: Rua* | Número*; aceitar número textual como S/N.
- Linha 3: Bairro* | Cidade*.
- Linha 4: Estado* (select de UFs) | CEP*.
- Parte inferior: Salvar e Cancelar.

## 7. Editar endereço — enderecos/editar.php?id=ID
- Mesma composição do cadastro com os valores preenchidos.
- Título Editar Endereço e ID oculto.
- Caso altere o tipo de endereço já utilizado, mensagem de erro acima do formulário.

## 8. Transportadores — transportadores/listar.php
- Cabeçalho: Transportadores e Cadastrar transportador.
- Tabela ID | Nome | CPF ou CNPJ | Telefone | Veículo | Placa | Ações.
- Editar e Excluir por linha; mensagem para exclusão bloqueada por vínculo.

## 9. Cadastrar transportador — transportadores/cadastrar.php
- Cabeçalho: Cadastrar Transportador e Voltar.
- Linha 1: Nome* | CPF ou CNPJ*.
- Linha 2: Telefone* | Veículo*.
- Linha 3: Placa* na primeira coluna.
- Parte inferior: Salvar e Cancelar.

## 10. Editar transportador — transportadores/editar.php?id=ID
- Mesma composição do cadastro, com título Editar Transportador.
- Campos preenchidos; ID oculto; botões Salvar e Cancelar.

## 11. Fretes — fretes/listar.php
- Cabeçalho: Fretes à esquerda e Gerar frete à direita.
- Tabela ID | Data | Origem | Destino | Transportador | Valor do frete | Status | Detalhes.
- Status com selo claro. Detalhes como botão contornado.
- Estado vazio: “Nenhum frete gerado”.

## 12. Gerar frete — fretes/novo.php
- Cabeçalho: Gerar frete e Voltar.
- Cartão com seção Dados do frete.
- Linha 1 em três colunas: Origem* | Destino* | Transportador*.
- Linha 2 em três colunas: Data do frete* | Valor do frete (R$)* | Status*.
- Abaixo: título Produtos transportados e instrução sobre agrupamento e valor do frete separado.
- Linha dinâmica dentro de bloco cinza-claro: Produto* com cerca de 58% da largura | Quantidade* com 25% | Remover com 17%.
- Abaixo das linhas: Adicionar produto à esquerda e contador de linhas ao lado.
- Rodapé do cartão: Salvar frete.
- Variante com cadastros ausentes: aviso amarelo antes do formulário; campos desabilitados.
- Mobile: campos, seletor, quantidade e Remover empilhados.

## 13. Detalhes do frete — fretes/detalhes.php?id=ID
- Cabeçalho: Frete #ID e Voltar.
- Primeiro cartão, três colunas: Data | Status | Valor do frete.
- Segunda faixa: cartão Origem à esquerda e Destino à direita; identificação e endereço completo.
- Terceira faixa: cartão Transportador, largura total, com documento, telefone, veículo e placa.
- Quarta faixa: cartão Produtos com tabela Código | Produto | Qtd. | Peso unitário (kg) | Valor unitário | Subtotal.
- Abaixo da tabela: Total de unidades | Peso total | Valor das mercadorias.
- Nota discreta sobre valores dos produtos na geração e dados cadastrais atuais.

## 14. Relatório — relatorios/frete_dia.php
- Título: Relatório de fretes por dia.
- Cartão com descrição e linha de formulário: Data do frete (largura aproximada 40%) | Consultar | Baixar CSV.
- Após consulta: cartão com total de fretes e data; tabela Frete | Transportador | Status | Valor do frete.
- ID de cada frete com link para detalhes.
- Variante sem registros: “Nenhum frete encontrado nesta data”.
- Variante de download sem registros: aviso amarelo, sem baixar arquivo vazio.

## Estados adicionais
- Salvar com sucesso: alerta verde e retorno à listagem ou aos detalhes do frete.
- Erro de validação: alerta vermelho; formulário preserva os dados digitados.
- Excluir: caixa de confirmação do navegador, com Cancelar e OK.
- Cadastro vinculado: alerta vermelho ao tentar excluir, preservando o registro.
- ID inexistente: retorno à listagem com mensagem.

## Frames a exportar
Crie os 14 frames principais acima e, se desejar, variantes mobile e de erro. Nomeie os frames com número e título. Exporte as telas selecionadas em PDF para a etapa de análise. Esta especificação descreve o projeto entregue e não representa uma inspeção do protótipo Figma anterior.
