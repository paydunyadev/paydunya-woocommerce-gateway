const settings = window.wc.wcSettings.getSetting("paydunya_data", {});
const label =
  window.wp.htmlEntities.decodeEntities(settings.title) ||
  window.wp.i18n.__("PAYDUNYA", "paydunya");

const Content = () => {
  const description = window.wp.htmlEntities.decodeEntities(
    settings.description ||
      "PAYDUNYA est la plateforme qui facilite le paiement pour l'achat de biens et services via mobile money et cartes bancaires."
  );

  const pathname = window.location.pathname;

  // Trouver le nom du dossier dans le chemin
  const pathParts = pathname.split('/');
  
  // Le nom du dossier est le deuxième élément du tableau (index 1), si il existe
  const folderName = pathParts[1] || ''; // "" en production si WordPress est à la racine
  
  // Construire l'URL complète de l'icône en utilisant le nom du dossier si nécessaire
  const iconUrl = window.location.protocol + "//" + window.location.host + (folderName ? "/" + folderName : "") + "/wp-content/plugins/paydunya-woocommerce-gateway-master/assets/images/Icone.svg";
 
 
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
