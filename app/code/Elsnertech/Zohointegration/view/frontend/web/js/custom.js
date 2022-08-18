define(['jquery'], function($){
   "use strict";
    $(document).ready(function(){

    let test = jQuery('#url').val();
    jQuery('.refress').click(function(){
        var url = test+"zohointegration/index/save";
        let cd = jQuery('#code').val();
        let ci = jQuery('#client_id').val();
        let cs = jQuery('#client_secret').val();
        let ru = jQuery('#redirect_uri').val();
        jQuery.ajax({
                url: url,
                type: "POST",
                data: {n1:cd,n2:ci,n3:cs,n4:ru},
                cache: false,
                success: function(response){
                  jQuery("#output").val(response);
            }
        });
    });

    });
});