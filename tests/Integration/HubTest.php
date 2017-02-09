<?php

namespace Nayjest\DI\Test\Integration;

use Nayjest\DI\Definition;
use Nayjest\DI\Hub;
use Nayjest\DI\Relation;
use PHPUnit\Framework\TestCase;

class HubTest extends TestCase
{
    public function testSimple()
    {
        $hub = new Hub();
        $d1 = new Definition('item1');
        $d1->source = function () {
            return 1;
        };
        $d2 = new Definition('item2');
        $d2->source = 2;
        $hub->addDefinitions([$d1, $d2]);
        $this->assertEquals(1, $hub->get('item1'));
        $this->assertEquals(2, $hub->get('item2'));
    }

    public function testSet()
    {
        $hub = new Hub();
        $hub->addDefinition(new Definition('item1', 'initial_value'));
        $hub->set('item1', 'new_value');
        $this->assertEquals('new_value', $hub->get('item1'));
    }

    public function testRelation()
    {
        $addFunc = function (&$item2, $item1, $old) {
            $item2 += $item1 - ($old ?: 0);
        };
        $hub = new Hub();
        $d1 = new Definition('added', function () {
            return 1;
        });
        $d2 = new Definition('final', 10);

        $d2->relations[] = new Relation('final', 'added', $addFunc);
        $hub->addDefinitions([$d1, $d2]);

        # assert relation works
        $this->assertEquals(11, $hub->get('final'));

        # assert relation works when changing target value
        $this->assertEquals(101, $hub->set('final', 100)->get('final'));

        # assert relation works when changing source value
        $this->assertEquals(100+2, $hub->set('added', 2)->get('final'));

        $d3 = new Definition('added_to_added', 3);
        $d3->relations[] = new Relation('added', 'added_to_added', $addFunc);
        $hub->addDefinition($d3);
        $this->assertEquals(100 + 2 + 3, $hub->get('final'));
    }

    public function testEmptyRelation()
    {

        $hub = new Hub();
        $d1 = new Definition('item', 2);
        $hub->addDefinition($d1);
        $d1->relations[] = new Relation('item', null, function(&$val){
            $val++;
        });

        $this->assertEquals(2+1, $hub->get('item'));
        $this->assertEquals(3+1, $hub->set('item', 3)->get('item'));
    }
}