<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Main categories, with sub-categories where the prototype references them.
     * "Other" stays last.
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
        'medical-supplies' => ['مستلزمات وأجهزة طبية', 'Medical supplies & devices', [
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
        'agricultural-supplies' => ['مستلزمات واسمدة زراعية وكيماوية', 'Agricultural supplies, fertilisers & chemicals', []],
        'machines' => ['مكينات', 'Machines', []],
        'plastic' => ['بلاستيك', 'Plastic', []],
        'furniture' => ['اثاث وتجهيزات', 'Furniture & fittings', []],
        'solar-energy' => ['طاقة وألواح شمسية', 'Energy & solar panels', []],
        'devices-equipment' => ['أجهزة ومعدات', 'Devices & equipment', []],
        'detergents-chemicals' => ['المنظفات والكيماويات', 'Detergents & chemicals', []],
        'clothing-uniforms' => ['الملابس واليونيفورم', 'Clothing & uniforms', []],
        'animal-feed' => ['اعلاف', 'Animal feed', []],
        'printing-advertising' => ['الطباعة والدعاية', 'Printing & advertising', []],
        'catering-hospitality' => ['الكاترينج والضيافة', 'Catering & hospitality', []],
        'pharma-cosmetics' => ['أدوية وتجميل', 'Pharmaceuticals & cosmetics', []],
        'security-safety' => ['الامن والسلامة', 'Security & safety', []],
        'hotel-restaurant-supplies' => ['مستلزمات الفنادق والمطاعم', 'Hotel & restaurant supplies', []],
        'transport-logistics' => ['النقل والخدمات اللوجستية', 'Transport & logistics', []],
        'auto-parts' => ['قطع غيار سيارات', 'Auto parts', []],
        'electronics' => ['الكترونيات', 'Electronics', []],
        'other' => ['أخرى', 'Other', []],
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
