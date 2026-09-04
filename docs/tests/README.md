# Documentation des tests

Ce dossier explique pourquoi les tests existent et quels risques ils empêchent
de réintroduire. Un test matérialise une règle métier, une erreur attendue ou
une frontière du système, pas seulement une valeur de retour.

## Ordre des niveaux

1. **Unitaire** : vérifie une règle isolée, sans kernel ni base.
2. **Intégration** : vérifie l’assemblage réel de Symfony, Workflow, Doctrine,
   Messenger ou du système de fichiers.
3. **Applicatif** : traverse une route HTTP avec `WebTestCase`, BrowserKit et
   DomCrawler comme le ferait un utilisateur.
4. **End-to-end** : pilote Chromium avec Panther et exécute réellement
   JavaScript, Turbo et Stimulus.

Cet ordre permet de localiser une panne : règle métier, configuration des
services, puis parcours visible par l’utilisateur.

## Conventions d’erreur

- `Blocage :` désigne le comportement fonctionnel qui ne respecte plus son
  contrat ;
- `Sécurité :` interrompt un scénario qui viserait une base autre que `_test` ;
- `Blocage de préparation :` indique que les données ou fichiers nécessaires
  au scénario n’ont pas pu être créés.

## Couverture détaillée

- [Tests Avatar](avatar.md)
- [Tests vêtements](clothes.md)

Chaque fiche indique les comportements couverts, les erreurs et cas limites,
ainsi que les limites connues. Une limite documentée n’est pas une garantie :
elle indique où ajouter un futur test lorsque la fonctionnalité évolue.

## Commandes

```bash
# Tous les niveaux, dans l’ordre
make test

# Tous les volets pour un niveau
make test-unit
make test-integration
make test-application
make test-e2e

# Volets fonctionnels, dans l’ordre unitaire → intégration → applicatif → E2E
make test-avatar
make test-clothes

# Tous les volets depuis l’intégration dans Docker, avec Selenium/Chromium
make docker-test

# Parcours Docker limité à Avatar
make docker-test-avatar
make docker-test-down

# Exécution indépendante de l’emplacement des fichiers
php bin/phpunit --group avatar --display-all-issues
php bin/phpunit --group clothes --display-all-issues
```
