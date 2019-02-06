jQuery(document).ready(function() {
	jQuery(".nsubmite").click(function() {
		jQuery.post(the_ajax_script.ajaxurl, jQuery("#wbnfe").serialize(), function(t) {
	        jQuery("#nresulte").html(t);
	        jQuery(".regfild").val('');
	    })
	});
});