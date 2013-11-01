/*
 * 	Character Count Plugin - jQuery plugin
 * 	Dynamic character count for text areas and input fields
 *	written by Alen Grakalic	
 *	http://cssglobe.com/post/7161/jquery-plugin-simplest-twitterlike-dynamic-character-count-for-textareas
 *
 *	Copyright (c) 2009 Alen Grakalic (http://cssglobe.com)
 *	Dual licensed under the MIT (MIT-LICENSE.txt)
 *	and GPL (GPL-LICENSE.txt) licenses.
 *	Built for jQuery library
 *	http://jquery.com
 *  @modified for JR5 by the Jamroom Network.
 */
 
(function($) {

	$.fn.charCount = function(options){
	  
		// default configuration properties
		var defaults = {	
			allowed: 140,
			warning: 20,
			cssWarning: 'action_warning',
			cssExceeded: 'action_exceeded'
		};
		var options = $.extend(defaults, options);
		
		function calculate(obj){
			var count = $(obj).val().length;
			var available = options.allowed - count;
			if (available <= options.warning && available >= 0){
				$('#action_text_counter').addClass(options.cssWarning);
			} else {
				$('#action_text_counter').removeClass(options.cssWarning);
			}
			if (available < 0){
				$('#action_text_counter').addClass(options.cssExceeded);
			} else {
				$('#action_text_counter').removeClass(options.cssExceeded);
			}
            $('#action_text_num').html(available);
		};
		this.each(function() {
			calculate(this);
			$(this).keyup(function(){calculate(this)});
			$(this).change(function(){calculate(this)});
		});


	};

})(jQuery);
