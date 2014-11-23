(function ($) {
	
	window.setupFormCollection = function(formSelector) {
		var f = $(formSelector);
		var p = f.data('prototype');
		
		var a = $('.add-btn', f);
		if (a.length > 0) f.after(a.on('click', addFunc));
		
		var d = $('.del-btn', f);
		if (d.length > 0) d.on('click', removeFunc);
		
		function removeFunc() {
			var e = $(this).parent();
			/*
			e.removeClass('in');
			setTimeout(function() {
				e.remove();
			}, 500);
			*/
			if ($.support.transition && e.hasClass('fade')) {
				//e.one($.support.transition.end, removeElement(e));
				e.removeClass('in');
				setTimeout(removeElement(e), 500);
			} else {
				e.remove();
			}
			function removeElement(e) {
				e.remove();
			}
		}
		
		function addFunc() {
			var lst = $('> div', f).last();
			var id = (lst.length > 0) ? lst.data('id') + 1 : 0;
			var e = $(p.replace(new RegExp('__name__label__','g'), id).replace(new RegExp('__name__','g'), id));
			var d = $('.del-btn', e);
			if (d.length > 0) d.on('click', removeFunc);
			f.append(e);
			e.addClass('fade');
			setTimeout(function() { e.addClass('in'); }, 1);
		}
	};
	
})(jQuery);