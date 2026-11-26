/* Menu lateral do painel: abrir/fechar submenus.
 *
 * Substitui o SidebarNav.min.js, que vinha empacotado com eval() e referenciava
 * um global inexistente ("W is not defined"), quebrando o script ao carregar.
 * Este aqui nao depende de jQuery nem de eval: o submenu abre pela classe
 * .active do <li>, que o CSS do painel ja usa para exibir o .treeview-menu.
 */
(function () {
	"use strict";

	function submenuDo(item) {
		for (var i = 0; i < item.children.length; i++) {
			if (item.children[i].classList.contains("treeview-menu")) {
				return item.children[i];
			}
		}
		return null;
	}

	function fechar(item) {
		var submenu = submenuDo(item);
		item.classList.remove("active");
		if (submenu) { submenu.style.display = "none"; }
	}

	function abrir(item) {
		var submenu = submenuDo(item);
		item.classList.add("active");
		if (submenu) { submenu.style.display = "block"; }
	}

	document.addEventListener("click", function (evento) {
		var link = evento.target.closest ? evento.target.closest("a") : null;
		if (!link) { return; }

		// So o item de menu que tem submenu responde a este clique.
		var item = link.parentElement;
		if (!item || !item.classList.contains("treeview") || !submenuDo(item)) {
			return;
		}

		evento.preventDefault();

		if (item.classList.contains("active")) {
			fechar(item);
			return;
		}

		// Um submenu aberto por vez no mesmo nivel (como no script antigo).
		var irmaos = item.parentElement ? item.parentElement.children : [];
		for (var i = 0; i < irmaos.length; i++) {
			if (irmaos[i] !== item && irmaos[i].classList && irmaos[i].classList.contains("treeview")) {
				fechar(irmaos[i]);
			}
		}

		abrir(item);
	});
})();
