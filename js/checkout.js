// const settings = window.wc.wcSettings.getSetting("paydunya_data", {});
// const label =
//   window.wp.htmlEntities.decodeEntities(settings.title) ||
//   window.wp.i18n.__("PAYDUNYA Gateway", "paydunya");
// const Content = () => {
//   return window.wp.htmlEntities.decodeEntities(
//     settings.description ||
//       "PAYDUNYA est la passerelle de paiement la plus populaire pour les achats en ligne au Sénégal"
//   );
// };
// const Block_Gateway = {
//   name: "paydunya",
//   label: label,
//   content: Object(window.wp.element.createElement)(Content, null),
//   edit: Object(window.wp.element.createElement)(Content, null),
//   canMakePayment: () => true,
//   ariaLabel: label,
//   supports: {
//     features: settings.supports,
//   },
// };
// window.wc.wcBlocksRegistry.registerPaymentMethod(Block_Gateway);

const settings = window.wc.wcSettings.getSetting("paydunya_data", {});
const label =
  window.wp.htmlEntities.decodeEntities(settings.title) ||
  window.wp.i18n.__("PAYDUNYA Gateway", "paydunya");

const Content = () => {
  const description = window.wp.htmlEntities.decodeEntities(
    settings.description ||
      "est la passerelle de paiement la plus populaire pour les achats en ligne au Sénégal"
  );

  // Vérifiez si l'URL de l'icône est définie dans les paramètres
  const iconUrl = settings.iconUrl || "assets/images/logo.png"; // Remplacez par l'URL par défaut de votre choix

  // Debugging: Log the icon URL and description
  console.log("Icon URL:", iconUrl);
  console.log("Description:", description);

  // Retourne le JSX avec l'icône et la description
  return window.wp.element.createElement(
    "div",
    null,
    window.wp.element.createElement("img", {
      src: iconUrl,
      alt: label,
      style: { width: "50px", height: "50px", marginRight: "10px" },
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
