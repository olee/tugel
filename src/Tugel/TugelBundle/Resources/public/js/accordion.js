/* 
 * Accordion
 */
+ function($) {
	'use strict';

	$(document).ready(function() {
		$('.accordion').each(function() {
			var accordion = $(this);
			var panels = accordion.find('.panel');
			panels.each(function() {
				var panel = $(this);
				var heading = panel.find('.panel-heading').first();
				var body = panel.find('.panel-collapse').first();
				var expanded = body.hasClass('in');

				// Set attributes
				body.attr('aria-labelledby', heading.attr('id'));
				heading.attr('aria-controls', body.attr('id'));
				heading.attr('href', 'javascript:void()');
				heading.data('target', '#' + body.attr('id'));
				heading.attr('aria-expanded', expanded ? 'true' : 'false');
				if (!expanded)
					heading.addClass('collapsed');

				// Clear any old data of collapse and initialize collapse
				body.data('bs.collapse', null);
				body.collapse({
					toggle : false,
					parent : accordion,
					trigger : heading,
				});

				// Set click-handler
				heading.click(function() {
					body.collapse('toggle');
					heading.find('input[type=radio]').prop('checked', true);
				});
			});
		});
		var anchor = window.location.hash;
		if (anchor)
			$(anchor + '.collapse').collapse('show');
	});
}(jQuery);
