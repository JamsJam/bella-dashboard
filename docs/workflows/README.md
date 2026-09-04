# Workflows

Les workflows Symfony sont déclarés dans `config/packages/workflow.yaml`. Chaque
document décrit les états en langage naturel, les transitions, les gardes, les
codes de blocage, les erreurs et les fichiers qui réalisent les effets métier.

- [Publication des vêtements](clothes-publication.md)
- [Renommage des avatars](avatar-rename.md)
- [Cycle des commandes](orders.md)

Attention : le workflow avatar présente actuellement un mauvais nom de propriété
dans son marking store. Le détail et l’erreur reproductible figurent dans sa
documentation.
