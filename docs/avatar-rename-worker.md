# Worker de renommage des avatars

Les messages `RenameAvatarMessage` utilisent exclusivement le transport Messenger
`avatar_rename`. La sérialisation des renommages dépend d'un consommateur unique :

```bash
APP_ENV=prod APP_DEBUG=0 php bin/console messenger:consume avatar_rename
```

La configuration de production (Supervisor, systemd ou orchestrateur) doit imposer
un seul processus et une seule réplique pour cette commande. Ajouter un second
consommateur supprimerait la garantie d'absence de renommages simultanés.

Les autres messages asynchrones continuent d'utiliser le transport `async`.
