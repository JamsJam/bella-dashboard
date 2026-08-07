# Pourquoi les tests vêtements existent

Le vêtement commercial est porté principalement par `ClothesVariant`. Les tests
protègent donc la complétude, la publication, la disponibilité publique et la
création d’une variante par taille.

## Tests unitaires

### Complétude et publication

`ClotheCompletenessCheckerTest` prouve que description, méta-description et
stock ne bloquent pas la publication. Il vérifie en revanche qu’au moins une
image existe dans chaque groupe de variantes partageant une couleur.

**Pourquoi :** éviter de réintroduire un ancien critère bloquant ou de publier
une couleur sans image.

**Cas limite :** un autre groupe possède une image, mais pas le groupe évalué.
**Limite :** qualité, format et dimensions relèvent du validateur d’upload.

### Ordre des statuts

`ClotheStatusTest` fixe l’ordre utilisé pour déterminer le statut le plus avancé
d’un vêtement à partir de ses variantes.

**Pourquoi :** l’index affiche un statut agrégé ; changer cet ordre modifierait
silencieusement le badge et le tri. **Limite :** son rendu visuel n’est pas testé.

### Création des variantes

`ClotheVariantFactoryTest` vérifie une variante par taille, le partage du
vêtement, de la couleur, des descriptions, images et slug, ainsi que le nom
combinant vêtement, couleur et taille.

**Pourquoi :** une création partielle peut laisser plusieurs tailles
incohérentes ou casser les URL communes à une couleur.

**Cas limite :** plusieurs tailles dans une soumission. **Limites :** doublons de
taille et échec d’écriture d’image devront être ajoutés si leur traitement évolue.

`ClotheNameGuardTest` garantit également que la normalisation compacte les
espaces sans tronquer le nom au premier mot ni perdre sa casse.

**Pourquoi :** le nom complet est une propriété partagée par les variants et
sert à former leur nom ainsi que leur slug ; une troncature crée aussi de faux
doublons entre des vêtements commençant par le même mot.

`ClotheVariantColorGuardTest` vérifie qu’un groupe peut conserver sa couleur ou
adopter une couleur encore absente du vêtement. Il refuse une couleur déjà
utilisée par un autre groupe, y compris lorsque sa casse diffère.

**Pourquoi :** modifier la couleur ne doit jamais fusionner silencieusement deux
groupes de variantes ni produire des noms, slugs et SKU concurrents.

**Cas limite :** `Rouge` et `rouge` représentent la même couleur pour cette
validation. **Limite :** la création et la persistance de la couleur relèvent du
parcours applicatif.

### Disponibilité publique

`ClothesVariantAvailabilityTest` impose qu’une variante soit disponible
uniquement si elle est `En ligne` avec du stock. L’ancien statut `isOnline` du
vêtement parent ne doit plus intervenir.

**Pourquoi :** le workflow appartient à chaque variante ; consulter le parent
pourrait masquer une variante valide ou exposer une variante hors ligne.

**Cas limites :** stock nul et statut hors ligne. Le stock ne bloque pas
`Publiable`, mais continue de bloquer l’achat : les règles sont distinctes.

### Garde SEO et DTO public

`ClotheOnlineGuardTest` limite la validation SEO au groupe portant le slug
demandé. `VariantDetailDTOTest` garantit que les avis publics ne divulguent ni
identifiant client ni identifiant technique de requête.

**Pourquoi :** éviter un blocage causé par une autre couleur et protéger les
données internes de l’API. **Limite :** les droits HTTP de toutes les routes API
ne sont pas vérifiés par le test du DTO.

## Tests d’intégration

`ClothePublicationWorkflowConfigurationTest` charge le workflow réel et vérifie
toutes les transitions : publication, programmation, dépublication, archivage
et restauration.

`ClotheWorkflowServiceTest` programme réellement les variantes sélectionnées et
vérifie leur persistance. Il contrôle qu’une variante non sélectionnée reste
intacte et qu’un lot contenant un brouillon est refusé avant toute transition.

**Pourquoi :** protéger la cohérence entre YAML, service, gardes et Doctrine.

