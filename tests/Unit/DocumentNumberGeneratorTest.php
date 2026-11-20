<?php

namespace Tests\Unit;

use App\Models\System\DocumentSequence;
use App\Services\System\DocumentNumberGenerator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use OverflowException;
use Tests\TestCase;

class DocumentNumberGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private DocumentNumberGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new DocumentNumberGenerator();
    }

    public function test_generates_correct_format_with_prefix_period_and_sequence(): void
    {
        $date = Carbon::create(2026, 8, 15, 10, 0, 0, 'Asia/Jakarta');
        $number = $this->generator->generate('SO', $date);

        $this->assertEquals('SO-202608-0001', $number);
        $this->assertDatabaseHas('document_sequences', [
            'prefix'        => 'SO',
            'period'        => '202608',
            'last_sequence' => 1,
        ]);
    }

    public function test_sequence_increments_consecutively_within_same_month(): void
    {
        $date = Carbon::create(2026, 8, 15, 10, 0, 0, 'Asia/Jakarta');

        $so1 = $this->generator->generate('SO', $date);
        $so2 = $this->generator->generate('SO', $date);
        $so3 = $this->generator->generate('SO', $date);

        $this->assertEquals('SO-202608-0001', $so1);
        $this->assertEquals('SO-202608-0002', $so2);
        $this->assertEquals('SO-202608-0003', $so3);
    }

    public function test_sequence_resets_by_month(): void
    {
        $dateAug = Carbon::create(2026, 8, 31, 23, 59, 59, 'Asia/Jakarta');
        $dateSep = Carbon::create(2026, 9, 1, 0, 0, 1, 'Asia/Jakarta');

        $soAug = $this->generator->generate('SO', $dateAug);
        $soSep = $this->generator->generate('SO', $dateSep);

        $this->assertEquals('SO-202608-0001', $soAug);
        $this->assertEquals('SO-202609-0001', $soSep);
    }

    public function test_month_rollover_with_high_sequence_does_not_affect_subsequent_month_for_all_prefixes(): void
    {
        $dateAug = Carbon::create(2026, 8, 20, 10, 0, 0, 'Asia/Jakarta');
        $dateSep = Carbon::create(2026, 9, 1, 8, 0, 0, 'Asia/Jakarta');

        // Advance August sequences to higher values
        for ($i = 1; $i <= 8; $i++) {
            $lastBatAug = $this->generator->generate('BAT', $dateAug);
        }
        $this->assertEquals('BAT-202608-0008', $lastBatAug);

        for ($i = 1; $i <= 5; $i++) {
            $lastSoAug = $this->generator->generate('SO', $dateAug);
        }
        $this->assertEquals('SO-202608-0005', $lastSoAug);

        for ($i = 1; $i <= 3; $i++) {
            $lastPoAug = $this->generator->generate('PO', $dateAug);
            $lastInvAug = $this->generator->generate('INV', $dateAug);
        }
        $this->assertEquals('PO-202608-0003', $lastPoAug);
        $this->assertEquals('INV-202608-0003', $lastInvAug);

        // September sequences MUST restart independently from 0001
        $batSep = $this->generator->generate('BAT', $dateSep);
        $soSep = $this->generator->generate('SO', $dateSep);
        $poSep = $this->generator->generate('PO', $dateSep);
        $invSep = $this->generator->generate('INV', $dateSep);

        $this->assertEquals('BAT-202609-0001', $batSep);
        $this->assertEquals('SO-202609-0001', $soSep);
        $this->assertEquals('PO-202609-0001', $poSep);
        $this->assertEquals('INV-202609-0001', $invSep);
    }

    public function test_different_prefixes_have_independent_sequences(): void
    {
        $date = Carbon::create(2026, 8, 15, 10, 0, 0, 'Asia/Jakarta');

        $so = $this->generator->generate('SO', $date);
        $bat = $this->generator->generate('BAT', $date);
        $po = $this->generator->generate('PO', $date);
        $inv = $this->generator->generate('INV', $date);

        $this->assertEquals('SO-202608-0001', $so);
        $this->assertEquals('BAT-202608-0001', $bat);
        $this->assertEquals('PO-202608-0001', $po);
        $this->assertEquals('INV-202608-0001', $inv);
    }

    public function test_overflow_exceeding_9999_throws_overflow_exception(): void
    {
        $date = Carbon::create(2026, 8, 15, 10, 0, 0, 'Asia/Jakarta');

        DocumentSequence::create([
            'prefix'        => 'SO',
            'period'        => '202608',
            'last_sequence' => 9999,
        ]);

        $this->expectException(OverflowException::class);
        $this->generator->generate('SO', $date);
    }

    public function test_participates_in_outer_transaction_and_rolls_back_on_failure(): void
    {
        $date = Carbon::create(2026, 8, 15, 10, 0, 0, 'Asia/Jakarta');

        try {
            DB::transaction(function () use ($date) {
                $this->generator->generate('SO', $date);
                throw new \RuntimeException('Simulated failure during order creation.');
            });
        } catch (\RuntimeException $e) {
            // Expected
        }

        $this->assertDatabaseMissing('document_sequences', [
            'prefix' => 'SO',
            'period' => '202608',
        ]);
    }
}
