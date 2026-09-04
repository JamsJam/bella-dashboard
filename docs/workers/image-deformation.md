# Worker de déformation d’images

Le traitement utilise une file dédiée. Un seul consommateur doit être lancé afin de sérialiser les transformations :

```bash
APP_ENV=prod APP_DEBUG=0 php bin/console messenger:consume image_deformation
```

Le nettoyage quotidien nécessite aussi le transport Scheduler :

```bash
APP_ENV=prod APP_DEBUG=0 php bin/console messenger:consume scheduler_image_deformation_cleanup
```

Les fichiers temporaires terminés ou échoués sont supprimés chaque jour à 06:00, heure de Paris. Les traitements actifs ne sont pas interrompus. Aucune table en base de données n’est utilisée.

Les messages `DeformImageMessage` sont routés vers `image_deformation` dans
`config/packages/messenger.yaml` et traités par
`src/MessageHandler/ImageDeformation/DeformImageMessageHandler.php`.
