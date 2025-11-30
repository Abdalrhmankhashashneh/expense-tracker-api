<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultCategories = [
            [
                'name' => [
                    'en' => 'Food',
                    'ar' => 'طعام'
                ],
                'icon' => 'restaurant',
                'color' => '#FF6B6B',
            ],
            [
                'name' => [
                    'en' => 'Transport',
                    'ar' => 'مواصلات'
                ],
                'icon' => 'directions_car',
                'color' => '#4ECDC4',
            ],
            [
                'name' => [
                    'en' => 'Bills',
                    'ar' => 'فواتير'
                ],
                'icon' => 'receipt',
                'color' => '#45B7D1',
            ],
            [
                'name' => [
                    'en' => 'Shopping',
                    'ar' => 'تسوق'
                ],
                'icon' => 'shopping_bag',
                'color' => '#96CEB4',
            ],
            [
                'name' => [
                    'en' => 'Entertainment',
                    'ar' => 'ترفيه'
                ],
                'icon' => 'movie',
                'color' => '#FFEAA7',
            ],
            [
                'name' => [
                    'en' => 'Health',
                    'ar' => 'صحة'
                ],
                'icon' => 'local_hospital',
                'color' => '#DFE6E9',
            ],
            [
                'name' => [
                    'en' => 'Education',
                    'ar' => 'تعليم'
                ],
                'icon' => 'school',
                'color' => '#A29BFE',
            ],
            [
                'name' => [
                    'en' => 'Other',
                    'ar' => 'أخرى'
                ],
                'icon' => 'category',
                'color' => '#907B60',
            ],
        ];

        foreach ($defaultCategories as $category) {
            Category::create([
                'user_id' => null,
                'name' => $category['name'],
                'icon' => $category['icon'],
                'color' => $category['color'],
                'is_default' => true,
            ]);
        }
    }
}
