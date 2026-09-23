<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Main categories with the sub-categories the prototype references.
     *
     * @var array<string, array{0: string, 1: string, 2: array<int, array{0: string, 1: string, 2: string}>}>
     */
    private array $tree = [
        'food' => ['مواد غذائية', 'Food', [
            ['grains-rice', 'حبوب وأرز', 'Grains & rice'],
            ['cooking-oils', 'زيوت الطعام', 'Cooking oils'],
            ['sugar-sweeteners', 'سكر ومحليات', 'Sugar & sweeteners'],
            ['dairy', 'ألبان وأجبان', 'Dairy'],
            ['frozen-food', 'أغذية مجمدة', 'Frozen food'],
        ]],
        'beverages' => ['مشروبات', 'Beverages', [
            ['coffee-hot-drinks', 'بن ومشروبات ساخنة', 'Coffee & hot beverages'],
            ['bottled-water', 'مياه معدنية', 'Bottled water'],
            ['juices-soft-drinks', 'عصائر ومشروبات غازية', 'Juices & soft drinks'],
        ]],
        'packaging' => ['تغليف', 'Packaging', [
            ['paper-cups', 'أكواب ورقية', 'Paper cups'],
            ['cartons-boxes', 'كراتين وصناديق', 'Cartons & boxes'],
            ['plastic-packaging', 'تغليف بلاستيكي', 'Plastic packaging'],
        ]],
        'building-materials' => ['مواد بناء', 'Building materials', [
            ['cement', 'أسمنت', 'Cement'],
            ['steel', 'حديد تسليح', 'Steel rebar'],
            ['paints', 'دهانات', 'Paints'],
        ]],
        'medical-supplies' => ['مستلزمات طبية', 'Medical supplies', [
            ['gloves-ppe', 'قفازات ومستلزمات وقاية', 'Gloves & PPE'],
            ['disposables', 'مستهلكات طبية', 'Medical disposables'],
        ]],
        'stationery' => ['قرطاسية', 'Stationery', [
            ['copy-paper', 'ورق تصوير', 'Copy paper'],
            ['notebooks', 'كراسات ودفاتر', 'Notebooks'],
        ]],
        'textiles' => ['منسوجات', 'Textiles', [
            ['bed-linen', 'مفروشات وأسرّة', 'Bed linen'],
            ['uniforms', 'يونيفورم', 'Uniforms'],
        ]],
    ];

    public function run(): void
    {
        foreach (array_values($this->tree) as $sort => [$nameAr, $nameEn, $children]) {
            $slug = array_keys($this->tree)[$sort];

            $parent = Category::updateOrCreate(
                ['slug' => $slug],
                ['name_ar' => $nameAr, 'name_en' => $nameEn, 'sort' => $sort, 'parent_id' => null],
            );

            foreach ($children as $childSort => [$childSlug, $childAr, $childEn]) {
                Category::updateOrCreate(
                    ['slug' => $childSlug],
                    [
                        'parent_id' => $parent->id,
                        'name_ar' => $childAr,
                        'name_en' => $childEn,
                        'sort' => $childSort,
                    ],
                );
            }
        }
    }
}
