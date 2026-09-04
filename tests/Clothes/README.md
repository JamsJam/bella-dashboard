# Tests du volet vêtements

Ce dossier regroupe les tests portant le groupe PHPUnit `clothes`. La séparation
suit les définitions de la documentation Symfony.

La raison de chaque test, les erreurs et cas limites couverts et les limites
connues sont détaillées dans
[docs/tests/clothes.md](../../docs/tests/clothes.md).

## Unit

Les tests de `Unit/` étendent `PHPUnit\Framework\TestCase`. Ils ne démarrent ni
le kernel Symfony ni MySQL. Ils vérifient notamment :

- la complétude nécessaire à la publication ;
- la disponibilité selon le stock et le statut ;
- les règles SEO d’un groupe de couleur ;
- l’ordre de progression des statuts ;
- la construction des noms, slugs et propriétés communes des variantes ;
- l’absence de données privées dans le DTO public.

```bash
make test-clothes-unit
```

## Integration

Les tests de `Integration/` étendent `KernelTestCase`. Ils démarrent le vrai
conteneur Symfony et vérifient ensemble Workflow, services et Doctrine :

- présence de toutes les transitions configurées ;
- programmation de plusieurs variantes publiables ;
- programmation limitée à la sélection ;
- refus atomique d’une sélection contenant un brouillon.

```bash
make test-clothes-integration
```

## Application

Les tests de `Application/` étendent `WebTestCase`. Ils utilisent BrowserKit,
DomCrawler et CssSelector pour parcourir l’application comme un navigateur sans
exécuter JavaScript.

Le scénario actuel vérifie `/clothes/variants/add` de bout en bout :

1. authentification d’un administrateur ;
2. réponse HTTP et présence du formulaire dans le DOM ;
3. présence du CSRF, du choix de couleur et des tailles ;
4. envoi multipart avec une image ;
5. création d’une variante par taille dans MySQL ;
6. slug commun à la couleur ;
7. passage automatique des variantes complètes à `Publiable` ;
8. redirection vers la fiche du vêtement.

```bash
make test-clothes-application
```

BrowserKit n’exécute pas Stimulus. Les interactions JavaScript réelles sont
couvertes au niveau suivant.

## End-to-end

Les tests de `EndToEnd/` pilotent Chromium avec Panther. Ils vérifient :

1. la création d’un vêtement complet avec couleur, deux tailles et image ;
2. la génération d’une variante par taille, du slug commun et du statut
   `Publiable` ;
3. l’ajout d’une taille depuis la modale Turbo et son stock initial ;
4. la modification du stock d’une variante existante ;
5. l’annulation d’une suppression dans la confirmation JavaScript ;
6. la suppression confirmée d’une variante sans toucher aux autres.

```bash
make test-clothes-e2e
```

Ces scénarios nécessitent MySQL, Chromium et un serveur HTTP. Le parcours
reproductible recommandé est `make docker-test`.

## Comprendre un échec

Les assertions métier fournissent volontairement un message lisible :

- `Blocage :` indique la fonctionnalité qui ne respecte plus son contrat ;
- `Sécurité :` indique qu’un test risque d’utiliser une base autre que la base
  `_test` ;
- `Blocage de préparation :` indique que le scénario n’a pas pu fabriquer ses
  données temporaires.

Pour afficher tous les détails PHPUnit :

```bash
php bin/phpunit --group clothes --display-all-issues
```
