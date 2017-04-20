<?php

namespace Nayjest\DI\Test\Integration;

use Nayjest\DI\Definition\Item;
use Nayjest\DI\Definition\Relation;
use Nayjest\DI\Hub;
use PHPUnit\Framework\TestCase;

class MultiSourceRelationTest extends TestCase
{
    public function test()
    {
        $hub = new Hub([
            new Item('src1', '1'),
            new Item('src2', '2'),
            new Item('src3', '3'),
            new Item('target', 'target'),
            new Relation('target', ['src1', 'src3', 'src2'], function (&$target, $src1, $src3, $src2) {
                $target = 'target.' . join(',', [$src1, $src2, $src3]);
            })
        ]);
        $this->assertEquals('target.1,2,3', $hub->get('target'));
        $hub->addDefinitions([
            new Item('subsrc', 's'),
            new Relation('src2', 'subsrc', function (&$t, $s, $prev) {
                if ($prev) $t = str_replace(':' . $prev, '', $t);
                if ($s) {
                    $t .= ':' . $s;
                }
            })
        ]);
        $this->assertEquals('target.1,2:s,3', $hub->get('target'));
        $hub->set('subsrc', 's2');
        $this->assertEquals('target.1,2:s2,3', $hub->get('target'));
    }
}
