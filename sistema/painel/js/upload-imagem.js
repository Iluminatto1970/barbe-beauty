/* ============================================================
   UPLOAD DE IMAGEM PELO NAVEGADOR
   ------------------------------------------------------------
   Fotos de celular chegam com 10-20 MB. Subir isso pelo 4G levava
   minutos, segurava um worker do PHP-FPM (o pool e pequeno) e, ao
   passar do max_input_time, o POST chegava vazio: o formulario nao
   salvava e o usuario era mandado para o login. O log do Caddy
   mostrou POSTs de 14,8 MB em editar-config.php com 502.

   Aqui a imagem e reduzida no proprio navegador antes do envio
   (maior lado 1920 px, alvo ~1,2 MB). O servidor continua
   validando e comprimindo igual; isto so evita o upload gigante.
   ============================================================ */
(function () {
    'use strict';

    var LIMITE_BYTES = 1200 * 1024;
    var MAX_LADO = 1920;
    var reduzindo = false;

    function reduzivel(arquivo) {
        return !!arquivo && arquivo.size > LIMITE_BYTES
            && /^image\/(jpeg|jpg|png|webp)$/i.test(arquivo.type);
    }

    function reduzir(arquivo) {
        return new Promise(function (resolve) {
            var url = URL.createObjectURL(arquivo);
            var img = new Image();
            img.onload = function () {
                var escala = Math.min(1, MAX_LADO / Math.max(img.width, img.height));
                var canvas = document.createElement('canvas');
                canvas.width = Math.max(1, Math.round(img.width * escala));
                canvas.height = Math.max(1, Math.round(img.height * escala));
                canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
                URL.revokeObjectURL(url);
                var manterPng = /png/i.test(arquivo.type);
                canvas.toBlob(function (blob) {
                    if (!blob || blob.size >= arquivo.size) {
                        resolve(arquivo);
                        return;
                    }
                    var nome = arquivo.name.replace(/\.[^.]+$/, '') + (manterPng ? '.png' : '.jpg');
                    resolve(new File([blob], nome, {
                        type: manterPng ? 'image/png' : 'image/jpeg',
                        lastModified: Date.now()
                    }));
                }, manterPng ? 'image/png' : 'image/jpeg', 0.85);
            };
            img.onerror = function () {
                URL.revokeObjectURL(url);
                resolve(arquivo); // formato que o navegador nao decodifica (ex.: HEIC)
            };
            img.src = url;
        });
    }

    function inputsDeArquivo(form) {
        return Array.prototype.slice.call(form.querySelectorAll('input[type=file]'))
            .filter(function (input) { return input.files && input.files.length; });
    }

    function temImagemGrande(form) {
        return inputsDeArquivo(form).some(function (input) {
            return reduzivel(input.files[0]);
        });
    }

    function trocarArquivo(input, arquivo) {
        try {
            var dt = new DataTransfer();
            dt.items.add(arquivo);
            input.files = dt.files;
        } catch (e) {
            // Navegador sem DataTransfer: segue com o arquivo original.
        }
    }

    document.addEventListener('submit', function (ev) {
        var form = ev.target;
        if (!form || !form.querySelectorAll || reduzindo) {
            return;
        }
        if (!temImagemGrande(form)) {
            return;
        }

        ev.preventDefault();
        ev.stopImmediatePropagation();
        reduzindo = true;

        var tarefas = inputsDeArquivo(form).map(function (input) {
            if (!reduzivel(input.files[0])) {
                return null;
            }
            return reduzir(input.files[0]).then(function (novo) {
                trocarArquivo(input, novo);
            });
        }).filter(Boolean);

        Promise.all(tarefas).then(function () {
            reduzindo = false;
            var botao = form.querySelector('[type=submit]');
            if (botao) {
                botao.click(); // segue pelo handler que o painel ja tem (ajax)
            } else {
                form.submit();
            }
        });
    }, true);

    // O envio da configuracao e ajax sem tratador de erro: quando o servidor
    // recusa (413) ou cai (502/504) a tela fica sem resposta nenhuma.
    if (window.jQuery) {
        jQuery(document).ajaxError(function (ev, xhr, settings) {
            if (!settings || String(settings.url).indexOf('editar-config.php') === -1) {
                return;
            }
            var msg = 'Não foi possível salvar agora. Tente novamente.';
            var resposta = '';
            try {
                resposta = (JSON.parse(xhr.responseText || '{}').erro || '').trim();
            } catch (e) {
                resposta = String(xhr.responseText || '').trim();
            }
            if (xhr.status === 413) {
                msg = resposta || 'As imagens enviadas passam do limite do servidor.';
            } else if (xhr.status === 502 || xhr.status === 504) {
                msg = 'O servidor demorou para responder e o envio foi interrompido. Tente novamente.';
            } else if (resposta) {
                msg = resposta;
            }
            jQuery('#mensagem-config').removeClass().addClass('text-danger').text(msg);
        });
    }
})();
