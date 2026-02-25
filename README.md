# MyCustomView

Plugin GLPI de personnalisation de la page d'accueil (central) par groupes. Il permet à chaque utilisateur autorisé de choisir quels groupes suivre et quels tableaux afficher (tickets à traiter, en cours, en attente, observés, tâches), dans une vue adaptée à son activité.

Le plugin sert à remplacer une page d'accueil trop générique par une vue opérationnelle orientée équipe.

## Ce que voit l'utilisateur

- Un onglet / une vue personnalisée sur le central GLPI.
- Une préférence `Vue groupe(s)` pour choisir ses groupes et les tableaux à afficher.
- Une page central plus lisible pour le suivi quotidien.

## Fonctionnement (parcours type)

1. L'administrateur règle la limite de groupes sélectionnables (`max_filters`).
2. L'utilisateur ouvre ses préférences (`Vue groupe(s)`).
3. L'utilisateur sélectionne un ou plusieurs groupes.
4. L'utilisateur choisit quels tableaux afficher (tickets à traiter, en cours, en attente, observés, tâches, etc.).
5. L'utilisateur retourne sur la page centrale et obtient sa vue personnalisée.

## Configuration plugin (ce que chaque zone active)

### Configuration administrateur

- `max_filters`: nombre maximal de groupes que l'utilisateur peut sélectionner.

Pourquoi c'est utile:
- éviter une page centrale trop chargée
- limiter le coût d'affichage si un utilisateur sélectionne beaucoup de groupes
- cadrer l'usage selon votre organisation (petite équipe vs centre de services)

### Préférences utilisateur (`Vue groupe(s)`)

L'utilisateur peut généralement choisir:
- `groups_id[]`: les groupes à suivre
- `Tickets_to_be_processed`: afficher les tickets à traiter
- `Ticket_tasks_to_be_addressed`: afficher les tâches à traiter
- `Pending_tickets`: afficher les tickets en attente
- `Current_tickets`: afficher les tickets en cours
- `Observed_tickets`: afficher les tickets observés/suivis
- `full_view`: mode d'affichage plus détaillé (nom/commentaire de groupe selon la logique du plugin)

## Prérequis

- GLPI 11.x (selon version installée)
- PHP compatible GLPI
- Pas de dépendance externe obligatoire

## Droits / profils

- Le plugin nécessite le droit d'usage `plugin_mycustomview_use`.
- La configuration globale (admin) est réservée aux profils autorisés.
- Les données affichées restent filtrées par les droits GLPI réels de l'utilisateur.

## Architecture (résumé court)

- Une configuration globale fixe la limite de filtres.
- Des préférences utilisateur stockent la sélection de groupes et tableaux.
- L'affichage du central agrège les tickets/tâches selon ces préférences et les droits GLPI.

## Vérifications rapides après mise à jour

- Sauvegarder `max_filters` côté admin.
- Enregistrer des préférences utilisateur avec 1..N groupes.
- Vérifier l'affichage du central avec tableaux activés/désactivés.
- Vérifier qu'un utilisateur sans droit plugin n'accède pas à la vue.
