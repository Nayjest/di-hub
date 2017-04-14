<?php

namespace Nayjest\DI\Test\Integration;

use Nayjest\DI\Hub;
use Nayjest\DI\Definition\ItemDefinition;
use Nayjest\DI\HubInterface;
use Nayjest\DI\SubHub;
use PHPUnit\Framework\TestCase;

/**
 * Currently this test fails
 * Remove 'abstract' keyword to use this test.
 */
class SubSubHubTestDescBuild extends TestCase
{
    /** @var HubInterface */
    protected $h1;
    /** @var HubInterface */
    protected $h2;
    /** @var HubInterface */
    protected $h3;
    /** @var HubInterface */
    protected $h4;

    /** @var HubInterface */
    protected $sh1;

    /** @var HubInterface */
    protected $sh2;

    /** @var HubInterface */
    protected $sh3;

    protected $dotSrc;

    public function setUp()
    {
        $this->h1 = new Hub;
        $this->h2 = new Hub;
        $this->h3 = new Hub;
        $this->h4 = new Hub;
        $this->h3->addDefinition(new ItemDefinition('eb', 'eb'));
        $this->h3->addDefinition(new ItemDefinition('e', 'e'));
        $this->h1->addDefinition(new ItemDefinition('ib', 'ib'));
        $this->h1->addDefinition(new ItemDefinition('i', 'i'));


        $this->sh3 = new SubHub('h3.', $this->h3, $this->h4);
        $this->sh2 = new SubHub('h2.',$this->h2,$this->sh3);
        $this->sh1 = new SubHub('h1.',$this->h1,$this->sh2);

        $this->sh1->addDefinition(new ItemDefinition('ia', 'ia'));
        $this->sh3->addDefinition(new ItemDefinition('ea', 'ea'));
        $this->dotSrc = function(&$t, $s, $p) {
            if ($p){
                $t = str_replace(".$p", '', $t);
            }
            if ($s) {
                $t .= ".$s";
            }
        };

    }

    protected function assertAll($key, $expected = null)
    {
        if ($expected === null) {
            $expected = $key;
        }
        $this->assertEquals($expected, $this->sh1->get($key));
        $this->assertEquals($expected, $this->sh2->get("h1.$key"));
        $this->assertEquals($expected, $this->sh3->get("h2.h1.$key"));
        $this->assertEquals($expected, $this->h4->get("h3.h2.h1.$key"));
    }

    public function testRead()
    {
        $this->assertAll('ib');
        $this->assertAll('ia');
    }



    public function test1()
    {

        $this->sh3->builder()
            ->defineRelation('h2.h1.i', 'e', $this->dotSrc);

        $this->assertAll('i', "i.e");

        $this->sh3->set('e', 'e2');
        $this->assertAll('i', "i.e2");

        $this->h4->set('h3.e', 'e3');
        $this->assertAll('i', "i.e3");

        $this->sh2->set('h1.i', 'i2');
        $this->assertAll('i', "i2.e3");

        $this->sh3->builder()
            ->defineRelation('h2.h1.ia', 'eb', $this->dotSrc);

        $this->assertAll('ia', "ia.eb");

        $this->sh3->set('eb', 'e2');
        $this->assertAll('ia', "ia.e2");

        $this->h4->set('h3.eb', 'e3');
        $this->assertAll('ia', "ia.e3");

        $this->sh2->set('h1.ia', 'ia2');
        $this->assertAll('ia', "ia2.e3");
    }

    public function test2()
    {
        $this->sh3->builder()
            ->defineRelation('e', 'h2.h1.i', $this->dotSrc);
        $this->assertEquals('e.i', $this->h4->get('h3.e'));
    }

    public function test3()
    {
        $this->sh3->builder()
            ->defineRelation('h2.h1.ib', 'ea', $this->dotSrc)
            ->defineRelation('ea', 'h2.h1.ia', $this->dotSrc)
            ->defineRelation('h2.h1.ia', 'eb', $this->dotSrc);
        $this->assertAll('ib', 'ib.ea.ia.eb');

        $this->sh1->set('ib', 'ib2');
        $this->assertAll('ib', 'ib2.ea.ia.eb');
        $this->sh3->set('eb', 'eb2');
        $this->assertAll('ib', 'ib2.ea.ia.eb2');
        $this->h4->set('h3.eb', 'eb3');
        $this->assertAll('ib', 'ib2.ea.ia.eb3');

//        // @todo FAIL
//        $this->sh1->builder()
//            ->define('in','in')
//            ->usedBy('ib', $this->dotSrc)
//            ->define('in2','in2')
//            ->usedBy('ib', $this->dotSrc)
//        ;
//        $this->assertAll('ib', 'ib2.ea.ia.eb3.in.in2');
//        // actual result
//        //-'ib2.ea.ia.eb3.in.in2'
//        //+'ib2.ea.ia.eb3.in.ea.ia.eb3.in2.ea.ia.eb3'
//        // looks like it's not ok
    }
}
