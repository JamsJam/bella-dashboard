# Tests du volet Avatar

La raison de chaque test, les erreurs et cas limites couverts et les limites
connues sont détaillées dans
[docs/tests/avatar.md](../../docs/tests/avatar.md).

La couverture Avatar suit quatre niveaux, exécutés dans cet ordre : unitaire,
intégration, applicatif, puis end-to-end.

## Tests unitaires

`tests/Avatar/Unit` vérifie sans kernel ni base de données :

- le contrat commun des dépôts de parties d’avatar ;
- la résolution des entités et des dépôts ;
- le tri, l’analyse des noms et la détection des accessoires ;
- les transitions du workflow et la garde d’écrasement.

Commande : `make test-avatar-unit`.

## Tests d’intégration

`tests/Avatar/Integration` démarre le vrai conteneur Symfony. Il vérifie la
configuration du workflow, ses gardes, Doctrine, Messenger, le handler de
renommage et le déplacement du fichier jusqu’au répertoire final.

Commande : `make test-avatar-integration`.

## Tests applicatifs

`tests/Avatar/Application` utilise `WebTestCase`, BrowserKit, DomCrawler et les
sélecteurs CSS. Il ouvre le catalogue comme administrateur et vérifie la route,
la sécurité, Twig, la grille et les principales actions visibles.

Commande : `make test-avatar-application`.

## Tests end-to-end

`tests/Avatar/EndToEnd` pilote un vrai navigateur avec Panther. Le scénario
actuel vérifie JavaScript, la connexion, le retour au catalogue et la navigation
Turbo vers la dropzone d’ajout.

Commandes : `make test-avatar-e2e` en local ou `make docker-test-avatar` dans
l’environnement reproductible MySQL/Selenium.

Pour lancer les quatre niveaux : `make test-avatar`. Tous ces tests appartiennent
aussi au groupe PHPUnit `avatar` : `php bin/phpunit --group avatar`.

Les assertions importantes décrivent le problème en français avec le préfixe
`Blocage :` ou `Sécurité :` afin que l’échec soit directement exploitable.
