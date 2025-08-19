<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Beauty & Spa',
                'name_ar' => 'الجمال والسبا',
                'slug' => 'beauty-spa',
                'description' => 'Beauty salons, spas, and wellness centers',
                'description_ar' => 'صالونات التجميل والسبا ومراكز العافية',
                'icon' => 'spa',
                'color' => '#EC4899',
                'sort_order' => 1,
                'children' => [
                    [
                        'name' => 'Hair Salons',
                        'name_ar' => 'صالونات الشعر',
                        'slug' => 'hair-salons',
                        'icon' => 'scissors',
                        'sort_order' => 1,
                    ],
                    [
                        'name' => 'Nail Salons',
                        'name_ar' => 'صالونات الأظافر',
                        'slug' => 'nail-salons',
                        'icon' => 'hand',
                        'sort_order' => 2,
                    ],
                    [
                        'name' => 'Spas',
                        'name_ar' => 'السبا',
                        'slug' => 'spas',
                        'icon' => 'spa',
                        'sort_order' => 3,
                    ],
                    [
                        'name' => 'Massage Centers',
                        'name_ar' => 'مراكز التدليك',
                        'slug' => 'massage-centers',
                        'icon' => 'massage',
                        'sort_order' => 4,
                    ],
                ]
            ],
            [
                'name' => 'Fitness & Wellness',
                'name_ar' => 'اللياقة والعافية',
                'slug' => 'fitness-wellness',
                'description' => 'Gyms, yoga studios, and wellness centers',
                'description_ar' => 'الصالات الرياضية واستوديوهات اليوغا ومراكز العافية',
                'icon' => 'dumbbell',
                'color' => '#10B981',
                'sort_order' => 2,
                'children' => [
                    [
                        'name' => 'Gyms',
                        'name_ar' => 'الصالات الرياضية',
                        'slug' => 'gyms',
                        'icon' => 'dumbbell',
                        'sort_order' => 1,
                    ],
                    [
                        'name' => 'Yoga Studios',
                        'name_ar' => 'استوديوهات اليوغا',
                        'slug' => 'yoga-studios',
                        'icon' => 'yoga',
                        'sort_order' => 2,
                    ],
                    [
                        'name' => 'Pilates Studios',
                        'name_ar' => 'استوديوهات البيلاتس',
                        'slug' => 'pilates-studios',
                        'icon' => 'pilates',
                        'sort_order' => 3,
                    ],
                ]
            ],
            [
                'name' => 'Dining & Restaurants',
                'name_ar' => 'المطاعم والمأكولات',
                'slug' => 'dining-restaurants',
                'description' => 'Restaurants, cafes, and dining experiences',
                'description_ar' => 'المطاعم والمقاهي وتجارب الطعام',
                'icon' => 'restaurant',
                'color' => '#F59E0B',
                'sort_order' => 3,
                'children' => [
                    [
                        'name' => 'Fine Dining',
                        'name_ar' => 'المطاعم الفاخرة',
                        'slug' => 'fine-dining',
                        'icon' => 'utensils',
                        'sort_order' => 1,
                    ],
                    [
                        'name' => 'Cafes',
                        'name_ar' => 'المقاهي',
                        'slug' => 'cafes',
                        'icon' => 'coffee',
                        'sort_order' => 2,
                    ],
                    [
                        'name' => 'Casual Dining',
                        'name_ar' => 'المطاعم العادية',
                        'slug' => 'casual-dining',
                        'icon' => 'plate',
                        'sort_order' => 3,
                    ],
                ]
            ],
            [
                'name' => 'Shopping & Retail',
                'name_ar' => 'التسوق والبيع بالتجزئة',
                'slug' => 'shopping-retail',
                'description' => 'Fashion, accessories, and retail stores',
                'description_ar' => 'الأزياء والإكسسوارات ومتاجر التجزئة',
                'icon' => 'shopping-bag',
                'color' => '#8B5CF6',
                'sort_order' => 4,
                'children' => [
                    [
                        'name' => 'Fashion Boutiques',
                        'name_ar' => 'بوتيكات الأزياء',
                        'slug' => 'fashion-boutiques',
                        'icon' => 'shirt',
                        'sort_order' => 1,
                    ],
                    [
                        'name' => 'Jewelry Stores',
                        'name_ar' => 'متاجر المجوهرات',
                        'slug' => 'jewelry-stores',
                        'icon' => 'gem',
                        'sort_order' => 2,
                    ],
                    [
                        'name' => 'Accessories',
                        'name_ar' => 'الإكسسوارات',
                        'slug' => 'accessories',
                        'icon' => 'handbag',
                        'sort_order' => 3,
                    ],
                ]
            ],
            [
                'name' => 'Entertainment & Leisure',
                'name_ar' => 'الترفيه والاستجمام',
                'slug' => 'entertainment-leisure',
                'description' => 'Entertainment venues and leisure activities',
                'description_ar' => 'أماكن الترفيه وأنشطة الاستجمام',
                'icon' => 'music',
                'color' => '#EF4444',
                'sort_order' => 5,
                'children' => [
                    [
                        'name' => 'Cinemas',
                        'name_ar' => 'دور السينما',
                        'slug' => 'cinemas',
                        'icon' => 'film',
                        'sort_order' => 1,
                    ],
                    [
                        'name' => 'Art Galleries',
                        'name_ar' => 'المعارض الفنية',
                        'slug' => 'art-galleries',
                        'icon' => 'palette',
                        'sort_order' => 2,
                    ],
                    [
                        'name' => 'Museums',
                        'name_ar' => 'المتاحف',
                        'slug' => 'museums',
                        'icon' => 'museum',
                        'sort_order' => 3,
                    ],
                ]
            ],
        ];

        foreach ($categories as $categoryData) {
            $children = $categoryData['children'] ?? [];
            unset($categoryData['children']);

            $category = Category::create($categoryData);

            foreach ($children as $childData) {
                $childData['parent_id'] = $category->id;
                Category::create($childData);
            }
        }
    }
}