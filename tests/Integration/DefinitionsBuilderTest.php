<?php

namespace Nayjest\DI\Test\Integration;

use Nayjest\DI\Hub;
use PHPUnit\Framework\TestCase;

class DefinitionsBuilderTest extends TestCase
{
    public function test()
    {
        $hub = new Hub();
        $add = function (&$t, $s, $prev) {
            $t += $s - ($prev ?: 0);
        };
        $hub->builder()
            ->define('intSum')
            ->uses(['int1', 'int2'], $add)
            ->define('int1', 1)
            ->define('int2', 2)
        ;
        $this->assertEquals(3, $hub->get('intSum'));
    }
}