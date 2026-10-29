# === Passerelle de paiement PAYDUNYA pour WooCommerce ===
Contributors: PAYDUNYA
Tags: woocommerce, passerelle de paiement, portefeuille mobile, paiement, mobilemoney, mobile money, orange, sénégal, orange money, paiement par carte, carte, banque, bénin, côte d'ivoire, mtn, wari, free money, wizall money, wizall, visa, mastercard
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
WC requires at least: 8.0
WC tested up to: 11.2
Stable tag: 2.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

La passerelle de paiement PAYDUNYA pour WooCommerce vous permet d'accepter des paiements sur votre boutique WooCommerce. PAYDUNYA prend en charge les paiements par Mobile Money et par carte bancaire.



## == Description ==

Ce plugin est la passerelle de paiement PAYDUNYA pour WooCommerce.


Pour créer votre compte PAYDUNYA, rendez-vous sur notre site en cliquant [ici](https://paydunya.com/signup).

La passerelle de paiement PAYDUNYA pour WooCommerce vous permet d'accepter des paiements sur votre boutique WooCommerce. Le client est redirigé vers la page de paiement PAYDUNYA pour régler sa commande.

Avec ce plugin, vous pourrez accepter les moyens de paiement suivants sur votre boutique :

- __Mobile Money__
- __Cartes bancaires__

= Remarque =

Ce plugin est destiné aux marchands du Sénégal.

## = Fonctionnalités =

-   __Paiement__ par Mobile Money et carte bancaire.
- 	__Intégration transparente__ à la page de commande de WooCommerce.
-   __Compatible avec le checkout classique et le checkout en blocs__, ainsi qu'avec le stockage des commandes haute performance (HPOS) de WooCommerce.
-   __Statut de commande « Payée »__ : après un paiement PAYDUNYA réussi, la commande passe en « Payée ». Le marchand la passe lui-même en « Terminée » une fois la commande livrée.
-   __Mise à jour automatique du statut de la commande__ selon PAYDUNYA : payée, annulée ou échouée (au retour du client sur le site, et par la notification PAYDUNYA une fois le site en ligne).
- 	__Ajout de la devise BCEAO XOF__. Pour la sélectionner, allez dans __WooCommerce > Réglages__ dans le menu de gauche, puis cliquez sur l'onglet __Général__. Dans __Devise__, choisissez BCEAO XOF, puis cliquez sur __Enregistrer les modifications__.
- 	__Notification par SMS__ envoyée au client après un paiement réussi. Cette fonctionnalité doit être activée et configurée pour fonctionner ; vous aurez besoin d'une API d'envoi de SMS.


= Suggestions / Demandes de fonctionnalités =

Si vous avez des suggestions ou une demande de nouvelle fonctionnalité, n'hésitez pas à nous contacter par e-mail à paydunya@paydunya.com



# == Installation ==

= Installation automatique =
- 	Connectez-vous à l'administration de votre site WordPress.
- 	Allez dans « Extensions > Ajouter » dans le menu de gauche.
- 	Dans le champ de recherche, saisissez « PAYDUNYA WooCommerce ».
-	Dans les résultats, cliquez sur « Installer maintenant » sous « Passerelle de paiement PAYDUNYA pour WooCommerce ».
-	Une fenêtre vous demande de confirmer l'installation de l'extension.

- Cliquez sur « Continuer » pour lancer l'installation. L'écran suivant indique si l'installation a réussi ou signale les éventuels problèmes.
- Si l'installation a réussi, cliquez sur « Activer l'extension ».
- 	Ouvrez les réglages de WooCommerce et cliquez sur l'onglet « Paiements ».
- 	Cliquez sur « PAYDUNYA ».
-	Configurez les réglages PAYDUNYA (voir ci-dessous).

## = Installation manuelle =
1. 	Téléchargez le fichier zip du plugin.
2. 	Connectez-vous à l'administration WordPress. Cliquez sur « Extensions > Ajouter » dans le menu de gauche.
3.  Cliquez sur « Téléverser une extension », puis sur « Choisir un fichier » pour sélectionner le fichier zip sur votre ordinateur. Cliquez ensuite sur « Installer maintenant ».
4.  Activez l'extension.
5. 	Ouvrez les réglages de WooCommerce et cliquez sur l'onglet « Paiements ».
6. 	Cliquez sur « PAYDUNYA ».
7.	Configurez les réglages PAYDUNYA (voir ci-dessous).



## = Configuration du plugin =
Pour configurer le plugin, allez dans __WooCommerce > Réglages__ dans le menu de gauche, puis cliquez sur l'onglet « Paiements ». __« PAYDUNYA »__ apparaît dans la liste des moyens de paiement. Cliquez dessus pour configurer la passerelle.

__*Vous pouvez placer PAYDUNYA en tête de la liste des moyens de paiement pour en faire le moyen de paiement par défaut.*__

- __Activer/Désactiver__ : cochez la case pour activer la passerelle de paiement PAYDUNYA.
- __Titre__ : texte que verront vos clients pour ce moyen de paiement sur la page de commande.
- __Description__ : message affiché sous ce moyen de paiement sur la page de commande. Vous pouvez y indiquer les moyens de paiement acceptés.
- __Clé Principale__ : saisissez la clé principale fournie par PAYDUNYA.
- __Clé Privée de production__ : saisissez la clé privée de production fournie par PAYDUNYA.
- __Token de production__ : saisissez le token de production fourni par PAYDUNYA.
- __Clé Privée de test__ : saisissez la clé privée de test fournie par PAYDUNYA.
- __Token de test__ : saisissez le token de test fourni par PAYDUNYA.
- __Activer le mode test__ : cochez cette case pour faire des paiements de test. Pensez à la décocher quand vous êtes prêt à accepter de vrais paiements sur votre site.
- __Notification SMS__, __URL de votre API REST d'envoi de SMS__ et __Contenu du SMS__ : réglages de la notification SMS (facultatif).
- Cliquez sur __Enregistrer les modifications__ pour appliquer vos changements.





# == Questions fréquentes ==

## = De quoi ai-je besoin pour utiliser le plugin ? =

1.	L'extension WooCommerce doit être installée et activée sur votre site WordPress.
2.	Vous devez créer un compte PAYDUNYA pour obtenir la clé principale, la clé privée et le token (https://paydunya.com/signup).



## == Journal des modifications ==

## = 2.0.0 =
-   Compatibilité avec WordPress 7.1, WooCommerce 11.2 (HPOS et checkout en blocs) et PHP 8.
-   Nouveau statut de commande « Payée » après un paiement réussi, pour les produits physiques et virtuels.
-   Le statut de la commande suit désormais le statut de la facture PAYDUNYA (payée, annulée, échouée), sans dépendre de la session du client.
-   Notification PAYDUNYA (IPN) sécurisée : vérification du hash, statut revérifié auprès de l'API, montant payé contrôlé.
-   Appels à l'API via l'API HTTP de WordPress avec vérification SSL ; erreurs journalisées dans WooCommerce > État > Journaux (source « paydunya »).
-   Logo de paiement affiché correctement sur le checkout en blocs, en local comme en ligne.
-   Notification SMS : les variables {NUMBER} et {MESSAGE} sont désormais remplacées dans l'URL de l'API SMS.
-   Clés et tokens API masqués dans la page de réglages.
