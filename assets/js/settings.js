jQuery(document).on("ready", function() {
    if (jQuery("#woocommerce_paydunya_sms").is(":checked")) {
        jQuery("#woocommerce_paydunya_sms_url").prop("disabled", false);
        jQuery("#woocommerce_paydunya_sms_message").prop("disabled", false);
    } else {
        jQuery("#woocommerce_paydunya_sms_url").prop("disabled", true);
        jQuery("#woocommerce_paydunya_sms_message").prop("disabled", true);
    }
    jQuery("#woocommerce_paydunya_sms").on("click", function() {
        if (jQuery(this).is(":checked")) {
            jQuery("#woocommerce_paydunya_sms_url").prop("disabled", false);
            jQuery("#woocommerce_paydunya_sms_message").prop("disabled", false);
        } else {
            jQuery("#woocommerce_paydunya_sms_url").prop("disabled", true);
            jQuery("#woocommerce_paydunya_sms_message").prop("disabled", true);
        }
    });
});