# Exécuter les tests dans Docker

Le profil Compose `test` reproduit l’infrastructure nécessaire à partir du
niveau intégration :

- `database` : MySQL 8.0.32 ;
- `test-app` : serveur Symfony avec PHP 8.3 ;
- `test-runner` : PHPUnit et les commandes Symfony ;
- `test-browser` : Selenium avec Chromium ;
- `mailer` : Mailpit, conservé pour le développement.

## Exécution complète

```bash
make docker-test
make docker-test-down
```

La première commande construit l’image, attend les healthchecks, crée la base
portant le suffixe `_test`, applique les migrations puis lance :

```text
Integration → Application → EndToEnd
```

La construction compile également Sass. Si la commande atteint réellement les
tests, la sortie contient un résumé PHPUnit pour chacun des trois niveaux et se
termine par `OK`. Un affichage limité à `Built`, `Started`, `Waiting` ou `Healthy`
signifie seulement que l’infrastructure est en préparation : aucun test n’a
encore été exécuté.

Le code de retour permet une vérification automatique : `echo $?` doit afficher
`0` juste après `make docker-test`. Toute autre valeur indique un échec de
construction, de migration, de healthcheck ou de test.

Pour limiter exactement le même parcours au volet Avatar :

```bash
make docker-test-avatar
```

Les tests unitaires restent volontairement exécutables sans Docker.

Pour une exécution Panther directement sur le poste, installer d’abord le
driver correspondant au navigateur présent :

```bash
make test-browser-install
make test-clothes-e2e
make test-avatar-e2e
```

Le dossier `drivers/` contient un binaire propre à la machine et n’est donc pas
versionné.

## Diagnostic étape par étape

```bash
make docker-test-up
make docker-test-prepare
make docker-test-run
make docker-test-down
```

`docker-test-down` ne supprime pas le volume MySQL. Pour repartir d’une base
vide, l’opération destructive doit être demandée explicitement avec
`docker compose --profile test down --volumes`.
