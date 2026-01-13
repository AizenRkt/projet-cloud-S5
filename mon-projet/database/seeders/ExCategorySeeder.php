<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class ExCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Technologie',
                'slug' => 'technologie',
                'description' => 'Articles sur la technologie et l\'innovation',
                'is_active' => true,
                'color' => '#ff0000'
            ],
            [
                'name' => 'Science',
                'slug' => 'science',
                'description' => 'Découvertes scientifiques et recherches',
                'is_active' => true,
                'color' => '#00ff00'
            ],
            [
                'name' => 'Sport',
                'slug' => 'sport',
                'description' => 'Actualités sportives',
                'is_active' => true,
                'color' => '#0000ff'
            ],
            [
                'name' => 'Culture',
                'slug' => 'culture',
                'description' => 'Arts, musique et culture',
                'is_active' => true,
                'color' => '#ff9900'
            ],
            [
                'name' => 'Économie',
                'slug' => 'economie',
                'description' => 'Actualités économiques et financières',
                'is_active' => true,
                'color' => '#33cc33'
            ],
            // 3 nouvelles catégories
            [
                'name' => 'Santé',
                'slug' => 'sante',
                'description' => 'Articles sur la santé et le bien-être',
                'is_active' => true,
                'color' => '#ff66cc'
            ],
            [
                'name' => 'Voyage',
                'slug' => 'voyage',
                'description' => 'Destinations et conseils de voyage',
                'is_active' => true,
                'color' => '#6699ff'
            ],
            [
                'name' => 'Cuisine',
                'slug' => 'cuisine',
                'description' => 'Recettes et astuces culinaires',
                'is_active' => true,
                'color' => '#ffcc33'
            ]
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }

    }
}