**Cas limites :** sélection partielle et lot publiable/brouillon avec atomicité.
**Limites :** programmations concurrentes et worker à l’instant exact de mise en
ligne ne sont pas simulés.

`ClotheColorDeletionServiceTest` supprime une couleur utilisée par deux
vêtements et vérifie la suppression de ses variants et de leurs images. Un des
vêtements possède une seconde couleur : il doit être conservé avec son variant,
tandis que le vêtement devenu vide doit disparaître.

**Pourquoi :** la cascade Doctrine ne connaît pas les fichiers physiques. Ce
test protège donc simultanément les associations, les parents devenus vides et
le nettoyage de `public/images/upload/clothes`.

**Cas limite :** une autre couleur du même vêtement ne doit jamais être touchée.
Les chemins extérieurs au répertoire d’upload sont volontairement ignorés.

## Test applicatif

`ClothesVariantCreationTest` connecte un administrateur, ouvre le formulaire,
inspecte le CSRF, puis envoie une couleur existante, deux tailles et un PNG. Il
vérifie une variante par taille, le nom, le slug partagé, les propriétés, l’image,
le passage à `Publiable` et la redirection finale.

**Pourquoi :** réunir sécurité, Form, validation, upload, factory, Doctrine et
workflow comme dans le parcours réel.

**Cas limites et erreurs :** CSRF absent, fichier temporaire impossible à créer
et refus d’une base sans suffixe `_test`. **Limites :** nouvelle couleur et
erreurs de formulaire ne sont pas encore couvertes.

`ClothesColorManagementTest` vérifie aussi que l’index expose l’action Turbo,
que la modale affiche le nombre de vêtements reliés et qu’une suppression avec
un jeton CSRF valide renvoie un Turbo Stream puis retire la couleur.

**Pourquoi :** couvrir la jonction entre l’onglet d’action, la route, Twig,
Turbo, la sécurité CSRF et le service de suppression.

## Tests end-to-end

`ClothesLifecycleTest` emploie un véritable Chromium et découpe le cycle en
trois scénarios afin qu’un échec désigne une opération précise.

### Création complète du vêtement

Le navigateur ouvre `/clothes/add`, renseigne le nom, le prix, la collection et
une couleur existante, sélectionne deux tailles au moyen des contrôles stylisés,
ajoute les descriptions et téléverse un vrai PNG. Le test attend la fin de la
navigation avant d’interroger MySQL.

**Pourquoi :** vérifier ensemble le thème du formulaire, Stimulus, l’upload, la
factory, Doctrine et la réconciliation du workflow. Il exige deux variantes,
un slug commun au groupe de couleur, une image et le statut `Publiable`.

**Erreurs humaines :** formulaire inaccessible, contrôleur Stimulus absent,
vêtement non persisté, variante de taille manquante, slug divergent, image
perdue ou variante restant en brouillon.

**Cas limite :** un seul groupe de couleur produit plusieurs tailles partageant
les mêmes propriétés. **Limite :** la création d’une couleur inexistante reste
à couvrir dans un scénario distinct.

### Ajout et modification d’un variant

Depuis la fiche, Panther active l’onglet Variantes, ouvre la modale Turbo des
tailles, ajoute M avec un stock de 7 puis rouvre la modale et remplace ce stock
par 11.

**Pourquoi :** contrôler le rendu asynchrone de la modale, la création d’une
nouvelle variante en `Brouillon` et la persistance d’une modification existante.

**Erreurs humaines :** modale absente, champ de stock non créé, mauvais stock
initial, statut initial incorrect ou modification non enregistrée.

### Suppression d’un variant

Le navigateur décoche M. Il refuse d’abord la confirmation JavaScript et
vérifie que M existe encore. Il recommence, accepte, attend la disparition de la
modale Turbo, puis vérifie que M a disparu tandis que S demeure.

**Pourquoi :** protéger l’utilisateur d’une suppression accidentelle et éviter
qu’une suppression ciblée affecte tout le groupe de couleur.

**Cas limites :** annulation explicite et conservation d’au moins une autre
taille. **Limite :** la suppression de la dernière variante, si elle devient
autorisée fonctionnellement, devra posséder son propre test.
