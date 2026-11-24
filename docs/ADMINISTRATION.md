# Guide du back-office

Accès : `https://votre-domaine/admin` (connexion sur `/connexion`, par email). Après 5 échecs de
connexion, l'accès est bloqué 30 minutes pour ce couple identifiant/adresse IP.

## Rôles

| Rôle | Peut modifier | Peut consulter |
| --- | --- | --- |
| Super administrateur | tout, y compris les autres super administrateurs | tout |
| Administrateur | tout, sauf les comptes super administrateurs | tout |
| Éditeur de contenu | pages, menus, FAQ, équipe, partenaires, carrières, médias, catalogue, tarifs, articles, réalisations | statistiques |
| Commercial | demandes, prospects, newsletter | catalogue, statistiques |
| Support | demandes | prospects, catalogue |
| Lecteur | rien | tout sauf utilisateurs, configuration et audit |

Les droits sont vérifiés par le serveur à chaque action. Désactivez un compte plutôt que de le
supprimer : l'historique reste attribué.

## Modifier le site

### Pages et sections
**Site → Pages**. Une page = un contenu principal facultatif + des **sections** ordonnées.
Chaque section a un *type* qui détermine sa mise en forme :

| Type | Contenu |
| --- | --- |
| Hero | grand bandeau : sur-titre, titre, sous-titre, bouton principal, bouton rond (bouton secondaire), points clés (éléments) ; le nom de l'entreprise s'affiche en très grand en bas |
| Bandeau défilant | fait défiler les domaines d'expertise publiés |
| Domaines / Solutions / Actualités / Équipe / Partenaires / Témoignages / Réalisations / FAQ | se remplissent **automatiquement** avec les contenus publiés |
| Grille d'éléments | cartes icône + titre + texte (éléments) |
| Vision Afrique | carte interactive ; les 3 premiers éléments sont les étapes |
| Feuille de route | étapes avec avancement Réalisé / En cours / Prévu |
| Texte riche, Texte + image, Appel à l'action | contenus libres |

Astuce : dans un titre, `*mots*` affiche ces mots en dégradé.
Les flèches ↑ ↓ réordonnent les sections ; l'œil les masque sans les supprimer.
Dans les listes d'éléments (fonctionnalités, liens de menu…), « Ajouter » crée une ligne et
« Supprimer cet élément » la retire à l'enregistrement.

### Navigation
**Site → Navigation** : en-tête (avec sous-menus via « élément parent ») et colonnes du footer.
Le style « Bouton » affiche le lien comme bouton d'action.

### Configuration
**Administration → Configuration** : nom, logo (fond clair/sombre), favicon, couleurs de marque,
coordonnées, réseaux sociaux, SEO par défaut, bandeau d'annonce, texte de transparence sur
l'hébergement, emails de notification.

## Catalogue et tarifs

- **Domaines d'expertise** : chaque domaine a sa page (`/expertises/<slug>/`) et ses services.
  Choisissez honnêtement le statut de chaque service : *Disponible*, *Bêta*, *À venir*.
- **Solutions** : fiche détaillée, fonctionnalités, captures, démo, tarifs. Décochez
  « contenu d'exemple » quand la fiche est réelle.
- **Tarifs** : un *groupe* = un tableau comparatif. Dans chaque offre, utilisez les mêmes
  libellés de caractéristiques pour aligner la comparaison. « Prix sur devis » masque les montants.

## Articles

Statut *Brouillon* → invisible. *Publié* avec une date future → **programmé** (publication
automatique à la date). « Prévisualiser » montre l'article tel qu'il apparaîtra. L'éditeur
propose gras, italique, titres, listes, citations et liens ; pour les images, utilisez l'image de
couverture ou la médiathèque.

## Réalisations et témoignages

Une réalisation ne peut être publiée qu'avec le **consentement du client** (ou si elle est
marquée « exemple illustratif »). Un témoignage exige un consentement écrit : renseignez sa référence.
Ne publiez que des résultats vérifiables.

## Commercial

- **Demandes** : tout formulaire du site crée une demande (référence `DEV-`, `DEM-`, `CON-`…),
  rattachée automatiquement à un **prospect** (même email). L'équipe reçoit une notification
  dans le back-office et par email ; le demandeur reçoit un accusé de réception.
  Depuis la fiche : changer le statut, affecter, commenter en interne, **répondre par email**
  (la réponse est tracée). Export CSV filtré.
- **Prospects / Pipeline** : statuts Nouveau → Qualifié → Contacté → Démonstration →
  Proposition → Gagné / Perdu, priorité, responsable, valeur estimée, interactions
  (appel, email, rendez-vous…), **relances** datées, conversion en client.
- **Relances** : liste des relances en retard et à venir.

## Statistiques

Mesure interne sans cookie : pages vues et visiteurs uniques par jour (empreinte quotidienne
non réversible). Les visites de l'équipe connectée, des robots et des navigateurs refusant le
suivi ne sont pas comptées. Aucune donnée n'est affichée tant qu'elle n'a pas été mesurée.

## Journal d'audit

Connexions (réussies/échouées), créations, modifications, suppressions, publications,
changements de rôle, paramètres, affectations, emails envoyés, exports et conversions.
Lecture seule.
