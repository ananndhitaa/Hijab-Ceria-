<?php

namespace App\Services\Platform;

use App\Models\Master\Product;
use App\Models\Platform\OutletListingSlot;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductPublicationService
{
    public function __construct(
        protected TenantEntitlementService $entitlementService
    ) {}

    /**
     * Publish a product to the public storefront with concurrency protection.
     *
     * @throws ValidationException
     */
    public function publish(Product $product, int $outletId): Product
    {
        if ($product->outlet_id !== $outletId) {
            throw ValidationException::withMessages([
                'product' => ['Produk tidak ditemukan atau bukan milik outlet ini.'],
            ]);
        }

        if ($product->is_published) {
            return $product;
        }

        return DB::transaction(function () use ($product, $outletId) {
            // Lock slot record for concurrency serialization
            $slotRecord = OutletListingSlot::firstOrCreate(
                ['outlet_id' => $outletId],
                ['purchased_slots' => 0]
            );
            $slotRecord = OutletListingSlot::where('id', $slotRecord->id)->lockForUpdate()->first();

            $capacity = $this->entitlementService->getListingCapacity($outletId);
            $currentlyPublished = Product::where('outlet_id', $outletId)
                ->where('is_published', true)
                ->lockForUpdate()
                ->count();

            if ($currentlyPublished >= $capacity) {
                throw ValidationException::withMessages([
                    'listing_quota' => [
                        "Kuota etalase publik telah penuh ({$currentlyPublished}/{$capacity} slot). Tingkatkan paket Premium atau beli slot tambahan untuk menerbitkan produk ini.",
                    ],
                ]);
            }

            $product->is_published = true;
            $product->save();

            return $product;
        });
    }

    /**
     * Unpublish a product from the public storefront.
     */
    public function unpublish(Product $product, int $outletId): Product
    {
        if ($product->outlet_id !== $outletId) {
            throw ValidationException::withMessages([
                'product' => ['Produk tidak ditemukan atau bukan milik outlet ini.'],
            ]);
        }

        if (! $product->is_published) {
            return $product;
        }

        $product->is_published = false;
        $product->save();

        return $product;
    }

    /**
     * Update public showcase details for a product.
     */
    public function updateShowcaseDetails(Product $product, int $outletId, array $data): Product
    {
        if ($product->outlet_id !== $outletId) {
            throw ValidationException::withMessages([
                'product' => ['Produk tidak ditemukan atau bukan milik outlet ini.'],
            ]);
        }

        $fillableData = array_intersect_key($data, array_flip([
            'public_description',
            'public_price',
            'featured_image',
            'public_category',
        ]));

        $product->update($fillableData);

        return $product;
    }
}
