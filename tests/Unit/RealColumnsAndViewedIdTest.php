<?php

namespace JundFauz\EloquentKompact\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use JundFauz\EloquentKompact\Model\KompactModel;
use JundFauz\EloquentKompact\Model\KompactUser;
use JundFauz\EloquentKompact\Model\Traits\AdditionalQueries;
use JundFauz\EloquentKompact\Model\Traits\Kompact;
use JundFauz\EloquentKompact\Model\Traits\ViewedId;
use JundFauz\EloquentKompact\Tests\Fixtures\Blueprint\Invoice;
use JundFauz\EloquentKompact\Tests\Fixtures\Blueprint\Ledger;
use JundFauz\EloquentKompact\Tests\Fixtures\Blueprint\Order;
use JundFauz\EloquentKompact\Tests\Fixtures\Blueprint\Ticket;
use JundFauz\EloquentKompact\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ViewedIdStandaloneModel extends Model
{
    use ViewedId;
}

class RealColumnsKompactItem extends KompactModel
{
    protected $guarded = [];
}

class RealColumnsAndViewedIdTest extends TestCase
{
    #[Test]
    public function kompact_includes_additional_queries_and_viewed_id_traits(): void
    {
        foreach ([Kompact::class, KompactModel::class, KompactUser::class] as $target) {
            $traits = class_uses_recursive($target);
            $this->assertContains(AdditionalQueries::class, $traits);
            $this->assertContains(ViewedId::class, $traits);
        }
    }

    #[Test]
    public function viewed_id_provides_default_and_overridden_prefix_and_source_column(): void
    {
        $this->assertNull(ViewedIdStandaloneModel::getPrefixId());
        $this->assertSame('id', ViewedIdStandaloneModel::getSourceColumnNameForViewedId());

        $this->assertNull(Ticket::getPrefixId());
        $this->assertSame('id', Ticket::getSourceColumnNameForViewedId());

        $this->assertSame('ORD', Order::getPrefixId());
        $this->assertSame('id', Order::getSourceColumnNameForViewedId());

        $this->assertSame('INV', Invoice::getPrefixId());
        $this->assertSame('seq_no', Invoice::getSourceColumnNameForViewedId());

        $this->assertSame(["'debit'" => "'DB'", "'credit'" => "'CR'"], Ledger::getPrefixId());
        $this->assertSame('id', Ledger::getSourceColumnNameForViewedId());
    }

    #[Test]
    public function custom_compact_strips_real_prefix_and_maps_single_and_multi_pair_arrays(): void
    {
        $item = new RealColumnsKompactItem;
        $item->forceFill([
            'real_weight_gram' => '12.5000000',
            'real_quantity_pcs' => 8,
            'real_final_weight_gram' => '10.2500000',
            'real_final_pcs' => 7,
            'status_enum' => 'done',
        ]);

        $this->assertSame([], $item->customCompact());

        $this->assertSame(
            [
                'weight_gram' => '12.5000000',
                'quantity_pcs' => 8,
            ],
            $item->customCompact('real_weight_gram', 'real_quantity_pcs')
        );

        $this->assertSame(
            [
                'initial_weight_gram' => '10.2500000',
            ],
            $item->customCompact(['real_final_weight_gram' => 'initial_weight_gram'])
        );

        $this->assertSame(
            [
                'initial_weight_gram' => '10.2500000',
                'initial_pcs' => 7,
                'weight_gram' => '12.5000000',
                'current_status' => 'done',
            ],
            $item->customCompact(
                [
                    'real_final_weight_gram' => 'initial_weight_gram',
                    'real_final_pcs' => 'initial_pcs',
                ],
                'real_weight_gram',
                ['status_enum' => 'current_status']
            )
        );
    }

    #[Test]
    public function assign_self_to_real_copies_all_specified_columns_unconditionally(): void
    {
        $item = new RealColumnsKompactItem;
        $item->forceFill([
            'weight_gram' => '25.0000000',
            'quantity_pcs' => 0,
            'note' => null,
            'real_note' => 'previous',
        ]);

        $item->assignSelfToReal('weight_gram', 'quantity_pcs', 'note');

        $this->assertSame('25.0000000', $item->real_weight_gram);
        $this->assertSame(0, $item->real_quantity_pcs);
        $this->assertNull($item->real_note);
    }

    #[Test]
    public function assign_self_to_real_if_only_copies_values_satisfying_the_condition(): void
    {
        $item = new RealColumnsKompactItem;
        $item->forceFill([
            'weight_gram' => '18.7500000',
            'quantity_pcs' => 0,
            'zero_str' => '0',
            'flag' => false,
            'empty_str' => '',
            'whitespace' => '   ',
            'null_col' => null,
            'real_empty_str' => 'keep-empty',
            'real_whitespace' => 'keep-ws',
            'real_null_col' => 'keep-null',
        ]);

        $item->assignSelfToRealIf(
            'filled',
            'weight_gram',
            'quantity_pcs',
            'zero_str',
            'flag',
            'empty_str',
            'whitespace',
            'null_col'
        );

        $this->assertSame('18.7500000', $item->real_weight_gram);
        $this->assertSame(0, $item->real_quantity_pcs);
        $this->assertSame('0', $item->real_zero_str);
        $this->assertFalse($item->real_flag);
        $this->assertSame('keep-empty', $item->real_empty_str);
        $this->assertSame('keep-ws', $item->real_whitespace);
        $this->assertSame('keep-null', $item->real_null_col);
    }

    #[Test]
    public function assign_self_to_real_if_accepts_array_conditions_and_no_condition(): void
    {
        $item = new RealColumnsKompactItem;
        $item->forceFill(['a' => 'x', 'b' => null, 'c' => 'paid']);

        $item->assignSelfToRealIf(['filled', 'same_with' => 'paid'], 'a', 'c');
        $this->assertNull($item->real_a);
        $this->assertSame('paid', $item->real_c);

        $item->assignSelfToRealIf('null', 'a', 'b');
        $this->assertNull($item->real_b);
        $this->assertNull($item->real_a);

        $item->assignSelfToRealIf(null, 'a');
        $this->assertSame('x', $item->real_a);
    }
}
