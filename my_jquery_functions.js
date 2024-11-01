jQuery.noConflict();


(function($){

  $.fn.wata_js_validate = function() {
		if($(this).is("select")) {
			$(this).css('background', '#fff');
		} else {
			$(this).css('background', '#fff url('+pluginsUrl+'/wp-autopost-trakttv-activity/images/green-checkmark_v3.png) no-repeat 98% center');
}
		$(this).css("box-shadow", "0 0 5px #5cd053");
		$(this).css("border-color", "#28921f");



  }
  $.fn.wata_js_invalidate = function() {
		$(this).css('background','#fff url('+pluginsUrl+'/wp-autopost-trakttv-activity/images/red-cross.png) no-repeat 98% center');
		$(this).css("box-shadow", "0 0 5px #d45252");
		$(this).css("border-color", "#b03535");
  }

})(jQuery);


jQuery(function ($) {//You can safely use $ in this code block to reference jQuery 
$(document).ready(function(){

// The checkbox controls the disabled attribute for textarea
$('#wata_post_signature_option').click(function() {
    var $this = $(this);   
    if ($this.is(':checked')) {
        $('#wata_post_signature_template').removeAttr('disabled');
    } else {
        $('#wata_post_signature_template').attr("disabled","disabled"); 
    }
});


});
});

