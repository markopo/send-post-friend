/**
 * Created by marko on 2016-11-19.
 */
jQuery(function () {


    jQuery("#content").on("click", "button.spf_button", function (e) {
        e.preventDefault();
        var that = jQuery(this);
        var spf_post_id = that.attr("data-spf-post-id");
        var email = jQuery("#spf_email_text_" + spf_post_id).val();
        var message = jQuery("#spf_message_" + spf_post_id).val();

        jQuery.ajax({
            url: spf_ajax.ajax_url,
            type: "POST",

            data: {
                action: 'spf_add_ajax_post',
                post_id: spf_post_id,
                email: email,
                message: message,
                security: spf_ajax.check_nonce
            },
            success: function (response) {

                console.log(response);

                if(response != null) {
                    var obj = JSON.parse(response);
                    var emailToSend = obj[0].email;
                    alert("This will be sent to " + emailToSend + "!");
                }
            }
        });



    });





});