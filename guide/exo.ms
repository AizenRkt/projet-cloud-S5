Exercices supplémentaires
Créer une route pour afficher une catégorie spécifique par son slug
Ajouter une colonne 
Créer un controller 
color à la table categories via une nouvelle migration
CategoryController avec la méthode index()
Modifier le seeder pour ajouter 3 catégories supplémentaires


php artisan make:migration add_color_to_categories_table --table=categories
php artisan migrate


php artisan make:controller CategoryController

php artisan db:seed --class=CategorySeeder

php artisan migrate:fresh --seed
