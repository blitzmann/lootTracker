// simple function from <http://jqueryminute.com/calculating-the-sum-of-inputs/>
// edited by me to return a masked string via meiomask.js
(function($){  
	$.fn.sumValues = function() {
		var sum = 0; 
		this.each(function() {
			if ( $(this).is(':input') ) {
				var val = $(this).val();
			} else {
				var val = $(this).text();
			}
			sum += parseFloat( ('0' + val).replace(/[^0-9-\.]/g, ''), 10 );
		});
		return $.mask.string(sum, 'integer');
	};
})(jQuery);  