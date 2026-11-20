<?php

namespace Tests\Unit;

use App\Models\Master\Customer;
use App\Models\Sales\Order;
use App\Models\System\OrderTrackingToken;
use App\Services\Tracking\TrackingTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackingTokenServiceTest extends TestCase
{
    use RefreshDatabase;

    private TrackingTokenService $service;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TrackingTokenService();

        $customer = Customer::create(['customer_name' => 'Customer Testing']);
        $this->order = Order::create([
            'customer_id' => $customer->id,
            'order_no'    => 'SO-202608-0001',
            'order_date'  => '2026-08-15',
            'status'      => 'KOMUNIKASI',
        ]);
    }

    public function test_creates_tracking_token_with_sha256_hash(): void
    {
        $rawToken = $this->service->createForOrder($this->order);

        $this->assertNotEmpty($rawToken);
        $this->assertEquals(64, strlen($rawToken)); // 32 bytes hex = 64 chars

        $expectedHash = hash('sha256', $rawToken);

        $this->assertDatabaseHas('order_tracking_tokens', [
            'order_id'   => $this->order->id,
            'token_hash' => $expectedHash,
        ]);
    }

    public function test_raw_token_is_not_stored_in_database(): void
    {
        $rawToken = $this->service->createForOrder($this->order);

        // Database should never contain the raw token string
        $this->assertDatabaseMissing('order_tracking_tokens', [
            'token_hash' => $rawToken,
        ]);
    }

    public function test_can_find_valid_token_by_raw_token(): void
    {
        $rawToken = $this->service->createForOrder($this->order);

        $found = $this->service->findValidToken($rawToken);

        $this->assertNotNull($found);
        $this->assertEquals($this->order->id, $found->order_id);
    }

    public function test_returns_null_when_token_is_revoked(): void
    {
        $rawToken = $this->service->createForOrder($this->order);

        $tokenRecord = OrderTrackingToken::where('token_hash', hash('sha256', $rawToken))->first();
        $tokenRecord->update(['revoked_at' => now()]);

        $found = $this->service->findValidToken($rawToken);

        $this->assertNull($found);
    }

    public function test_create_for_order_sets_expires_at_to_null_for_permanent_tracking(): void
    {
        $rawToken = $this->service->createForOrder($this->order);

        $tokenRecord = OrderTrackingToken::where('token_hash', hash('sha256', $rawToken))->first();
        $this->assertNotNull($tokenRecord);
        $this->assertNull($tokenRecord->expires_at);
        $this->assertNull($tokenRecord->revoked_at);
        $this->assertTrue($tokenRecord->isValid());
    }

    public function test_create_for_order_throws_domain_exception_if_order_already_has_active_token(): void
    {
        $this->service->createForOrder($this->order);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('sudah memiliki token tracking aktif');

        $this->service->createForOrder($this->order);
    }

    public function test_regenerate_for_order_revokes_old_token_and_creates_new_permanent_token(): void
    {
        $oldToken = $this->service->createForOrder($this->order);
        $oldHash = hash('sha256', $oldToken);

        $newToken = $this->service->regenerateForOrder($this->order);
        $newHash = hash('sha256', $newToken);

        $this->assertNotEquals($oldToken, $newToken);

        $oldRecord = OrderTrackingToken::where('token_hash', $oldHash)->first();
        $newRecord = OrderTrackingToken::where('token_hash', $newHash)->first();

        $this->assertNotNull($oldRecord->revoked_at);
        $this->assertFalse($oldRecord->isValid());

        $this->assertNull($newRecord->revoked_at);
        $this->assertNull($newRecord->expires_at);
        $this->assertTrue($newRecord->isValid());

        $this->assertNull($this->service->findValidToken($oldToken));
        $this->assertNotNull($this->service->findValidToken($newToken));
    }

    public function test_find_valid_token_treats_null_expires_at_as_permanent(): void
    {
        $rawToken = $this->service->createForOrder($this->order);
        $found = $this->service->findValidToken($rawToken);

        $this->assertNotNull($found);
        $this->assertNull($found->expires_at);
        $this->assertTrue($found->isValid());
    }

    public function test_find_valid_token_returns_null_when_expired_in_past(): void
    {
        $rawToken = $this->service->createForOrder($this->order);
        $record = OrderTrackingToken::where('token_hash', hash('sha256', $rawToken))->first();
        $record->update(['expires_at' => now()->subDay()]);

        $found = $this->service->findValidToken($rawToken);
        $this->assertNull($found);
    }

    public function test_find_valid_token_returns_token_when_expires_at_is_in_future(): void
    {
        $rawToken = $this->service->createForOrder($this->order);
        $record = OrderTrackingToken::where('token_hash', hash('sha256', $rawToken))->first();
        $record->update(['expires_at' => now()->addDays(7)]);

        $found = $this->service->findValidToken($rawToken);
        $this->assertNotNull($found);
        $this->assertTrue($found->isValid());
    }
}
