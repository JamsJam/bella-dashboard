# BellaGP Dashboard — Backend

Back-office et API de BellaGP construits avec PHP 8.2 et Symfony 7.4.
L’application gère notamment le catalogue et ses variantes, les collections,
les avatars, les commandes, les livraisons, les clients et la configuration
éditoriale.

## Stack technique

- Symfony 7.4 et Doctrine ORM ;
- MySQL 8 ;
- Twig, AssetMapper, Stimulus, Turbo et Sass ;
- Symfony Workflow, Messenger et Scheduler ;
- authentification JWT ;
- Stripe pour les paiements et les webhooks.

## Get started

### Prérequis

- PHP 8.2 ou supérieur avec les extensions requises par Symfony et MySQL ;
- Composer ;
- MySQL 8 ;
- Symfony CLI.

### Installation

1. Installer les dépendances :

   ```bash
   composer install
   ```

2. Créer un fichier `.env.local` et renseigner les valeurs propres à la machine,
   notamment `DATABASE_URL`, `MESSENGER_TRANSPORT_DSN`, `MAILER_DSN`, les
   paramètres JWT, les secrets Stripe et l’URL de l’application front.

   Exemple de connexion MySQL locale :

   ```dotenv
   DATABASE_URL="mysql://app:password@127.0.0.1:3306/bellagp?serverVersion=8.0.32&charset=utf8mb4"
   ```

3. Initialiser la base de données :

   ```bash
   php bin/console doctrine:database:create --if-not-exists
   php bin/console doctrine:migrations:migrate --no-interaction
   ```

4. Générer les clés JWT si nécessaire :

   ```bash
   php bin/console lexik:jwt:generate-keypair --skip-if-exists
   ```

### Lancer l’environnement de développement

```bash
make dev
```

Cette commande :

1. exécute `composer install` ;
2. démarre le serveur Symfony en arrière-plan avec `symfony serve:start -d` ;
3. lance `symfony console sass:build --watch` au premier plan.

Utiliser `Ctrl+C` pour arrêter le watcher Sass. Le serveur Symfony reste en
arrière-plan et peut être arrêté avec :

```bash
symfony server:stop
```

## Development

### Organisation du projet

```text
assets/          Contrôleurs Stimulus et styles Sass
config/          Configuration Symfony, Workflow, Messenger et services
docs/            Documentation des workflows et workers
migrations/      Évolution du schéma Doctrine
public/          Point d’entrée HTTP et fichiers publics
src/Application/ Cas d’usage, services métier, gardes et handlers
src/Controller/  Contrôleurs du back-office et de l’API
src/Entity/      Entités Doctrine
src/Repository/  Requêtes de persistance
src/Scheduler/   Tâches récurrentes Symfony Scheduler
templates/       Interfaces Twig et fragments Turbo
tests/           Tests unitaires, fonctionnels et d’intégration
```

Le code métier doit être placé dans `src/Application` lorsque cela est
possible. Les contrôleurs restent responsables du protocole HTTP, de la
validation CSRF et de la présentation des réponses.

### Assets

Le projet utilise AssetMapper et SassBundle, sans étape Node obligatoire.

```bash
# Compilation ponctuelle des styles
php bin/console sass:build

# Surveillance des styles en développement
php bin/console sass:build --watch

# Compilation optimisée pour la production
php bin/console asset-map:compile
```

Stimulus gère les interactions légères. Turbo est utilisé pour les navigations,
les modales et les mises à jour partielles du back-office.

### Commandes métier

```bash
# Réévaluer la complétude et le statut de toutes les variantes
php bin/console app:clothes:reconcile-publication-status

# Traiter immédiatement les publications arrivées à échéance
php bin/console app:clothes:publish-scheduled
```

## Tests

### Préparer la base de test

Les tests utilisant Doctrine lisent `TEST_DATABASE_URL` dans `.env.test` ou
`.env.test.local`. Doctrine ajoute automatiquement le suffixe `_test`. Cette
base doit rester distincte des bases de développement et de production.

