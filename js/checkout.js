const settings = window.wc.wcSettings.getSetting("paydunya_data", {});
const label =
  window.wp.htmlEntities.decodeEntities(settings.title) ||
  window.wp.i18n.__("PAYDUNYA", "paydunya");

const Content = () => {
  const description = window.wp.htmlEntities.decodeEntities(
    settings.description ||
      "PAYDUNYA est la plateforme qui facilite le paiement pour l'achat de biens et services via mobile money et cartes bancaires."
  );

  // Vérifiez si l'URL de l'icône est définie dans les paramètres
  const domain = window.location.protocol + "//" + window.location.host;
  const iconUrl =
    settings.icon ||
    domain +
      "/wp-content/plugins/paydunya-woocommerce-gateway-paydunya-woocommerce-payment-gateway/assets/images/Icone.svg"; // Remplacez par l'URL par défaut de votre choix

  // Retourne le JSX avec l'icône et la description alignée
  return window.wp.element.createElement(
    "div",
    { style: { display: "flex", alignItems: "center" } },
    window.wp.element.createElement("img", {
      src: iconUrl,
      alt: label,
      style: { width: "32px", height: "32px", marginRight: "5px" },
    }),
    window.wp.element.createElement("span", null, description)
  );
};

const Block_Gateway = {
  name: "paydunya",
  label: label,
  content: window.wp.element.createElement(Content, null),
  edit: window.wp.element.createElement(Content, null),
  canMakePayment: () => true,
  ariaLabel: label,
  supports: {
    features: settings.supports,
  },
};

window.wc.wcBlocksRegistry.registerPaymentMethod(Block_Gateway);
