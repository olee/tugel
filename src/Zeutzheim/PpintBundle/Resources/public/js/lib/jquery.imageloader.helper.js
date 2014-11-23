
function postLoadImage(elements, background, reloadTimeout, errorImage) {
	elements.imageloader({
		background: background,
		callback: function (el) { $(el).fadeIn(); },
		eacherror: function (el) { 
			var $el = $(el);
			if (errorImage)
				$el.attr('src', errorImage);
			if (reloadTimeout)
				setTimeout(function() { postLoadImage($el, background, reloadTimeout, errorImage); }, reloadTimeout);
		},
	}); 
}