```bash
php bin/console --env=test doctrine:database:create --if-not-exists
php bin/console --env=test doctrine:migrations:migrate --no-interaction
```

### Exécuter les tests

```bash
# Suite complète, ordonnée par niveau
make test

# Tous les volets pour un niveau précis
make test-unit
make test-integration
make test-application
make test-e2e

# Tout le volet vêtements
make test-clothes

# Un niveau précis du volet vêtements
make test-clothes-unit
make test-clothes-integration
make test-clothes-application
make test-clothes-e2e

# Le groupe PHPUnit vêtements, indépendamment de son emplacement
php bin/phpunit --group clothes

# Tout le volet Avatar, dans l’ordre unitaire, intégration, applicatif
make test-avatar

# Un niveau précis du volet Avatar
make test-avatar-unit
make test-avatar-integration
make test-avatar-application
make test-avatar-e2e

# Tous les volets depuis l’intégration dans Docker
make docker-test

# Même parcours limité à Avatar
make docker-test-avatar

# Arrêter ensuite l’environnement Docker de test
make docker-test-down

# Le groupe PHPUnit Avatar, indépendamment de son emplacement
php bin/phpunit --group avatar
```

Les tests sont répartis entre :

- `tests/Clothes/Unit` pour les règles vêtements isolées, sans kernel ni base ;
- `tests/Clothes/Integration` pour les services réels, Doctrine et Workflow ;
- `tests/Clothes/Application` pour les parcours HTTP BrowserKit et DomCrawler ;
- `tests/Clothes/EndToEnd` pour la création du vêtement et le cycle des variants
  dans Chromium avec Panther ;
- `tests/Avatar/Unit` pour les règles Avatar isolées ;
- `tests/Avatar/Integration` pour Workflow, Doctrine, Messenger et les fichiers ;
- `tests/Avatar/Application` pour le catalogue HTTP avec BrowserKit et DomCrawler ;
- `tests/Avatar/EndToEnd` pour Chrome, JavaScript, Turbo et Stimulus avec Panther ;
- `tests/Application` pour les règles métier unitaires ;
- `tests/Controller` pour les parcours HTTP ;
- `tests/Integration` pour l’intégration réelle entre le conteneur Symfony,
  Doctrine, les services et les workflows ;
- `tests/Entity` et `tests/ApiResource` pour le modèle et l’API.

Tous les tests du volet vêtements portent le groupe PHPUnit `clothes`. Chaque
assertion importante possède un message en français commençant par `Blocage :`
ou `Sécurité :` pour identifier directement la fonctionnalité défaillante.

Les tests du volet Avatar portent de la même manière le groupe `avatar` et sont
exécutés dans l’ordre unitaire, intégration, applicatif, puis end-to-end.

Les tests d’intégration démarrent le kernel et récupèrent les vrais services
avec `static::getContainer()`. Les tests applicatifs utilisent `WebTestCase`,
BrowserKit, DomCrawler et les sélecteurs CSS pour vérifier la route, le DOM, le
CSRF, l’upload, la persistance et la redirection.

Les tests end-to-end utilisent Panther et un véritable Chromium. Avec Docker,
le profil `test` démarre MySQL, Symfony et Selenium puis exécute les tests depuis
une image PHP 8.3 dédiée. La base est créée et migrée automatiquement par
`make docker-test`, ou `make docker-test-avatar` pour limiter le parcours à
Avatar.

La documentation explique le pourquoi de chaque famille de tests, les erreurs
et cas limites couverts, ainsi que les limites encore connues :

- [stratégie générale des tests](docs/tests/README.md) ;
- [couverture Avatar](docs/tests/avatar.md) ;
- [couverture vêtements](docs/tests/clothes.md).

Les fichiers `tests/Avatar/README.md` et `tests/Clothes/README.md` restent des
points d’entrée courts depuis les dossiers de tests.

## Code quality

### Linters Symfony

```bash
make lint
```

