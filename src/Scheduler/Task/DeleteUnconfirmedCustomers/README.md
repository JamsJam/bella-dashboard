# Suppression des clients non confirmés

Cette tâche planifiée supprime les comptes clients dont l'inscription n'a pas été confirmée avant l'expiration de leur code de vérification.

## Organisation

La tâche est découpée en trois responsabilités :

- `DeleteUnconfirmedCustomersScheduleProvider` définit la fréquence d'exécution ;
- `DeleteUnconfirmedCustomersMessage` représente l'intention de lancer le nettoyage ;
- `DeleteUnconfirmedCustomersHandler` reçoit le message et délègue le traitement au purger métier.

Le flux d'exécution est le suivant :

```text
Scheduler, toutes les 5 minutes
    -> DeleteUnconfirmedCustomersMessage
    -> DeleteUnconfirmedCustomersHandler
    -> ExpiredUnconfirmedCustomersPurger::purge()
    -> suppression DQL groupée
```

## Provider

Le provider appartient au planning `default` grâce à `#[AsSchedule('default')]`. Il crée un message récurrent toutes les cinq minutes.

Le planning est stateful afin de conserver son état entre les redémarrages. `processOnlyLastMissedRun(true)` évite d'exécuter toutes les occurrences manquées lorsqu'un worker redémarre après une interruption.

## Message

Le message est volontairement vide. Il décrit l'intention suivante :

> Rechercher et supprimer les comptes clients non confirmés dont le délai de confirmation a expiré.

Les clients concernés ne sont pas connus lors de la construction du planning. Ils sont déterminés au moment du traitement.

Le message ne contient aucun repository ni service afin de rester simple et sérialisable.

## Handler

Le handler est découvert par Messenger grâce à `#[AsMessageHandler]`. Il reçoit uniquement le message et appelle `ExpiredUnconfirmedCustomersPurger`.

La logique de sélection et de suppression reste dans le purger afin que la tâche planifiée ne porte pas la logique métier.

La suppression est groupée dans une seule requête DQL. Elle ne charge donc pas toutes les entités en mémoire et une nouvelle exécution reste sans effet lorsque plus aucun compte n'est expiré.

## Exécution

Le planning peut être contrôlé avec :

```bash
php bin/console debug:scheduler
```

Le worker nécessaire à son exécution est :

```bash
APP_ENV=prod APP_DEBUG=0 php bin/console messenger:consume scheduler_default
```

En production, ce worker doit être maintenu actif par Supervisor, systemd ou un mécanisme équivalent.

## Vérifications utiles

Le message et son handler peuvent être vérifiés avec :

```bash
php bin/console debug:messenger
```

Le traitement est considéré comme réussi même lorsqu'aucun compte ne correspond aux conditions de suppression. Une exception non interceptée dans le handler permet à Messenger d'appliquer sa stratégie de retry.
