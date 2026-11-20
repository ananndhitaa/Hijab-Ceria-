<?php

namespace App\Services\Master;

use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\System\DocumentSequence;
use Illuminate\Support\Facades\DB;
use OverflowException;

class ProductVariantSkuGenerator
{
    public const PREFIX_HJB = 'HJB';
    public const SEQUENCE_PREFIX = 'SKU';
    public const MAX_SEQUENCE = 999;

    /**
     * Generate a concurrency-safe, semantic, human-readable, and unique SKU for a ProductVariant.
     * Format: HJB-{PRODUCT_CODE}-{VARIANT_CODE}-{SEQUENCE} (e.g. HJB-VP-DP-001)
     *
     * @param Product $product
     * @param string $variantName
     * @return string
     * @throws OverflowException
     */
    public function generate(Product $product, string $variantName): string
    {
        // 1. Resolve stable product code
        $productCode = $this->resolveProductCode($product);

        // 2. Derive semantic variant code with collision resolution
        $variantCode = $this->generateVariantCode($variantName, $product, $productCode);

        // 3. Concurrency-safe per-product sequence generation via document_sequences
        $period = sprintf('P%05d', $product->id % 100000);

        return DB::transaction(function () use ($product, $productCode, $variantCode, $period) {
            $outletId = $product->outlet_id;

            DB::table('document_sequences')->insertOrIgnore([
                'outlet_id'     => $outletId,
                'prefix'        => self::SEQUENCE_PREFIX,
                'period'        => $period,
                'last_sequence' => 0,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            /** @var DocumentSequence $seqRecord */
            $seqQuery = DocumentSequence::where('prefix', self::SEQUENCE_PREFIX)
                ->where('period', $period);

            if ($outletId !== null) {
                $seqQuery->where('outlet_id', $outletId);
            } else {
                $seqQuery->whereNull('outlet_id');
            }

            $seqRecord = $seqQuery->lockForUpdate()->firstOrFail();

            // If sequence is at 0, detect highest existing numeric sequence for this product
            if ($seqRecord->last_sequence === 0) {
                $maxSeq = 0;
                $existingSkus = ProductVariant::where('product_id', $product->id)->pluck('sku');
                foreach ($existingSkus as $existingSku) {
                    if (preg_match('/-(\d{3,})$/', $existingSku, $matches)) {
                        $num = (int) $matches[1];
                        if ($num > $maxSeq) {
                            $maxSeq = $num;
                        }
                    }
                }
                $seqRecord->last_sequence = $maxSeq;
            }

            // Loop until a non-colliding candidate SKU is found within tenant/legacy namespace
            do {
                if ($seqRecord->last_sequence >= self::MAX_SEQUENCE) {
                    throw new OverflowException(
                        "Nomor urut varian untuk produk [{$productCode}] telah melebihi batas maksimal (" . self::MAX_SEQUENCE . ")."
                    );
                }

                $seqRecord->last_sequence += 1;
                $seqStr = sprintf('%03d', $seqRecord->last_sequence);
                $candidate = sprintf('%s-%s-%s-%s', self::PREFIX_HJB, $productCode, $variantCode, $seqStr);

                $candidateQuery = ProductVariant::where('sku', $candidate);
                if ($outletId !== null) {
                    $candidateQuery->where('outlet_id', $outletId);
                } else {
                    $candidateQuery->whereNull('outlet_id');
                }
            } while ($candidateQuery->exists());

            $seqRecord->save();

            return $candidate;
        });
    }

    /**
     * Resolve the stable product code, creating or inferring it if missing.
     *
     * @param Product $product
     * @return string
     */
    public function resolveProductCode(Product $product): string
    {
        if (!empty($product->code)) {
            return strtoupper($product->code);
        }

        // Check if any existing variant in this product has SKU HJB-{CODE}-...
        $existingSku = ProductVariant::where('product_id', $product->id)
            ->whereNotNull('sku')
            ->value('sku');

        if ($existingSku && preg_match('/^HJB-([A-Z0-9]+)-/i', $existingSku, $matches)) {
            $code = strtoupper($matches[1]);
            $product->code = $code;
            $product->saveQuietly();
            return $code;
        }

        /** @var ProductCodeGenerator $generator */
        $generator = app(ProductCodeGenerator::class);
        $code = $generator->generate($product->product_name, $product->id);
        $product->code = $code;
        $product->saveQuietly();

        return $code;
    }

    /**
     * Derive a human-readable variant code from variant name.
     * Multi-word: initials (e.g. Dusty Pink -> DP, Olive Green -> OG)
     * Single-word: first letter + first consonant (e.g. Mocca -> MC, Cream -> CR, Black -> BL)
     *
     * @param string $variantName
     * @param Product $product
     * @param string $productCode
     * @return string
     */
    public function generateVariantCode(string $variantName, Product $product, string $productCode): string
    {
        $cleanName = trim(preg_replace('/[^a-zA-Z0-9\s]/', '', $variantName));
        $words = array_values(array_filter(preg_split('/\s+/', $cleanName)));

        if (empty($words)) {
            $baseCode = 'VR';
        } elseif (count($words) >= 2) {
            $baseCode = strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
        } else {
            $word = strtoupper($words[0]);
            if (strlen($word) <= 2) {
                $baseCode = str_pad($word, 2, 'X');
            } else {
                $firstChar = substr($word, 0, 1);
                $remainder = substr($word, 1);
                if (preg_match('/[BCDFGHJKLMNPQRSTVWXYZ]/i', $remainder, $matches)) {
                    $baseCode = $firstChar . strtoupper($matches[0]);
                } else {
                    $baseCode = substr($word, 0, 2);
                }
            }
        }

        // Check if this variant code is already used by another variant in the same product
        $existingCodes = ProductVariant::where('product_id', $product->id)
            ->pluck('sku')
            ->map(function ($sku) use ($productCode) {
                if (preg_match('/^HJB-' . preg_quote($productCode, '/') . '-([A-Z0-9]+)-\d+$/i', $sku, $m)) {
                    return strtoupper($m[1]);
                }
                return null;
            })
            ->filter()
            ->all();

        if (!in_array($baseCode, $existingCodes, true)) {
            return $baseCode;
        }

        // Collision within product: Try 3 letters if available
        if (count($words) >= 3) {
            $threeLetter = strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1) . substr($words[2], 0, 1));
            if (!in_array($threeLetter, $existingCodes, true)) {
                return $threeLetter;
            }
        } elseif (count($words) === 1 && strlen($words[0]) >= 3) {
            // Pick first letter + next two consonants or letters
            $threeLetter = strtoupper(substr($words[0], 0, 3));
            if (!in_array($threeLetter, $existingCodes, true)) {
                return $threeLetter;
            }
        }

        // Suffix counter
        $counter = 2;
        while (in_array($baseCode . $counter, $existingCodes, true)) {
            $counter++;
        }

        return $baseCode . $counter;
    }
}
