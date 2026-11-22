<?php
/**
 * Textes par défaut des pages « Politique de confidentialité » et « Conditions générales ».
 * Modifiables ensuite dans l'admin (Paramètres du site → Pages légales).
 * Jetons remplacés à l'affichage : {email} (e-mail de contact), {address} (adresse postale), {site} (adresse du site).
 * À faire relire par l'administration de l'université avant publication définitive.
 */
return [
    'legal_privacy' => <<<'TXT'
L'Université Adventiste Zurcher (UAZ) attache une grande importance à la protection de vos données personnelles. Cette page explique quelles données nous collectons sur le site {site}, pourquoi, et quels sont vos droits.

## Responsable du traitement
Université Adventiste Zurcher — {address}. Contact : {email}.

## Données que nous collectons
- **Formulaire de promesse de don** : nom, e-mail, téléphone (facultatif), pays (facultatif), projet choisi, montant annoncé, moyen de don et message.
- **Formulaire de contact** : nom, e-mail, téléphone (facultatif), sujet et message.
- **Données techniques** : l'adresse IP de l'expéditeur d'un formulaire est conservée temporairement pour limiter le spam.
- **Statistiques de fréquentation** : nous comptons les pages vues de façon anonyme (page visitée, langue, type d'appareil, site de provenance), **sans cookie et sans enregistrer votre adresse IP**.

Nous ne collectons **aucune donnée bancaire** : les paiements en ligne sont traités directement par des prestataires sécurisés (Stripe pour la carte bancaire, MVola et Orange Money pour le Mobile Money), qui nous transmettent seulement le résultat du paiement. Pour un don en ligne, nous conservons votre nom, votre e-mail, votre numéro de téléphone (Mobile Money), le montant, la fréquence et la référence du paiement.

## Pourquoi nous utilisons vos données
- Vous remercier et vous envoyer un reçu si vous le demandez.
- Affecter votre don au projet choisi et vous informer de son avancement.
- Répondre à vos questions.
- Afficher votre nom sur le mur des donateurs, **uniquement avec votre accord** (votre nom peut aussi être masqué).

Base légale : votre consentement et l'intérêt légitime de l'université à gérer les dons qu'elle reçoit.

## Destinataires
Vos données sont réservées à l'équipe de l'UAZ chargée des dons. Elles ne sont **jamais vendues ni cédées**. Le site est hébergé sur un serveur sécurisé (Hostinger) ; l'hébergeur n'y accède pas pour ses propres besoins.

## Durée de conservation
Les messages et promesses de don sont conservés au maximum 3 ans après le dernier échange, puis supprimés. Les informations du mur des donateurs restent publiées tant que vous ne demandez pas leur retrait.

## Cookies
- **Cookie de session** (« uaz_session ») : indispensable au fonctionnement des formulaires et à leur sécurité. Il est supprimé à la fermeture du navigateur.
- **Cookie de consentement** (« uaz_consent ») : mémorise votre choix concernant les cookies, pendant 6 mois.
- **Mesure d'audience Google Analytics** : utilisée uniquement si elle est activée par l'université **et** si vous l'acceptez dans le bandeau des cookies.

Vous pouvez modifier votre choix à tout moment grâce au lien « Gérer les cookies » en bas de chaque page.

## Vos droits
Conformément à la loi malgache n° 2014-038 sur la protection des données à caractère personnel et, pour les donateurs résidant dans l'Union européenne, au Règlement général sur la protection des données (RGPD), vous disposez d'un droit d'accès, de rectification, d'effacement, d'opposition et de limitation du traitement de vos données.

Pour les exercer, écrivez-nous à {email}. Nous répondons dans un délai d'un mois.

## Sécurité
Le site utilise une connexion chiffrée (HTTPS). Les accès à l'espace d'administration sont protégés par mot de passe et limités à l'équipe autorisée.

## Modifications
Cette politique peut être mise à jour. La date de dernière mise à jour figure en haut de cette page.
TXT,

    'legal_privacy_en' => <<<'TXT'
Zurcher Adventist University (UAZ) is committed to protecting your personal data. This page explains what data we collect on {site}, why we collect it and what your rights are.

## Data controller
Zurcher Adventist University — {address}. Contact: {email}.

## Data we collect
- **Pledge form**: name, email, phone (optional), country (optional), chosen project, announced amount, giving method and message.
- **Contact form**: name, email, phone (optional), subject and message.
- **Technical data**: the IP address of a form sender is kept temporarily to limit spam.
- **Audience statistics**: we count page views anonymously (page visited, language, device type, referring site), **without cookies and without storing your IP address**.

We collect **no banking data**: online payments are processed directly by secure providers (Stripe for bank cards, MVola and Orange Money for Mobile Money), which only send us the result of the payment. For an online gift, we keep your name, email, phone number (Mobile Money), the amount, the frequency and the payment reference.

## Why we use your data
- To thank you and send you a receipt if you ask for one.
- To assign your gift to the chosen project and keep you informed of its progress.
- To answer your questions.
- To display your name on the donor wall, **only with your consent** (your name can also be masked).

Legal basis: your consent and the university's legitimate interest in managing the gifts it receives.

## Recipients
Your data is only available to the UAZ team in charge of giving. It is **never sold or shared**. The website is hosted on a secure server (Hostinger); the host does not access it for its own purposes.

## Retention period
Messages and pledges are kept for up to 3 years after the last exchange, then deleted. Donor wall information stays published until you ask for its removal.

## Cookies
- **Session cookie** ("uaz_session"): required for forms to work securely. It is deleted when you close your browser.
- **Consent cookie** ("uaz_consent"): remembers your cookie choice for 6 months.
- **Google Analytics audience measurement**: used only if enabled by the university **and** if you accept it in the cookie banner.

You can change your choice at any time with the "Manage cookies" link at the bottom of every page.

## Your rights
Under Malagasy law no. 2014-038 on the protection of personal data and, for donors living in the European Union, the General Data Protection Regulation (GDPR), you have the right to access, rectify, erase, object to and restrict the processing of your data.

To exercise these rights, write to us at {email}. We reply within one month.

## Security
The website uses an encrypted connection (HTTPS). Access to the administration area is password-protected and limited to authorised staff.

## Changes
This policy may be updated. The date of the last update is shown at the top of this page.
TXT,

    'legal_terms' => <<<'TXT'
Les présentes conditions générales encadrent l'utilisation du site de dons de l'Université Adventiste Zurcher (UAZ), accessible à l'adresse {site}. En utilisant le site, vous les acceptez.

## Éditeur du site
Université Adventiste Zurcher — {address}. Contact : {email}.

## Objet du site
Le site présente les projets de construction et d'équipement de l'UAZ, explique comment les soutenir et remercie les donateurs. Il permet d'envoyer une promesse de don et un message à l'université.

## Dons
- **Don en ligne** : les paiements sont traités par des prestataires sécurisés (Stripe, MVola, Orange Money) ; le site ne voit ni ne conserve vos données bancaires. Vous recevez une confirmation par e-mail.
- **Don régulier** (chaque mois, tous les 3 mois ou chaque année) : par carte, chaque échéance est prélevée automatiquement ; par Mobile Money, vous recevez un e-mail de rappel avec un lien de paiement. Vous pouvez arrêter votre don régulier à tout moment grâce au lien reçu par e-mail ou en nous écrivant.
- Les dons peuvent aussi être faits directement par virement bancaire, Mobile Money ou Western Union, selon les coordonnées indiquées sur la page « Comment donner ».
- Une **promesse de don** envoyée par formulaire ne constitue pas un paiement : elle permet à l'université de vous remercier et d'affecter votre don.
- Les dons sont affectés au projet que vous indiquez. Si ce projet est entièrement financé ou n'est plus réalisable, votre don est affecté au projet le plus proche ou là où le besoin est le plus grand.
- Un reçu peut être délivré sur demande, par e-mail à {email}.
- Les dons sont volontaires et, sauf erreur manifeste, ne sont pas remboursables.

## Informations publiées
Les montants, l'avancement et les fiches de suivi des projets sont mis à jour par l'université et donnés à titre indicatif. Ils peuvent évoluer.

## Mur des donateurs et témoignages
Le nom d'un donateur, sa photo ou son message ne sont publiés qu'avec son accord. Vous pouvez demander à tout moment leur modification ou leur retrait.

## Propriété intellectuelle
Les textes, photos, logos et éléments graphiques du site appartiennent à l'UAZ ou sont utilisés avec autorisation. Toute reproduction sans accord préalable est interdite.

## Liens externes
Le site contient des liens vers des sites tiers (réseaux sociaux, prestataires de paiement, site de l'université). L'UAZ n'est pas responsable de leur contenu ni de leurs conditions.

## Responsabilité
L'UAZ veille à l'exactitude des informations et à la disponibilité du site, sans pouvoir garantir l'absence d'erreur ou d'interruption.

## Données personnelles
Le traitement de vos données est décrit dans notre politique de confidentialité.

## Droit applicable
Les présentes conditions sont soumises au droit malgache. En cas de litige, une solution amiable sera recherchée en priorité.

## Modifications
Ces conditions peuvent être mises à jour. La date de dernière mise à jour figure en haut de cette page.
TXT,

    'legal_terms_en' => <<<'TXT'
These terms govern the use of the Zurcher Adventist University (UAZ) giving website, available at {site}. By using the website, you accept them.

## Publisher
Zurcher Adventist University — {address}. Contact: {email}.

## Purpose of the website
The website presents UAZ's construction and equipment projects, explains how to support them and thanks donors. It lets you send a pledge and a message to the university.

## Gifts
- **Online giving**: payments are processed by secure providers (Stripe, MVola, Orange Money); the website never sees or stores your banking data. You receive a confirmation by email.
- **Regular gifts** (monthly, quarterly or yearly): by card, each payment is charged automatically; by Mobile Money, you receive a reminder email with a payment link. You can stop your regular gift at any time with the link you receive by email or by writing to us.
- Gifts can also be made directly by bank transfer, Mobile Money or Western Union, using the details shown on the "How to give" page.
- A **pledge** sent through the form is not a payment: it lets the university thank you and assign your gift.
- Gifts are assigned to the project you choose. If that project is fully funded or can no longer be carried out, your gift is assigned to the closest project or where the need is greatest.
- A receipt can be issued on request, by email to {email}.
- Gifts are voluntary and, except in case of obvious error, are not refundable.

## Published information
Amounts, progress and project progress reports are updated by the university and are given for information. They may change.

## Donor wall and testimonials
A donor's name, photo or message is only published with their consent. You can ask for it to be changed or removed at any time.

## Intellectual property
The texts, photos, logos and graphics on the website belong to UAZ or are used with permission. Any reproduction without prior consent is prohibited.

## External links
The website contains links to third-party sites (social networks, payment providers, the university website). UAZ is not responsible for their content or terms.

## Liability
UAZ takes care to keep information accurate and the website available, but cannot guarantee that it is free of errors or interruptions.

## Personal data
How your data is processed is described in our privacy policy.

## Governing law
These terms are governed by Malagasy law. In the event of a dispute, an amicable solution will be sought first.

## Changes
These terms may be updated. The date of the last update is shown at the top of this page.
TXT,
];
