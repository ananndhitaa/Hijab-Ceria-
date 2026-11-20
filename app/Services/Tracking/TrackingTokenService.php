<?php

namespace App\Services\Tracking;

use App\Models\Sales\Order;
use App\Models\System\OrderTrackingToken;
use Illuminate\Support\Facades\DB;

class TrackingTokenService
{
    /**
     * Extract a 64-hex tracking token from a raw string or full tracking URL.
     *
     * @param string $input
     * @return string|null 64-hex token or null if invalid
     */
    public static function extractToken(string $input): ?string
    {
        $trimmed = trim($input);
        if (empty($trimmed)) {
            return null;
        }

        // Match 64-hex token from full URL path (/track/{token}) or standalone token string
        if (preg_match('/(?:\/track\/|^)([a-f0-9]{64})(?:[\/?#]|$)/i', $trimmed, $matches)) {
            return strtolower($matches[1]);
        }

        if (preg_match('/^[a-f0-9]{64}$/i', $trimmed)) {
            return strtolower($trimmed);
        }

        return null;
    }

    /**
     * Create a cryptographically secure tracking token for an order.
     * Stores only the SHA-256 hash in the database.
     * Returns the raw token string for one-time display/flash.
     * Tracking token is permanent (expires_at is null) and immutable.
     *
     * @param Order $order
     * @return string Raw token (64 hex characters)
     * @throws \DomainException if order already has an active tracking token
     */
    public function createForOrder(Order $order): string
    {
        if ($order->hasActiveTrackingToken()) {
            throw new \DomainException("Order {$order->order_no} sudah memiliki token tracking aktif. Token tracking bersifat permanen dan tidak dapat digenerate ulang secara otomatis.");
        }

        return DB::transaction(function () use ($order) {
            $rawToken = bin2hex(random_bytes(32));
            $hash = hash('sha256', $rawToken);

            OrderTrackingToken::create([
                'order_id'   => $order->id,
                'token_hash' => $hash,
                'expires_at' => null,
            ]);

            return $rawToken;
        });
    }

    /**
     * Regenerate tracking token for an order.
     * Explicitly invalidates any active tracking tokens and generates a new permanent one.
     *
     * @param Order $order
     * @return string Raw token (64 hex characters)
     */
    public function regenerateForOrder(Order $order): string
    {
        return DB::transaction(function () use ($order) {
            // Explicitly revoke prior active tokens for this order
            OrderTrackingToken::where('order_id', $order->id)
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);

            $rawToken = bin2hex(random_bytes(32));
            $hash = hash('sha256', $rawToken);

            OrderTrackingToken::create([
                'order_id'   => $order->id,
                'token_hash' => $hash,
                'expires_at' => null,
            ]);

            return $rawToken;
        });
    }

    /**
     * Look up a valid tracking token record by its raw token or tracking URL.
     *
     * @param string $rawToken
     * @return OrderTrackingToken|null
     */
    public function findValidToken(string $rawToken): ?OrderTrackingToken
    {
        $extracted = self::extractToken($rawToken) ?? $rawToken;
        if (empty($extracted)) {
            return null;
        }

        $hash = hash('sha256', $extracted);

        $token = OrderTrackingToken::where('token_hash', $hash)
            ->valid()
            ->with('order.items.variant.product', 'order.customer', 'order.statusHistories')
            ->first();

        if (! $token || ! $token->isValid()) {
            return null;
        }

        // Token is only valid as long as the Sales Order exists
        if (! $token->order) {
            return null;
        }

        return $token;
    }
}
