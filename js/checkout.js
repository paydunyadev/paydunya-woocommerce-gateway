// Data provided by PaydunyaBlocks::get_payment_method_data() (title, description, icon, supports).
const settings = window.wc.wcSettings.getSetting("paydunya_data", {});
const label =
  window.wp.htmlEntities.decodeEntities(settings.title || "") ||
  window.wp.i18n.__("PAYDUNYA", "paydunya");

const Content = () => {
  const description = window.wp.htmlEntities.decodeEntities(
    settings.description ||
      "PAYDUNYA est la plateforme qui facilite le paiement pour l'achat de biens et services via mobile money et cartes bancaires."
  );

  // The logo URL is provided by WordPress (plugins_url),
  // both locally and on a live hosting environment.
  const icon = settings.icon
    ? window.wp.element.createElement("img", {
        src: settings.icon,
        alt: label,
        style: { width: "32px", height: "32px", marginRight: "8px", flexShrink: 0 },
      })
    : null;

  return window.wp.element.createElement(
    "div",
    { style: { display: "flex", alignItems: "center" } },
    icon,
    window.wp.element.createElement("span", null, description)
  );
};

const Block_Gateway = {
  name: "paydunya",
  label: label,
  content: window.wp.element.createElement(Content, null),
  edit: window.wp.element.createElement(Content, null),
  // The currency is already checked on the server side (Paydunya_Blocks::is_active()).
  canMakePayment: () => true,
  ariaLabel: label,
  supports: {
    features: settings.supports,
  },
};

window.wc.wcBlocksRegistry.registerPaymentMethod(Block_Gateway);
