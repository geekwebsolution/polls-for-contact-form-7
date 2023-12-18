jQuery(document).ready(function() {

    //make csv of polls data
    jQuery(".cf7-export-csv-btn").on("click",function() {
        var formId = jQuery(this).attr("data-form-id");
        // console.log(form_id);
        jQuery.ajax({
            url: custom_call.ajaxurl,
            type: "POST",
            data: {
                action: "cf7p_export_csv",
                form_id: formId
            },
            success: function(response) {

                // Assuming the response contains the CSV content

                // Create a hidden anchor element to trigger the download
                
                // console.log(response);
                var downloadLink = document.createElement("a");
                downloadLink.href = "data:text/csv;charset=utf-8," + encodeURIComponent(response);
                downloadLink.download = "poll_data.csv";
                downloadLink.style.display = "none";
                document.body.appendChild(downloadLink);
                downloadLink.click();
                document.body.removeChild(downloadLink);
            },
        });
     });

    jQuery("input[name=cf7p_limit]").removeAttr('min');

    /* view result scroll */
    jQuery(".cf7p-view-poll-result").click(function() {
        jQuery([document.documentElement, document.body]).animate({
            scrollTop: jQuery("#wpcf7-contact-form-editor").offset().top
        }, 500, function() {
            jQuery("#cf7p-polls-result-tab a").trigger("click");
        });
    });

    /*  */
    // jQuery("body").on("submit", "#wpcf7-admin-form-element", function() {
    //     $limit = jQuery("input[name=cf7p_limit]").val();

    //     if ((jQuery(".cf7p-set-limit").is(":checked")) && $limit <= 0) {
    //         jQuery("input[name=cf7p_limit]").addClass("cf7p_error");
    //         return false;
    //     }
    // });

    jQuery(function() {
        jQuery('.cf7p-color-field').wpColorPicker();
    });

    jQuery("body").on('change', '.cf7p-view-result', function() {
        ((jQuery(this).is(":checked")) ? jQuery(".cf7p_result_btn_shortcode").parents('tr').fadeIn() : jQuery(".cf7p_result_btn_shortcode").parents('tr').css("display", "none"));
    });

    jQuery("body").on('change', '.cf7p-set-limit', function() {
        if ((jQuery(this).is(":checked"))) {
            jQuery(".cf7p_set_limit").parents('tr').fadeIn();
            jQuery(".cf7p_set_limit").parents('tr').find('input').val('1');

        } else {
            jQuery(".cf7p_set_limit").parents('tr').css("display", "none");
        }

    });
    jQuery("body").on('change', '.cf7p_limit_per_mail', function() {
        if ((jQuery(this).is(":checked"))) {
            jQuery(".cf7p_mail").removeClass('cf7p-hide-fields');
        } else {
            jQuery(".cf7p_mail").addClass('cf7p-hide-fields');
        }

    });

    jQuery("body").on('change', '.cf7p-field-row select',function(){
        var prev = jQuery(this).closest('tr').attr('datafield');
        var selected_field = jQuery("#cf7p_add").attr("data-all-fields");
        selected_field = selected_field.split(",");
        var current = jQuery(this).val();
        var existing_dropdown_value = jQuery(".cf7p-field-row[datafield=" + current + "]");
        if (existing_dropdown_value.find("select option[value="+ current+"]")) {
            existing_dropdown_value.find("select option[value="+ current+"]").remove();
            existing_dropdown_value.find("select").prepend(jQuery('<option selected="selected"></option>').val(prev).html(prev));
            jQuery(".cf7p-field-row[datafield=" + current + "]").attr("datafield",prev);
            var mycode = {};
            existing_dropdown_value.find("select > option").each(function () {
                if(mycode[this.text]) {
                    jQuery(this).remove();
                } else {
                    mycode[this.text] = this.value;
                }
            });
        }
        jQuery(jQuery(this).closest('tr').attr('datafield',current));
        var fields = [];
        jQuery(".cf7p-field-row").each(function () {
            fields.push(jQuery(this).attr("datafield"))
        });
        jQuery("#cf7p_add").attr("data-selected_fields",fields.toString());
    });
    jQuery("body").on("click", "#cf7p_add", function() {
        var $this = jQuery(this);
        var selected_field = jQuery(this).attr("data-selected_fields");
        $this.find("span.loader").css("visibility", "visible");
        var form_id = jQuery("input[name=cf7p-form-id]").val();
        jQuery.ajax({
            url: custom_call.ajaxurl,
            type: "POST",
            data: {
                action: "cf7p_add_more",
                form_id: form_id,
                selected_field:selected_field
            },
            success: function(response) {
                jQuery("#cf7p_all_polls").append(response);
                $this.find("span.loader").css("visibility", "hidden");
                ((jQuery(".cf7p-no-field").attr("data-msg")) ? jQuery(".cf7p_polls_btn").hide() : jQuery(".cf7p_polls_btn").show());
                $this.parents('.cf7p_polls_btn').find('#cf7p_remove_all').fadeIn();
                var fields = jQuery("#cf7p_all_polls tr:last").find("#cf7p-name option");
                (selected_field == '') ? jQuery("#cf7p_add").attr("data-selected_fields",fields[0].outerText) : jQuery("#cf7p_add").attr("data-selected_fields",selected_field +","+ fields[0].outerText);
                if (jQuery("#cf7p_add").attr("data-selected_fields").length == jQuery("#cf7p_add").attr("data-all-fields").length) {
                    jQuery("#cf7p_add").hide();
                }
            },
        });
    });

    jQuery("body").on("click", ".cf7p_remove_field", function() {
        if (confirm("Are You Sure You Want To Remove ? ")) {
            var form_id = jQuery(this).val();
            var field_name = jQuery(this).data("name");
            jQuery(this).parents('.cf7p-field-row').remove();
            jQuery.ajax({
                url: custom_call.ajaxurl,
                type: "POST",
                data: {
                    action: "cf7p_remove",
                    form_id: form_id,
                    field_name: field_name
                },
                success: function(response) {
                    jQuery("#cf7p_all_polls").append(response);
                    var fields = [];
                    jQuery(".cf7p-field-row").each(function () {
                        fields.push(jQuery(this).attr("datafield"))
                    });
                    jQuery("#cf7p_add").attr("data-selected_fields",fields.toString());
                    if (jQuery("#cf7p_add").attr("data-selected_fields").length != jQuery("#cf7p_add").attr("data-all-fields").length) {
                        jQuery(".cf7p-no-field").hide();
                        jQuery(".cf7p-no-field").removeAttr("data-msg");
                        jQuery(".cf7p_polls_btn").show();
                        jQuery("#cf7p_add").show();
                        jQuery("#cf7p_remove_all").show();
                    }
                    if (jQuery("#cf7p_add").attr("data-selected_fields") == '') {
                        jQuery('input[name=cf7p_status]').removeAttr('checked');
                        jQuery("#cf7p_remove_all").hide();
                    }
                },
            });
        }
    });


    jQuery("body").on("click", "#cf7p_remove_all", function() {
        if (confirm("Are You Sure You Want To Remove All Poll ? ")) {
            var $this = jQuery(this);
            jQuery.ajax({
                url: custom_call.ajaxurl,
                type: "POST",
                data: {
                    action: "cf7p_remove_all",
                    form_id: jQuery("input[name=cf7p-form-id]").val()
                },
                success: function(response) {
                    $this.hide();
                    jQuery('#cf7p_all_polls').empty();
                    if (jQuery('input[name=cf7p_status]').is(':checked')) {
                        jQuery('input[name=cf7p_status]').removeAttr('checked');
                    }
                    
                    jQuery("#cf7p_add").attr("data-selected_fields","");
                    jQuery("#cf7p_add").show();
                    if (jQuery("#cf7p_add").attr("data-selected_fields").length == '') {
                        jQuery("#cf7p_remove_all").hide();

                    }
                },
            });
        }
    });

    
});