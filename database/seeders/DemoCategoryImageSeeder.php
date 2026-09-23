<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Gives the demo main categories the photos exported from the design, the way
 * an admin would upload them from the panel. Textiles has no matching photo and
 * keeps the striped placeholder.
 */
class DemoCategoryImageSeeder extends Seeder
{
    /**
     * Category slug => photo file in database/seeders/images/categories.
     *
     * @var array<string, string>
     */
    private array $photos = [
        'food' => 'food.webp',
        'beverages' => 'coffee.webp',
        'packaging' => 'packaging.webp',
        'building-materials' => 'construction.webp',
        'medical-supplies' => 'medical.webp',
        'stationery' => 'office.webp',
    ];

    public function run(): void
    {
        $disk = Storage::disk(Category::IMAGE_DISK);

        foreach ($this->photos as $slug => $file) {
            $category = Category::query()->where('slug', $slug)->first();

            if (! $category) {
                continue;
            }

            $path = "categories/{$file}";
            $disk->put($path, file_get_contents(database_path("seeders/images/categories/{$file}")));

            $category->update(['image_path' => $path]);
        }
    }
}
