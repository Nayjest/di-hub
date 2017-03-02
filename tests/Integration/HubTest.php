<?php

namespace Nayjest\DI\Test\Integration;

use Nayjest\DI\Exception\CanNotRemoveDefinitionException;
use Nayjest\DI\Exception\NotFoundException;
use Nayjest\DI\Exception\ReadonlyException;
use Nayjest\DI\ItemDefinition;
use Nayjest\DI\Hub;
use Nayjest\DI\RelationDefinition;
use PHPUnit\Framework\TestCase;

class HubTest extends TestCase
{
    public function testAddItemDefinitions()
    {
        $hub = new Hub();
        $d1 = new ItemDefinition('item1');
        $d1->source = function () {
            return 1;
        };
        $d2 = new ItemDefinition('item2');
        $d2->source = 2;
        $hub->addDefinitions([$d1, $d2]);
        $this->assertEquals(1, $hub->get('item1'));
        $this->assertEquals(2, $hub->get('item2'));
    }

    public function testSet()
    {
        $hub = new Hub();
        $hub->addDefinition(new ItemDefinition('item1', 'initial_value'));
        $hub->set('item1', 'new_value');
        $this->assertEquals('new_value', $hub->get('item1'));
    }

    public function testRelation()
    {
        $addFunc = function (&$item2, $item1, $old) {
            $item2 += $item1 - ($old ?: 0);
        };
        $hub = new Hub();
        $d1 = new ItemDefinition('added', function () {
            return 1;
        });
        $d2 = new ItemDefinition('final', 10);

        $rel1 = new RelationDefinition('final', 'added', $addFunc);
        $hub->addDefinitions([$d1, $d2, $rel1]);

        # assert relation works
        $this->assertEquals(11, $hub->get('final'));

        # assert relation works when changing target value
        $this->assertEquals(101, $hub->set('final', 100)->get('final'));

        # assert relation works when changing source value
        $this->assertEquals(100 + 2, $hub->set('added', 2)->get('final'));
        $this->assertEquals(2, $hub->get('added'));

        # assert 2 level rel. works when added later
        $d3 = new ItemDefinition('added2', 3);
        $rel2 = new RelationDefinition('added', 'added2', $addFunc);
        $hub->addDefinitions([$d3, $rel2]);

        # added2 = 3
        # added = (2:src + 3:added2) = 5
        # final = 100:src + 5:added = 105
        $this->assertEquals(5, $hub->get('added'));
        $this->assertEquals(100 + (2 + 3), $hub->get('final'));

        # assert 2 level rel. works when changing value
        $hub->set('added2', -10);
        # added2 = -10
        # added = 5:prev -10:added2 - 3:added2_prev = -8
        # final = 100:src8 - 8:added = 92
        $this->assertEquals(100 + (2 + (-10)), $hub->get('final'));
        $this->assertEquals(-8, $hub->get('added'));
    }

    public function testEmptyRelationSource()
    {

        $hub = new Hub();
        $d1 = new ItemDefinition('item', 2);
        $rel = new RelationDefinition('item', null, function (&$val) {
            $val++;
        });
        $hub
            ->addDefinition($d1)
            ->addDefinition($rel);
        $this->assertEquals(2 + 1, $hub->get('item'));
        $this->assertEquals(3 + 1, $hub->set('item', 3)->get('item'));
    }

    public function testEmptyRelationTarget()
    {
        $hub = new Hub();
        $d1 = new ItemDefinition('item', 2);
        $i = 1;
        $rel = new RelationDefinition(null, 'item', function ($target, $src) use (&$i) {
            $this->assertEquals(null, $target);
            $i += $src;
        });
        $hub
            ->addDefinition($d1)
            ->addDefinition($rel);

        $this->assertEquals(2, $hub->get('item'));
        $this->assertEquals(1 + 2, $i);
    }

    public function testSetReadonly()
    {
        $this->expectException(ReadonlyException::class);
        $hub = new Hub();
        $item = new ItemDefinition('item', 2, true);
        $hub->addDefinition($item);
        $hub->set('item', 3);
    }

    public function testRemoveNonExisting()
    {
        $this->expectException(NotFoundException::class);
        $hub = new Hub();
        $hub->remove('non-existent-id');
    }

    public function testRemove()
    {
        $hub = new Hub();
        $hub->builder()->define('a', 'A');
        $hub->remove('a');
        $this->assertFalse($hub->has('a'));
    }

    public function testRemoveUsed()
    {
        $hub = new Hub();
        $hub->builder()
            ->defineMany([
                'a' => 'A',
                'b' => 'B',
                'c' => 'C'
            ])
            ->define('concat')
            ->uses(['a', 'b', 'c'], function (&$t, $s) {
                $t .= $s;
            });
        $this->assertEquals('ABC', $hub->get('concat'));
        $this->expectException(CanNotRemoveDefinitionException::class);
        $hub->remove('c');
    }

    public function testRemoveThatUses()
    {
        $hub = new Hub();
        $hub->builder()
            ->defineMany([
                'a' => 'A',
                'b' => 'B',
                'c' => 'C'
            ])
            ->define('concat')
            ->uses(['a', 'b', 'c'], function (&$t, $s) {
                $t .= $s;
            });
        $hub->remove('concat');
        $this->assertFalse($hub->has('concat'));
        // @todo will fail
        //$hub->set('b', 'B2');
    }
}
