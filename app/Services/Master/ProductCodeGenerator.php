<?php

namespace App\Services\Master;

use App\Models\Master\Product;

class ProductCodeGenerator
{
    /**
     * Generate a deterministic, human-readable, and unique product code suggestion.
     * Example: "Hijab Voal Polos Premium" -> "VP" (Voal Premium)
     *          "Hijab Viscose" -> "VS"
     *
     * @param string $productName
     * @param int|null $ignoreProductId
     * @return string
     */
    public function generate(string $productName, ?int $ignoreProductId = null): string
    {
        $baseCode = $this->deriveBaseCode($productName);

        return $this->resolveCollision($baseCode, $productName, $ignoreProductId);
    }

    /**
     * Derive a 2-character semantic base code from the product name.
     * Identifies primary product/fabric term and primary qualifier.
     *
     * @param string $productName
     * @return string
     */
    public function deriveBaseCode(string $productName): string
    {
        // Clean and normalize name
        $name = trim($productName);

        // Strip generic prefix "Hijab" (case-insensitive)
        $nameWithoutPrefix = preg_replace('/^hijab\s+/i', '', $name);
        $cleanName = trim(preg_replace('/[^a-zA-Z0-9\s]/', '', $nameWithoutPrefix));

        if (empty($cleanName)) {
            $cleanName = trim(preg_replace('/[^a-zA-Z0-9\s]/', '', $name));
        }

        $words = array_values(array_filter(preg_split('/\s+/', $cleanName)));

        if (empty($words)) {
            return 'PR';
        }

        // Multi-word:
        if (count($words) >= 2) {
            $first = strtoupper(substr($words[0], 0, 1));

            // For multi-word product names (e.g. "Voal Polos Premium"),
            // pair primary fabric term ("Voal") with primary qualifier ("Premium"), skipping style descriptors like "Polos".
            $styleDescriptors = ['polos', 'basic', 'standar', 'standard'];
            $qualifierWord = null;

            for ($i = count($words) - 1; $i >= 1; $i--) {
                if (!in_array(strtolower($words[$i]), $styleDescriptors, true)) {
                    $qualifierWord = $words[$i];
                    break;
                }
            }

            if (!$qualifierWord) {
                $qualifierWord = $words[count($words) - 1];
            }

            $second = strtoupper(substr($qualifierWord, 0, 1));
            return $first . $second;
        }

        // Single-word (e.g. "Viscose" -> "VS", "Pashmina" -> "PS")
        $word = strtoupper($words[0]);
        if (strlen($word) <= 2) {
            return str_pad($word, 2, 'X');
        }

        $firstChar = substr($word, 0, 1);
        $remainder = substr($word, 1);

        // Look for the first consonant in remainder (excluding A, E, I, O, U)
        if (preg_match('/[BCDFGHJKLMNPQRSTVWXYZ]/i', $remainder, $matches)) {
            return $firstChar . strtoupper($matches[0]);
        }

        // Fallback to first 2 letters if no consonants found
        return substr($word, 0, 2);
    }

    /**
     * Ensure the generated product code is unique in the database.
     *
     * @param string $baseCode
     * @param string $productName
     * @param int|null $ignoreProductId
     * @return string
     */
    protected function resolveCollision(string $baseCode, string $productName, ?int $ignoreProductId = null): string
    {
        if ($this->isCodeAvailable($baseCode, $ignoreProductId)) {
            return $baseCode;
        }

        // Strategy 1: For multi-word products, try 3-letter abbreviation if available (e.g. VPP)
        $words = array_values(array_filter(preg_split('/\s+/', trim(preg_replace('/^hijab\s+/i', '', $productName)))));
        if (count($words) >= 3) {
            $threeLetter = strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1) . substr($words[2], 0, 1));
            if ($this->isCodeAvailable($threeLetter, $ignoreProductId)) {
                return $threeLetter;
            }
        }

        // Strategy 2: Append sequential numeric counter (e.g. VP2, VP3)
        $counter = 2;
        while (!$this->isCodeAvailable($baseCode . $counter, $ignoreProductId)) {
            $counter++;
        }

        return $baseCode . $counter;
    }

    /**
     * Check if a product code is available.
     *
     * @param string $code
     * @param int|null $ignoreProductId
     * @return bool
     */
    protected function isCodeAvailable(string $code, ?int $ignoreProductId = null): bool
    {
        return !Product::where('code', $code)
            ->when($ignoreProductId, fn($q) => $q->where('id', '!=', $ignoreProductId))
            ->exists();
    }
}
