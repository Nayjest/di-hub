<?php

namespace Nayjest\DI\Test\Integration;


use Nayjest\DI\Definition\Item;
use Nayjest\DI\Definition\Value;
use Nayjest\DI\Hub;
use PHPUnit\Framework\TestCase;

class ItemTest extends TestCase
{

    public function testSimpleValue()
    {
        $hub = new Hub([
            new Item('item', 'val')
        ]);
        $this->assertEquals('val', $hub->get('item'));
    }

    public function testOneSrc()
    {
        $log = '';
        $hub = new Hub([
            new Item('s1', 'v1'),
            new Item('t', 's1', function (&$t, $s1) use (&$log) {
                if (!$t) {
                    $log .= '[construct]';
                    $t = (object)['s' => $s1];
                    $t->s = $s1;

                } else {
                    $log .= "[update $s1]";
                    $t->s = $s1;
                }
            })
        ]);

        $this->assertEquals('v1', $hub->get('t')->s);
        $this->assertEquals('[construct]', $log);
        $hub->set('s1', 'v2');
        $this->assertEquals('v2', $hub->get('t')->s);
        $this->assertEquals('[construct][update v2]', $log);
    }

    public function testMultipleSrc()
    {
        $log = '';
        $hub = new Hub([
            new Item('s1', 'v1'),
            new Item('s2', 'val_changed_later'),
            new Value('s3', 'v3'),
            new Item('t', ['s1', 's2', 's3', 's4'], function (&$t, $s1, $s2, $s3, $s4) use (&$log) {
                if (!$t) {
                    $log .= '[construct]';
                    $t = (object)compact('s1', 's2', 's3', 's4');
                } else {
                    if ($t->s1 !== $s1) $t->s1 = $s1;
                    if ($t->s2 !== $s2) $t->s2 = $s2;
                    if ($t->s3 !== $s3) $t->s3 = $s3;
                    if ($t->s4 !== $s4) $t->s4 = $s4;
                    $log .= '[update]';
                }
            })
        ]);
        $hub->get('s1');
        $hub->set('s2', 'v2');
        $hub->addDefinition(new Value('s4', 'v4'));
        $t = $hub->get('t');
        $this->assertEquals('v1', $t->s1);
        $this->assertEquals('v2', $t->s2);
        $this->assertEquals('v3', $t->s3);
        $this->assertEquals('v4', $t->s4);
        $this->assertEquals('[construct]', $log);
        $this->assertEquals('v1', $t->s1);
        $hub->set('s1', 'v1b');
        $this->assertEquals('v1b', $t->s1);
        $hub->set('s2', 'v2b');
        $this->assertEquals('v2b', $t->s2);
        $this->assertEquals('[construct][update][update]', $log);
    }
}