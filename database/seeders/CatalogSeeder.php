<?php

namespace Database\Seeders;

use App\Enums\PrinterStation;
use App\Enums\ProductType;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['code' => 'PCS', 'name' => 'Pieces', 'family' => 'count', 'conversion_factor' => 1],
            ['code' => 'POR', 'name' => 'Portion', 'family' => 'count', 'conversion_factor' => 1],
            ['code' => 'KG', 'name' => 'Kilogram', 'family' => 'weight', 'conversion_factor' => 1000],
            ['code' => 'G', 'name' => 'Gram', 'family' => 'weight', 'conversion_factor' => 1],
            ['code' => 'L', 'name' => 'Liter', 'family' => 'volume', 'conversion_factor' => 1000],
            ['code' => 'ML', 'name' => 'Milliliter', 'family' => 'volume', 'conversion_factor' => 1],
        ];

        foreach ($units as $unit) {
            Unit::query()->updateOrCreate(['code' => $unit['code']], $unit);
        }

        $categories = [
            ['name' => 'Food', 'station' => PrinterStation::Kitchen->value, 'color' => '#f97316', 'sort_order' => 1],
            ['name' => 'Beverage', 'station' => PrinterStation::Bar->value, 'color' => '#0ea5e9', 'sort_order' => 2],
            ['name' => 'Dessert', 'station' => PrinterStation::Kitchen->value, 'color' => '#a855f7', 'sort_order' => 3],
            ['name' => 'Package', 'station' => PrinterStation::Cashier->value, 'color' => '#22c55e', 'sort_order' => 4],
            ['name' => 'Ingredients', 'station' => PrinterStation::Kitchen->value, 'color' => '#64748b', 'sort_order' => 5],
            ['name' => 'Semi Finished', 'station' => PrinterStation::Kitchen->value, 'color' => '#78716c', 'sort_order' => 6],
        ];

        foreach ($categories as $category) {
            Category::query()->updateOrCreate(
                ['slug' => Str::slug($category['name'])],
                $category + ['is_active' => true],
            );
        }

        $sku = 1;
        $nextSku = function () use (&$sku): string {
            return sprintf('PRD-%03d', $sku++);
        };

        $unit = fn (string $code) => Unit::query()->where('code', $code)->firstOrFail()->id;
        $category = fn (string $name) => Category::query()->where('name', $name)->firstOrFail();

        $foods = [
            ['name' => 'Nasi Goreng Rasa', 'price' => 38000, 'prep' => 12, 'desc' => 'Nasi goreng khas Rasa dengan bumbu rempah dan topping lengkap', 'image' => 'https://images.unsplash.com/photo-1512058564366-18510be2db19?auto=format&fit=crop&w=600&h=450&q=80'],
            ['name' => 'Chicken Rice Bowl', 'price' => 42000, 'prep' => 14, 'desc' => 'Bowl nasi hangat dengan ayam meresap dan sayuran segar', 'image' => 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=600&h=450&q=80'],
            ['name' => 'Beef Bowl', 'price' => 48000, 'prep' => 15, 'desc' => 'Irisan daging sapi empuk disajikan di atas nasi dengan saus house', 'image' => 'https://images.unsplash.com/photo-1604908176997-125f25cc6f3d?auto=format&fit=crop&w=600&h=450&q=80'],
            ['name' => 'Chicken Burger', 'price' => 35000, 'prep' => 10, 'desc' => 'Burger ayam crispy dengan selada, keju, dan saus spesial', 'image' => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&w=600&h=450&q=80'],
            ['name' => 'Fish & Chips', 'price' => 45000, 'prep' => 16, 'desc' => 'Ikan goreng tepung renyah disajikan dengan kentang goreng', 'image' => 'https://images.unsplash.com/photo-1579208030886-b937da0925dc?auto=format&fit=crop&w=600&h=450&q=80'],
            ['name' => 'Caesar Salad', 'price' => 32000, 'prep' => 8, 'desc' => 'Selada segar, crouton, dan dressing caesar creamy', 'image' => 'https://images.unsplash.com/photo-1546793665-c74683f339c1?auto=format&fit=crop&w=600&h=450&q=80'],
            ['name' => 'Chicken Wings', 'price' => 39000, 'prep' => 18, 'desc' => 'Sayap ayam glazed dengan saus pedas manis yang sticky', 'image' => 'https://images.unsplash.com/photo-1527477396000-e27163b481c2?auto=format&fit=crop&w=600&h=450&q=80'],
            ['name' => 'French Fries', 'price' => 28000, 'prep' => 8, 'desc' => 'Kentang goreng renyah yang pas untuk dampingan atau camilan', 'image' => 'https://images.unsplash.com/photo-1573080494125-573d9d98c864?auto=format&fit=crop&w=600&h=450&q=80'],
            ['name' => 'Onion Rings', 'price' => 28000, 'prep' => 9, 'desc' => 'Cincin bawang bombay goreng tepung yang gurih dan renyah', 'image' => 'https://images.unsplash.com/photo-1639024471283-03518883512d?auto=format&fit=crop&w=600&h=450&q=80'],
            ['name' => 'Garlic Bread', 'price' => 28000, 'prep' => 8, 'desc' => 'Roti panggang berlapis mentega bawang putih yang harum', 'image' => 'https://images.unsplash.com/photo-1573140247632-f8fd74997d5c?auto=format&fit=crop&w=600&h=450&q=80'],
        ];

        foreach ($foods as $item) {
            $this->product($nextSku(), $item['name'], $category('Food'), $unit('POR'), [
                'type' => ProductType::Finished,
                'price' => $item['price'],
                'cost' => round($item['price'] * 0.42, 2),
                'station' => PrinterStation::Kitchen->value,
                'prep_minutes' => $item['prep'],
                'image' => $item['image'],
                'description' => $item['desc'],
                'is_sellable' => true,
                'is_stockable' => true,
                'minimum_stock' => 10,
                'reorder_level' => 20,
                'maximum_stock' => 200,
            ]);
        }

        $beverages = [
            ['name' => 'Iced Lemon Tea', 'price' => 15000, 'prep' => 5, 'desc' => 'Teh lemon dingin yang segar untuk menemani hidangan', 'image' => 'https://images.unsplash.com/photo-1556679343-c7306c1976bc?auto=format&fit=crop&w=600&h=450&q=80'],
            ['name' => 'Iced Coffee', 'price' => 22000, 'prep' => 6, 'desc' => 'Kopi dingin dengan rasa bold dan es batu yang menyegarkan', 'image' => 'https://images.unsplash.com/photo-1517701604599-bb29b565090c?auto=format&fit=crop&w=600&h=450&q=80'],
            ['name' => 'Matcha Latte', 'price' => 25000, 'prep' => 7, 'desc' => 'Matcha creamy dengan susu yang lembut dan aroma teh hijau', 'image' => 'https://images.unsplash.com/photo-1515823662972-da6a2e4d3007?auto=format&fit=crop&w=600&h=450&q=80'],
            ['name' => 'Mineral Water', 'price' => 8000, 'prep' => 1, 'desc' => 'Air mineral dingin dalam kemasan botol', 'image' => 'https://images.unsplash.com/photo-1548839140-29a749e1cf4d?auto=format&fit=crop&w=600&h=450&q=80'],
            ['name' => 'Orange Juice', 'price' => 18000, 'prep' => 4, 'desc' => 'Jus jeruk segar yang manis dan menyegarkan', 'image' => 'https://images.unsplash.com/photo-1600271886742-f049cd451bba?auto=format&fit=crop&w=600&h=450&q=80'],
            ['name' => 'Chocolate Milkshake', 'price' => 28000, 'prep' => 6, 'desc' => 'Milkshake cokelat kental dengan topping whipped cream', 'image' => 'https://images.unsplash.com/photo-1572490122747-3968b75cc699?auto=format&fit=crop&w=600&h=450&q=80'],
        ];

        foreach ($beverages as $item) {
            $product = $this->product($nextSku(), $item['name'], $category('Beverage'), $unit('PCS'), [
                'type' => ProductType::Finished,
                'price' => $item['price'],
                'cost' => round($item['price'] * 0.35, 2),
                'station' => PrinterStation::Bar->value,
                'prep_minutes' => $item['prep'],
                'image' => $item['image'],
                'description' => $item['desc'],
                'is_sellable' => true,
                'is_stockable' => true,
                'minimum_stock' => 12,
                'reorder_level' => 24,
                'maximum_stock' => 250,
            ]);

            if ($item['name'] === 'Iced Coffee') {
                $product->variants()->updateOrCreate(
                    ['name' => 'Regular'],
                    ['sku' => $product->sku.'-REG', 'price_adjustment' => 0, 'is_active' => true],
                );
                $product->variants()->updateOrCreate(
                    ['name' => 'Large'],
                    ['sku' => $product->sku.'-LRG', 'price_adjustment' => 8000, 'is_active' => true],
                );
            }
        }

        $desserts = [
            ['name' => 'Chocolate Lava Cake', 'price' => 32000, 'prep' => 12, 'desc' => 'Kue cokelat hangat dengan lelehan lava di tengahnya', 'image' => 'https://images.unsplash.com/photo-1606313564200-e75d5e30476c?auto=format&fit=crop&w=600&h=450&q=80'],
            ['name' => 'Mango Pudding', 'price' => 28000, 'prep' => 8, 'desc' => 'Puding mangga lembut dengan potongan buah segar', 'image' => 'https://images.unsplash.com/photo-1488477181946-6428a0291777?auto=format&fit=crop&w=600&h=450&q=80'],
            ['name' => 'Cheese Cake', 'price' => 35000, 'prep' => 8, 'desc' => 'Cheesecake creamy dengan lapisan biskuit yang buttery', 'image' => 'https://images.unsplash.com/photo-1533134486753-c833f0ed4866?auto=format&fit=crop&w=600&h=450&q=80'],
        ];

        foreach ($desserts as $item) {
            $this->product($nextSku(), $item['name'], $category('Dessert'), $unit('PCS'), [
                'type' => ProductType::Finished,
                'price' => $item['price'],
                'cost' => round($item['price'] * 0.4, 2),
                'station' => PrinterStation::Kitchen->value,
                'prep_minutes' => $item['prep'],
                'image' => $item['image'],
                'description' => $item['desc'],
                'is_sellable' => true,
                'is_stockable' => true,
                'minimum_stock' => 8,
                'reorder_level' => 15,
                'maximum_stock' => 120,
            ]);
        }

        $this->product($nextSku(), 'Paket Hemat A', $category('Package'), $unit('PCS'), [
            'type' => ProductType::Package,
            'price' => 45000,
            'cost' => 28000,
            'station' => PrinterStation::Cashier->value,
            'prep_minutes' => 12,
            'image' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=600&h=450&q=80',
            'description' => 'Paket hemat berisi makanan utama dan minuman pilihan',
            'is_sellable' => true,
            'is_stockable' => false,
            'minimum_stock' => 0,
            'reorder_level' => 0,
            'maximum_stock' => 0,
        ]);

        $raws = [
            ['name' => 'Chicken', 'unit' => 'KG', 'reorder' => 10, 'min' => 5, 'max' => 250],
            ['name' => 'Beef', 'unit' => 'KG', 'reorder' => 8, 'min' => 4, 'max' => 180],
            ['name' => 'Rice', 'unit' => 'KG', 'reorder' => 15, 'min' => 8, 'max' => 300],
            ['name' => 'Seasoning', 'unit' => 'KG', 'reorder' => 2, 'min' => 1, 'max' => 40],
            ['name' => 'Cooking Oil', 'unit' => 'L', 'reorder' => 8, 'min' => 4, 'max' => 120],
            ['name' => 'Flour', 'unit' => 'KG', 'reorder' => 10, 'min' => 5, 'max' => 200],
            ['name' => 'Cheese', 'unit' => 'KG', 'reorder' => 3, 'min' => 1, 'max' => 60],
            ['name' => 'Lettuce', 'unit' => 'KG', 'reorder' => 4, 'min' => 2, 'max' => 80],
            ['name' => 'Bun', 'unit' => 'PCS', 'reorder' => 20, 'min' => 10, 'max' => 400],
            ['name' => 'Tea Leaf', 'unit' => 'G', 'reorder' => 200, 'min' => 100, 'max' => 5000],
            ['name' => 'Coffee Bean', 'unit' => 'KG', 'reorder' => 5, 'min' => 2, 'max' => 80],
            ['name' => 'Milk', 'unit' => 'L', 'reorder' => 10, 'min' => 5, 'max' => 150],
            ['name' => 'Sugar', 'unit' => 'KG', 'reorder' => 8, 'min' => 4, 'max' => 150],
        ];

        foreach ($raws as $item) {
            $this->product($nextSku(), $item['name'], $category('Ingredients'), $unit($item['unit']), [
                'type' => ProductType::Raw,
                'bom_level' => 0,
                'price' => 0,
                'cost' => 0,
                'station' => PrinterStation::Kitchen->value,
                'prep_minutes' => 0,
                'is_sellable' => false,
                'is_stockable' => true,
                'minimum_stock' => $item['min'],
                'reorder_level' => $item['reorder'],
                'maximum_stock' => $item['max'],
            ]);
        }

        $semi = [
            ['name' => 'Marinated Chicken', 'level' => 1],
            ['name' => 'Cooked Chicken', 'level' => 2],
            ['name' => 'Chicken Portion', 'level' => 3],
        ];

        foreach ($semi as $item) {
            $this->product($nextSku(), $item['name'], $category('Semi Finished'), $unit('KG'), [
                'type' => ProductType::SemiFinished,
                'bom_level' => $item['level'],
                'price' => 0,
                'cost' => 0,
                'station' => PrinterStation::Kitchen->value,
                'prep_minutes' => 20,
                'is_sellable' => false,
                'is_stockable' => true,
                'minimum_stock' => 5,
                'reorder_level' => 10,
                'maximum_stock' => 80,
            ]);
        }
    }

    protected function product(string $sku, string $name, Category $category, int $unitId, array $attrs): Product
    {
        return Product::query()->updateOrCreate(
            ['sku' => $sku],
            array_merge([
                'name' => $name,
                'category_id' => $category->id,
                'unit_id' => $unitId,
                'bom_level' => 0,
                'description' => $name,
                'is_active' => true,
            ], $attrs),
        );
    }
}
