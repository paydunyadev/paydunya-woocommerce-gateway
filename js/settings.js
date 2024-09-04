jQuery(document).on("ready", function () {
  if (jQuery("#woocommerce_mpowerpayment_sms").is(":checked")) {
    jQuery("#woocommerce_mpowerpayment_sms_url").prop("disabled", false);
    jQuery("#woocommerce_mpowerpayment_sms_message").prop("disabled", false);
  } else {
    jQuery("#woocommerce_mpowerpayment_sms_url").prop("disabled", true);
    jQuery("#woocommerce_mpowerpayment_sms_message").prop("disabled", true);
  }
  jQuery("#woocommerce_mpowerpayment_sms").on("click", function () {
    if (jQuery(this).is(":checked")) {
      jQuery("#woocommerce_mpowerpayment_sms_url").prop("disabled", false);
      jQuery("#woocommerce_mpowerpayment_sms_message").prop("disabled", false);
    } else {
      jQuery("#woocommerce_mpowerpayment_sms_url").prop("disabled", true);
      jQuery("#woocommerce_mpowerpayment_sms_message").prop("disabled", true);
    }
  });
});

// jQuery(document).ready(function () {
//   const $smsCheckbox = jQuery("#woocommerce_mpowerpayment_sms");
//   const $smsUrlInput = jQuery("#woocommerce_mpowerpayment_sms_url");
//   const $smsMessageInput = jQuery("#woocommerce_mpowerpayment_sms_message");

//   toggleInputs($smsCheckbox.is(":checked"));

//   $smsCheckbox.on("click", function () {
//     toggleInputs($smsCheckbox.is(":checked"));
//   });

//   function toggleInputs(checked) {
//     $smsUrlInput.prop("disabled", !checked);
//     $smsMessageInput.prop("disabled", !checked);
//   }
// });
