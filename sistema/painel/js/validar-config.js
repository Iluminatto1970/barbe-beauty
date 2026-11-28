/* ============================================================
   CAMPOS OBRIGATORIOS NA CONFIGURACAO
   ------------------------------------------------------------
   O formulario usava o `required` do navegador: quando um campo
   estava vazio o envio simplesmente nao acontecia e o balao de
   aviso aparecia fora da area visivel do modal - para o cliente
   o botao "Salvar dados" ficava sem resposta.

   Aqui a checagem aparece no proprio formulario, dizendo quais
   campos faltam, e destaca cada um. Os mesmos campos sao
   revalidados no servidor (editar-config.php).
   ============================================================ */
(function () {
    'use strict';

    var SEMPRE = {
        nome_sistema: 'Nome do Estabelecimento',
        email_sistema: 'E-mail do Estabelecimento',
        whatsapp_sistema: 'WhatsApp do Estabelecimento'
    };

    var SALAO = {
        nome_salao: 'Nome do Salão',
        whatsapp_salao: 'WhatsApp do Salão',
        endereco_salao: 'Endereço do Salão'
    };

    function obrigatorios(form) {
        var lista = {};
        var nome;
        for (nome in SEMPRE) {
            if (Object.prototype.hasOwnProperty.call(SEMPRE, nome)) {
                lista[nome] = SEMPRE[nome];
            }
        }
        var segmento = form.querySelector('[name="segmento"]');
        if (segmento && segmento.value === 'salao') {
            for (nome in SALAO) {
                if (Object.prototype.hasOwnProperty.call(SALAO, nome)) {
                    lista[nome] = SALAO[nome];
                }
            }
        }
        return lista;
    }

    function vazio(valor) {
        return String(valor === null || valor === undefined ? '' : valor).replace(/\s+/g, '') === '';
    }

    function marcar(campo, invalido) {
        if (!campo) {
            return;
        }
        if (campo.classList) {
            campo.classList.toggle('is-invalid', invalido);
        }
        campo.style.borderColor = invalido ? '#dc3545' : '';
    }

    function avisar(texto) {
        var alvo = document.getElementById('mensagem-config');
        if (!alvo) {
            return;
        }
        alvo.textContent = texto;
        alvo.className = 'text-danger';
    }

    document.addEventListener('submit', function (ev) {
        var form = ev.target;
        if (!form || form.id !== 'form-config') {
            return;
        }

        var lista = obrigatorios(form);
        var faltando = [];
        var primeiro = null;
        var nome;

        for (nome in lista) {
            if (!Object.prototype.hasOwnProperty.call(lista, nome)) {
                continue;
            }
            var campo = form.querySelector('[name="' + nome + '"]');
            if (!campo) {
                continue;
            }
            var falta = vazio(campo.value);
            marcar(campo, falta);
            if (falta) {
                faltando.push(lista[nome]);
                if (!primeiro) {
                    primeiro = campo;
                }
            }
        }

        if (faltando.length === 0) {
            return;
        }

        ev.preventDefault();
        ev.stopImmediatePropagation();
        avisar('Preencha os campos obrigatórios antes de salvar: ' + faltando.join(', ') + '.');
        if (primeiro) {
            if (primeiro.scrollIntoView) {
                primeiro.scrollIntoView({ block: 'center' });
            }
            primeiro.focus();
        }
    }, true);

    // Ao digitar, o destaque vermelho sai.
    document.addEventListener('input', function (ev) {
        var campo = ev.target;
        if (campo && campo.name && !vazio(campo.value)) {
            marcar(campo, false);
        }
    }, true);
})();
