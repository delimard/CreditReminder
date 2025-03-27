# Credit Reminder

Ce module est utilisé pour envoyer un e-mail de rappel aux clients avant l'expiration de leur crédit.

## Installation

```  
$ cd local/modules  
$ git clone https://github.com/thelia-modules/CreditReminder  
```  
Vous pouvez également télécharger le zip depuis Github.

Ensuite, il vous suffit d'activer le module dans votre back-office.

## Cas d'utilisation

Dans le back-office, vous trouverez un onglet « Credit Reminder » qui répertorie les clients dont l'e-mail a été envoyé.

Ce module sert à envoyer par email des rappels aux clients dont les crédits fidélité vont bientôt expirer. Il interroge la base de données pour identifier les comptes concernés, vérifie que suffisamment de temps s'est écoulé depuis le dernier rappel, puis déclenche un événement qui envoie l'email de rappel.

La commande associée peut être aussi utiliser dans le cas d'un cron : `php Thelia credit:send-reminders`
  