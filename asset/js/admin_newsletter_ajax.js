jQuery(document).ready(function() {
    jQuery('.setting_btn').click(function(){
    	jQuery('.res').html('');
         jQuery.post(
					ajaxurl,
				     jQuery("#wbns").serialize()
					, function(response) {
						jQuery('.res').html(response);
					}
				);
    });
});