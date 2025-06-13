# 🕒 Credit Reminder

**Credit Reminder** est un module Thelia qui permet d'envoyer automatiquement un e-mail de rappel aux clients avant l'expiration de leurs crédits fidélité.

---

## 🚀 Installation

Clonez le dépôt dans le dossier `local/modules` de votre installation Thelia :

```
cd local/modules
git clone https://github.com/thelia-modules/CreditReminder
```
Vous pouvez également télécharger le module en ZIP et le décompresser le contenue dans le dossier :
```local/modules/CreditReminder/```

Ensuite, activez simplement le module depuis le back-office de Thelia.

## 🛠️ Fonctionnement

Le module interroge la base de données pour :

Identifier les clients dont les crédits arrivent à expiration.
Vérifier qu’un e-mail de rappel n’a pas déjà été envoyé récemment.
Déclencher automatiquement l’envoi d’un e-mail de rappel.
Une fois les e-mails envoyés, vous pouvez consulter les clients notifiés dans l'onglet Credit Reminder du back-office.

## ⏱️ Utilisation en ligne de commande

Pour automatiser l’envoi des rappels, une commande CLI est disponible. Elle peut être utilisée avec un cron job :

```php Thelia credit:send-reminders```

N'hésitez pas à contribuer ou à ouvrir une issue si vous avez des suggestions ou des problèmes !