<?php

namespace Nayjest\DI\Test\Integration;

use Nayjest\DI\Definition\Value;
use Nayjest\DI\Definition\Relation;
use Nayjest\DI\Hub;
use PHPUnit\Framework\TestCase;

class MultiSourceRelationTest extends TestCase
{
    public function test()
    {
        $hub = new Hub([
            new Value('src1', '1'),
            new Value('src2', '2'),
            new Value('src3', '3'),
            new Value('target', 'target'),
            new Relation('target', ['src1', 'src3', 'src2'], function (&$target, $src1, $src3, $src2) {
                $target = 'target.' . join(',', [$src1, $src2, $src3]);
            })
        ]);
        $this->assertEquals('target.1,2,3', $hub->get('target'));
        $hub->addDefinitions([
            new Value('subsrc', 's'),
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
