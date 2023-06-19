jQuery(document).ready(function() {
    jQuery("body").on("click", ".cf7p_result_btn", function() {
        var $this = jQuery(this);
        var form_id = $this.data("value");
        jQuery(this).parents('.wpcf7').find('.wpcf7-response-output').addClass('cf7p-hide-fields');
        jQuery.ajax({
            url: custom_call.ajaxurl,
            type: "POST",
            data: {
                action: "cf7p_result_btn",
                form_id: form_id,
            },
            success: function(response) {
                $this.parents('.wpcf7').find('.wpcf7-form').children().addClass('cf7p-hide-fields');
                $this.parents('.wpcf7').find('.wpcf7-form .cf7p_view_result').remove();
                $this.parents('.wpcf7').find('.wpcf7-form').append(response);
            }
        });
    });

    jQuery("body").on("click", ".cf7p-btf", function() {
        var $this = jQuery(this);
        jQuery(this).parents('.wpcf7').find('.wpcf7-response-output').removeClass('cf7p-hide-fields');
        if ($this.parents('.wpcf7').find('.wpcf7-form').children().hasClass('cf7p-hide-fields')) {
            $this.parents('.wpcf7').find('.wpcf7-form').children().removeClass('cf7p-hide-fields');
        }
        $this.parents('.wpcf7-form').find('.cf7p_view_result').addClass('cf7p-hide-fields');

    });
});