La cible exécute les contrôles du conteneur, des traductions, des templates Twig,
des fichiers XLIFF et de la configuration YAML. Ils peuvent être lancés
séparément :

```bash
make lint-container
make lint-translations
make lint-twig
make lint-xliff
make lint-yaml
```

Contrôles complémentaires :

```bash
php bin/console doctrine:schema:validate
php bin/console sass:build
```

### PHP-CS-Fixer et PHP_CodeSniffer

Les règles sont définies dans `.php-cs-fixer.dist.php` et `phpcs.xml.dist`. Les
deux outils analysent `config/`, `migrations/`, `src/` et `tests/`.

Le dry-run est le mode par défaut. Il affiche les problèmes sans modifier les
fichiers :

```bash
make cs-scan

# Forme explicite
make cs-scan DR=1
```

Pour appliquer les corrections automatiques puis vérifier le résultat :

```bash
make cs-scan DR=0
```

La commande retourne un code non nul lorsqu’un problème est détecté ou ne peut
pas être corrigé automatiquement.

## Workflows

Trois state machines Symfony sont déclarées dans
`config/packages/workflow.yaml` :

- `clothe_publication` pour la publication de chaque variante de vêtement ;
- `avatar_rename` pour le renommage asynchrone des images d’avatar ;
- `order` pour le traitement et la livraison des commandes.

Les états, transitions, gardes, erreurs, schémas Mermaid et fichiers concernés
sont décrits dans [la documentation des workflows](docs/README.md#workflows).

Le workflow `avatar_rename` possède actuellement une incohérence connue entre
son marking store `publicationStatus` et la propriété `status` de `AvatarTemp`.
Le détail se trouve dans
[sa documentation](docs/workflows/avatar-rename.md#rôle).

## Workers

Initialiser les transports Messenger si nécessaire :

```bash
php bin/console messenger:setup-transports
```

Lancer les consommateurs selon les besoins :

```bash
# E-mails, Stripe et messages généraux
php bin/console messenger:consume async

# Renommage sérialisé des avatars
php bin/console messenger:consume avatar_rename

# Déformation sérialisée des images
php bin/console messenger:consume image_deformation

# Nettoyage périodique des déformations
php bin/console messenger:consume scheduler_image_deformation_cleanup

# Publication programmée des variantes de vêtements
php bin/console messenger:consume scheduler_clothes_publication
```

Les transports `avatar_rename` et `image_deformation` doivent conserver un seul
consommateur afin de garantir la sérialisation attendue. En production, leur
supervision doit être assurée par Supervisor, systemd ou l’orchestrateur.

Voir [la documentation des workers](docs/README.md#workers) pour le détail.

## Database

Après une modification du mapping Doctrine :

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

Toujours relire une migration générée avant de l’exécuter, particulièrement lors
d’un changement d’enum ou de la suppression d’une colonne. Les données
existantes doivent être normalisées avant leur hydratation par Doctrine.

## Production

Une livraison doit au minimum installer les dépendances, appliquer les
migrations, compiler les assets, réchauffer le cache et redémarrer les workers.

```bash
APP_ENV=prod APP_DEBUG=0 composer install --no-dev --optimize-autoloader
APP_ENV=prod APP_DEBUG=0 php bin/console doctrine:migrations:migrate --no-interaction
APP_ENV=prod APP_DEBUG=0 php bin/console asset-map:compile
APP_ENV=prod APP_DEBUG=0 php bin/console cache:clear
```

Les secrets doivent être fournis par l’environnement de déploiement ou Symfony
Secrets. Ils ne doivent jamais être ajoutés au dépôt.

## Documentation

L’index complet se trouve dans [docs/README.md](docs/README.md).

- [Workflow de publication des vêtements](docs/workflows/clothes-publication.md)
- [Workflow de renommage des avatars](docs/workflows/avatar-rename.md)
- [Workflow des commandes](docs/workflows/orders.md)
- [Worker de renommage des avatars](docs/workers/avatar-rename.md)
- [Worker de déformation des images](docs/workers/image-deformation.md)
