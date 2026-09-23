<?php

namespace App\Services;

use App\Enums\DiscountType;
use App\Models\Discount;
use App\Models\Product;

class DiscountService
{
    public function calculate(Discount $discount, float $subtotal, array $items = []): float
    {
        if ($subtotal < (float) $discount->minimum_transaction) {
            return 0;
        }

        if ($discount->type === DiscountType::Bogo) {
            return $this->calculateBogo($discount, $subtotal, $items);
        }

        $base = $subtotal;

        if (in_array($discount->scope, ['item', 'category'], true) && $items !== []) {
            $eligible = 0;
            $productIds = $discount->items->pluck('product_id')->filter()->all();
            $categoryIds = $discount->items->pluck('category_id')->filter()->all();

            foreach ($items as $item) {
                $productId = $item['product_id'] ?? null;
                $lineTotal = (float) ($item['unit_price'] ?? 0) * (float) ($item['quantity'] ?? 1);
                $matches = false;

                if ($productIds && in_array($productId, $productIds, true)) {
                    $matches = true;
                }

                if (! $matches && $categoryIds && $productId) {
                    $categoryId = Product::query()->whereKey($productId)->value('category_id');
                    $matches = in_array($categoryId, $categoryIds, true);
                }

                if ($matches) {
                    $eligible += $lineTotal;
                }
            }

            $base = $eligible;
        }

        $amount = $discount->type === DiscountType::Percentage
            ? $base * ((float) $discount->value / 100)
            : (float) $discount->value;

        if ($discount->maximum_discount !== null) {
            $amount = min($amount, (float) $discount->maximum_discount);
        }

        return round(max(0, min($amount, $subtotal)), 2);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    protected function calculateBogo(Discount $discount, float $subtotal, array $items): float
    {
        $discount->loadMissing('items');
        $productIds = $discount->items->pluck('product_id')->filter()->all();
        $categoryIds = $discount->items->pluck('category_id')->filter()->all();
        $units = [];

        foreach ($items as $item) {
            $productId = $item['product_id'] ?? null;
            $matches = $discount->scope === 'order';

            if ($discount->scope === 'item') {
                $matches = $productIds && in_array($productId, $productIds, true);
            }

            if ($discount->scope === 'category' && $productId) {
                $categoryId = Product::query()->whereKey($productId)->value('category_id');
                $matches = in_array($categoryId, $categoryIds, true);
            }

            if (! $matches) {
                continue;
            }

            $qty = max(1, (int) round((float) ($item['quantity'] ?? 1)));
            for ($i = 0; $i < $qty; $i++) {
                $units[] = (float) ($item['unit_price'] ?? 0);
            }
        }

        sort($units);
        $group = max(1, (int) $discount->buy_qty) + max(1, (int) $discount->get_qty);
        $free = intdiv(count($units), $group) * max(1, (int) $discount->get_qty);
        $amount = array_sum(array_slice($units, 0, $free));

        if ($discount->maximum_discount !== null) {
            $amount = min($amount, (float) $discount->maximum_discount);
        }

        return round(max(0, min($amount, $subtotal)), 2);
    }

    public function activeForOutlet(?int $outletId = null)
    {
        $outletId = $outletId ?? current_outlet_id();

        return Discount::query()
            ->with(['items', 'outlets'])
            ->where('is_active', true)
            ->get()
            ->filter(fn (Discount $discount) => $discount->isCurrentlyActive($outletId))
            ->values();
    }
}
