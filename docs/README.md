# Documentation technique

La documentation est organisée par mécanisme d’exécution. Les workflows
décrivent les cycles métier et leurs gardes. Les workers décrivent les processus
à maintenir en production.

## Tests

- [Stratégie générale, erreurs et cas limites](tests/README.md)
- [Couverture Avatar](tests/avatar.md)
- [Couverture vêtements](tests/clothes.md)
- [Exécution des tests dans Docker](tests/docker.md)

## Workflows

- [Vue d’ensemble des workflows](workflows/README.md)
- [Publication des vêtements](workflows/clothes-publication.md)
- [Renommage des avatars](workflows/avatar-rename.md)
- [Cycle des commandes](workflows/orders.md)

## Workers

- [Vue d’ensemble des workers](workers/README.md)
- [Worker de renommage des avatars](workers/avatar-rename.md)
- [Worker de déformation des images](workers/image-deformation.md)

## Règle de maintenance

Toute modification d’un état, d’une transition, d’une garde, d’un message
Messenger, d’un schedule ou d’une règle testée doit être répercutée dans le
document correspondant.
