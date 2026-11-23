// ============================================================
// Alternador de tema (escuro / claro) - Barbearia PRO
// Preferencia compartilhada com o painel via localStorage: bb_tema
// ============================================================
(function () {
  'use strict';

  var CHAVE = 'bb_tema';

  function temaAtual() {
    return localStorage.getItem(CHAVE) || 'escuro';
  }

  function aplicarTema(tema) {
    document.documentElement.setAttribute('data-theme', tema);

    var btn = document.getElementById('btn-tema');
    if (btn) {
      var icone = btn.querySelector('.tema-icone');
      if (icone) {
        icone.className = 'tema-icone fa ' + (tema === 'claro' ? 'fa-sun-o' : 'fa-moon-o');
      }
      btn.title = tema === 'claro' ? 'Mudar para o tema escuro' : 'Mudar para o tema claro';
      btn.setAttribute('aria-label', btn.title);
    }
  }

  function alternarTema() {
    var novo = temaAtual() === 'claro' ? 'escuro' : 'claro';
    localStorage.setItem(CHAVE, novo);
    aplicarTema(novo);
  }

  // Aplica o tema salvo assim que o DOM estiver pronto
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      aplicarTema(temaAtual());
      var btn = document.getElementById('btn-tema');
      if (btn) {
        btn.addEventListener('click', alternarTema);
      }
    });
  } else {
    aplicarTema(temaAtual());
    var btn = document.getElementById('btn-tema');
    if (btn) {
      btn.addEventListener('click', alternarTema);
    }
  }
})();
