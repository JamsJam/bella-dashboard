# Pourquoi les tests Avatar existent

Les tests Avatar protègent la sélection des parties, leur présentation dans le
catalogue et le processus asynchrone qui valide, renomme puis déplace une image.

## Tests unitaires

### Contrat des dépôts de parties

`AvatarPartModelInterfaceTest` garantit que chaque dépôt de partie — corps,
sourcils, yeux, visages, cheveux, bouches et nez — reste utilisable par la grille
générique. Il vérifie aussi les deux requêtes nécessaires à la lecture complète
et au filtrage.

**Pourquoi :** modifier un dépôt peut sinon casser le catalogue uniquement pour
une partie d’avatar.

**Erreur couverte :** dépôt ne respectant plus l’interface ou méthode de lecture
retirée. **Limite :** le SQL produit par chaque dépôt n’est pas validé ici.

### Résolution des entités et dépôts

`AvatarEntityResolverTest` et `AvatarRepositoryResolverTest` vérifient les huit
clés acceptées (`body`, `eyebrows`, `eyes`, `face`, `accessory`, `hair`, `mouth`,
`nose`), la correspondance spéciale de `accessory` avec le visage et le rejet
d’une clé inconnue.

**Pourquoi :** une valeur de route ou de formulaire mal résolue interrogerait ou
modifierait la mauvaise table.

**Cas limites :** chaîne vide, clé inconnue et clé plausible mais non déclarée.
**Limite :** casse et espaces ne sont pas normalisés et restent invalides.

### Tri des parties

`AvatarPartSortServiceTest` vérifie le tri des corps par morphologie ou vêtement,
des autres parties par couleur, l’ordre descendant et le tri par défaut du plus
récent au plus ancien. Il contrôle aussi les options propres à chaque partie.

**Pourquoi :** le nom encode ses propriétés à des positions différentes selon
la partie ; un tri générique naïf donnerait un ordre erroné.

**Cas limites :** critère ou direction absents et différences de structure entre
un corps et une autre partie. **Limite :** égalités, noms mal formés et caractères
accentués ne sont pas encore couverts.

### Analyse et classification des noms

`AvatarRenameNameParserTest` vérifie les filtres d’un cheveu, la conversion du
préfixe historique `visage` vers `face` et le refus d’un nom incomplet.

`FaceAccessoryNameMatcherTest` distingue un visage avec accessoire, le marqueur
`-none-`, un segment absent, un segment vide et un suffixe ajouté après `-none-`.

**Pourquoi :** le nom final pilote les relations en base et le répertoire de
destination ; un nom ambigu produirait une entité ou un fichier mal classé.

**Limite :** toutes les extensions, tailles de nom et collisions Unicode ne sont
pas testées ici ; le validateur d’upload reste la barrière dédiée.

### Workflow et autorisation d’écrasement

`AvatarRenameWorkflowTest` parcourt la réussite, l’annulation de validation,
l’échec pendant le renommage puis `retry`. Il vérifie aussi qu’un avatar déjà
renommé ne peut pas recommencer le traitement.

`AvatarOverwriteAuthorizationGuardTest` couvre les quatre combinaisons entre
existence de la cible et autorisation : seule une collision non confirmée doit
bloquer la transition.

**Pourquoi :** empêcher un saut d’état et l’écrasement silencieux d’un fichier.

**Cas limites :** cible absente avec autorisation superflue, cible présente avec
ou sans autorisation et état terminal. **Limite :** les pannes physiques du disque
sont du ressort du handler.

## Tests d’intégration

`AvatarRenameValidationWorkflowTest` récupère le vrai workflow depuis le
conteneur. Il garantit que son marking store écrit dans `AvatarTemp::status`, que
les gardes partagent le contexte et que la transition mène à `validated`. Ce test
a notamment détecté une configuration erronée utilisant `publicationStatus`.

`AvatarRenameProcessTest` crée un PNG temporaire et une ligne Doctrine, valide
les filtres, envoie le message au transport Messenger de test, exécute le vrai
handler puis contrôle :

- la suppression de l’entrée temporaire ;
- la création du nez et de ses relations couleur/forme ;
- la normalisation de la couleur hexadécimale ;
- le déplacement du fichier au bon emplacement ;
- la conservation du contenu par checksum SHA-256.

**Pourquoi :** chaque composant peut réussir seul alors que leur assemblage
échoue à cause du conteneur, du mapping Doctrine, du transport ou des chemins.

**Cas limites :** refus de toute base sans suffixe `_test` et nettoyage des
données/fichiers. **Limites :** worker réellement séparé, concurrence entre deux
messages et permissions disque insuffisantes ne sont pas simulés.

## Test applicatif

`AvatarCatalogueTest` connecte un administrateur et ouvre `/avatar`. Il vérifie
la réponse HTTP, le titre, la grille, le catalogue et les actions Ajouter,
Renommer et Gérer les couleurs.

**Pourquoi :** un service valide ne garantit pas que la sécurité, le contrôleur,
Twig et le DOM final fonctionnent ensemble.

**Erreur couverte :** route inaccessible, erreur Twig, grille absente ou action
retirée. **Limites :** BrowserKit n’exécute ni Turbo ni Stimulus ; glisser-déposer,
modales et mises à jour dynamiques nécessitent un navigateur E2E réel.

## Test end-to-end Panther

`AvatarNavigationTest` demande d’abord `/avatar` sans session et vérifie la
redirection vers la connexion. Il force ensuite le thème clair, clique sur le
bouton de thème et contrôle dans le DOM vivant que JavaScript applique le thème
sombre. Après une authentification réelle, il vérifie le retour vers le catalogue
demandé puis clique sur Ajouter : Turbo doit charger `/avatar/add` et Stimulus
doit être connecté à la dropzone.

**Pourquoi :** BrowserKit n’exécute pas JavaScript et ne peut donc pas détecter
un importmap cassé, un contrôleur Stimulus absent ou une navigation Turbo qui ne
remplace plus la page.

**Cas limites et erreurs :** visiteur anonyme, conservation de la cible après
connexion, exécution JavaScript et présence du branchement Stimulus. Le test
refuse aussi toute base ne se terminant pas par `_test`.

**Limites :** le glisser-déposer natif d’un fichier, une collision pendant le
renommage et l’exécution asynchrone complète du worker ne sont pas encore pilotés
depuis le navigateur.

En local, `make test-avatar-e2e` utilise ChromeDriver. Dans Docker,
`make docker-test-avatar` utilise Selenium/Chromium et exécute successivement les
tests d’intégration, applicatifs et end-to-end.
