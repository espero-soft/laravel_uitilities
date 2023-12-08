# Générateur d'Entités Laravel

Ce script en ligne de commande Laravel permet de générer des entités avec des migrations associées, en demandant à l'utilisateur les détails nécessaires pour créer ces entités.

## Utilisation

1. Assurez-vous d'avoir Laravel installé sur votre système.
2. Placez le fichier `GenerateEntityCommand.php` dans le répertoire `app/Console/Commands` de votre projet Laravel.
3. Exécutez la commande suivante pour générer une entité :

    ```bash
    php artisan generate:entity {nom_de_l_entite}
    ```

    Remplacez `{nom_de_l_entite}` par le nom de l'entité que vous souhaitez créer.

## Commandes disponibles

- `generate:entity {name}` : Génère une nouvelle entité avec le nom spécifié.

## Fonctionnalités

- Crée un modèle si celui-ci n'existe pas déjà.
- Demande à l'utilisateur les détails des champs et des relations.
- Génère automatiquement les fichiers de migration avec les champs spécifiés.
- Gère les relations OneToOne, OneToMany, ManyToOne et ManyToMany.

## Dépendances

Ce script utilise les fonctionnalités de base de Laravel et les classes de la Facade `Schema`.

## Auteur

Ce script a été développé par [Espero-soft Informatiques] pour faciliter la création d'entités dans un projet Laravel.

Si vous avez des questions ou des suggestions d'amélioration, n'hésitez pas à [me contacter](mailto:contact@mespero-soft.com).

