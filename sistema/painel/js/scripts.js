(function() {
    "use strict";

    // custom scrollbar - apenas em telas grandes com mouse.
    // Em celulares/tablets o niceScroll atrapalha a rolagem por toque.
    var usarBarraCustomizada = window.matchMedia('(min-width: 993px) and (pointer: fine)').matches;

    if (!usarBarraCustomizada || !$.fn.niceScroll) {
        return;
    }

    $("html").niceScroll({styler:"fb",cursorcolor:"#F2B33F", cursorwidth: '6', cursorborderradius: '10px', background: '#424f63', spacebarenabled:false, cursorborder: '0',  zindex: '1000'});

    $(".scrollbar1").niceScroll({styler:"fb",cursorcolor:"rgba(97, 100, 193, 0.78)", cursorwidth: '6', cursorborderradius: '0',autohidemode: 'false', background: '#F1F1F1', spacebarenabled:false, cursorborder: '0'});

	
	
    $(".scrollbar1").getNiceScroll();
    if ($('body').hasClass('scrollbar1-collapsed')) {
        $(".scrollbar1").getNiceScroll().hide();
    }

})(jQuery);

                     
     
  