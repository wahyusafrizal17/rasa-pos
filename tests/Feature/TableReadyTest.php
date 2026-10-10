<?php

namespace Tests\Feature;

use App\Enums\OrderType;
use App\Enums\TableStatus;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Support\SeedsPosFixture;
use Tests\TestCase;

class TableReadyTest extends TestCase
{
    use RefreshDatabase;
    use SeedsPosFixture;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPosFixture();
    }

    public function test_occupied_table_cannot_be_chosen_again_in_pos(): void
    {
        $this->actingAsAtOutlet($this->cashier);
        $orders = app(OrderService::class);

        $first = $orders->createDraft([
            'outlet_id' => $this->outlet->id,
            'table_id' => $this->tableA->id,
            'order_type' => OrderType::DineIn->value,
        ]);
        $this->assertSame(TableStatus::Occupied, $this->tableA->fresh()->status);

        $this->get(route('pos.index'))
            ->assertOk()
            ->assertSee('value="'.$this->tableA->id.'" disabled', false);

        $second = $orders->createDraft([
            'outlet_id' => $this->outlet->id,
            'order_type' => OrderType::DineIn->value,
        ]);

        try {
            $orders->assignTable($second, $this->tableA->id);
            $this->fail('Occupied table should be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('table_id', $e->errors());
        }

        $this->assertSame($first->id, $this->tableA->fresh()->currentOrder()?->id);
    }

    public function test_cashier_can_mark_occupied_table_ready(): void
    {
        $this->actingAsAtOutlet($this->cashier);
        app(OrderService::class)->createDraft([
            'outlet_id' => $this->outlet->id,
            'table_id' => $this->tableA->id,
            'order_type' => OrderType::DineIn->value,
        ]);

        $this->get(route('tables.index'))
            ->assertOk()
            ->assertSee('Ready', false);

        $this->post(route('tables.ready', $this->tableA))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(TableStatus::Available, $this->tableA->fresh()->status);
    }
}
