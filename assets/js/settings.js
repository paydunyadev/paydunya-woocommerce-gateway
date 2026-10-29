// Page de réglages PAYDUNYA : les champs SMS ne sont modifiables que si la notification SMS est activée.
jQuery(function ($) {
  var $sms = $("#woocommerce_paydunya_sms");
  var $smsFields = $("#woocommerce_paydunya_sms_url, #woocommerce_paydunya_sms_message");

  function toggleSmsFields() {
    $smsFields.prop("disabled", !$sms.is(":checked"));
  }

  toggleSmsFields();
  $sms.on("change", toggleSmsFields);
});
