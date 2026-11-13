'use strict';
document.addEventListener('DOMContentLoaded', () => {
    const lista = document.getElementById('itens');
    const modelo = document.getElementById('modelo-item');
    const adicionar = document.getElementById('adicionar-produto');
    if (!lista || !modelo || !adicionar) return;
    const atualizar = () => {
        const linhas = lista.querySelectorAll('.item-produto');
        linhas.forEach((linha, i) => {
            const select = linha.querySelector('select');
            const quantidade = linha.querySelector('input');
            select.id = `produto-${i}`;
            quantidade.id = `quantidade-${i}`;
            linha.querySelector('[data-label-produto]').htmlFor = select.id;
            linha.querySelector('[data-label-quantidade]').htmlFor = quantidade.id;
            linha.querySelector('[data-remover]').disabled = linhas.length === 1;
        });
        adicionar.disabled = linhas.length >= 100;
        document.getElementById('contador-itens').textContent = `${linhas.length} linha(s) de produto`;
    };
    adicionar.addEventListener('click', () => {
        if (lista.children.length >= 100) return;
        lista.appendChild(modelo.content.cloneNode(true));
        atualizar();
        lista.lastElementChild.querySelector('select').focus();
    });
    lista.addEventListener('click', event => {
        const botao = event.target.closest('[data-remover]');
        if (botao && lista.children.length > 1) { botao.closest('.item-produto').remove(); atualizar(); }
    });
    atualizar();
});
