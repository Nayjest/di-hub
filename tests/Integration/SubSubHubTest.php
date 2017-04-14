<?php

namespace Nayjest\DI\Test\Integration;

use Nayjest\DI\Hub;
use Nayjest\DI\Definition\ItemDefinition;
use Nayjest\DI\SubHub;
use PHPUnit\Framework\TestCase;

/**
 * Currently this test fails
 * Remove 'abstract' keyword to use this test.
 */
class SubSubHubTest extends TestCase
{
    public function testConstructFromBottomThenRead()
    {

        $h1 = new Hub;
        $h2 = new Hub;
        $h3 = new Hub;
        $h4 = new Hub;

        $h1->addDefinition(new ItemDefinition('item1', 'h1_item1'));

        $sh1 = new SubHub('h1.', $h1, $h2);


        $sh2 = new SubHub('h2.', $h2, $h3);
        /**
         * @todo When building sub-hub chain from bottom to top, multiple parents(ext. hubs) may cause errors
         * @see \Nayjest\DI\SubHub::replaceExternalHubsToThis()
         * Test fails when uncommenting next line:
         */
        // new SubHub('not_used', $h2,new Hub());

        $sh3 = new SubHub('h3.', $h3, $h4);

        $this->assertEquals('h1_item1', $sh1->get('item1'));
        $this->assertEquals('h1_item1', $sh2->get('h1.item1'));
        $this->assertEquals('h1_item1', $sh3->get('h2.h1.item1'));
        $this->assertEquals('h1_item1', $h4->get('h3.h2.h1.item1'));

        $sh1->addDefinition(new ItemDefinition('item2', 'h1_item2'));
        $this->assertEquals('h1_item2', $sh2->get('h1.item2'));
        $this->assertEquals('h1_item2', $sh3->get('h2.h1.item2'));
    }

    public function testConstructFromBottomThenAddAndRead()
    {

        $h1 = new Hub;
        $h2 = new Hub;
        $h3 = new Hub;
        $h4 = new Hub;

        $sh1 = new SubHub('h1.', $h1, $h2);
        $sh2 = new SubHub('h2.', $h2, $h3);
        $sh3 = new SubHub('h3.', $h3, $h4);

        $sh1->addDefinition(new ItemDefinition('item1', 'h1_item1'));

        $this->assertEquals('h1_item1', $sh1->get('item1'));
        $this->assertEquals('h1_item1', $sh2->get('h1.item1'));
        $this->assertEquals('h1_item1', $sh3->get('h2.h1.item1'));
        $this->assertEquals('h1_item1', $h4->get('h3.h2.h1.item1'));
        $sh1->addDefinition(new ItemDefinition('item2', 'h1_item2'));
        $this->assertEquals('h1_item2', $h2->get('h1.item2'));
        $this->assertEquals('h1_item2', $h3->get('h2.h1.item2'));
    }

    public function testConstructFromBottomThenModify()
    {

        $h1 = new Hub;
        $h2 = new Hub;
        $h3 = new Hub;
        $h4 = new Hub;

        $h1->addDefinition(new ItemDefinition('item1', 'h1_item1'));

        $sh1 = new SubHub('h1.', $h1, $h2);
        $sh2 = new SubHub('h2.', $h2, $h3);
        $sh3 = new SubHub('h3.', $h3, $h4);

        $h4->set('h3.h2.h1.item1', 'injected_via_h4');

        $this->assertEquals('injected_via_h4', $sh1->get('item1'));
        $this->assertEquals('injected_via_h4', $sh2->get('h1.item1'));
        $this->assertEquals('injected_via_h4', $sh3->get('h2.h1.item1'));
        $this->assertEquals('injected_via_h4', $h4->get('h3.h2.h1.item1'));


        $sh3->set('h2.h1.item1', 'injected_via_h3');

        $this->assertEquals('injected_via_h3', $sh1->get('item1'));
        $this->assertEquals('injected_via_h3', $sh2->get('h1.item1'));
        $this->assertEquals('injected_via_h3', $sh3->get('h2.h1.item1'));
        $this->assertEquals('injected_via_h3', $h4->get('h3.h2.h1.item1'));

        $sh2->set('h1.item1', 'injected_via_h2');

        $this->assertEquals('injected_via_h2', $sh1->get('item1'));
        $this->assertEquals('injected_via_h2', $sh2->get('h1.item1'));
        $this->assertEquals('injected_via_h2', $sh3->get('h2.h1.item1'));
        $this->assertEquals('injected_via_h2', $h4->get('h3.h2.h1.item1'));
    }

    public function testConstructFromTopThenRead()
    {

        $h1 = new Hub;
        $h2 = new Hub;
        $h3 = new Hub;
        $h4 = new Hub;

        $h1->addDefinition(new ItemDefinition('item1', 'h1_item1'));

        $sh3 = new SubHub('h3.', $h3, $h4);
        $sh2 = new SubHub('h2.', $h2, $sh3);
        $sh1 = new SubHub('h1.', $h1, $sh2);

        $this->assertEquals('h1_item1', $sh1->get('item1'));
        $this->assertEquals('h1_item1', $sh2->get('h1.item1'));
        $this->assertEquals('h1_item1', $sh3->get('h2.h1.item1'));
        $this->assertEquals('h1_item1', $h4->get('h3.h2.h1.item1'));
    }

    public function testConstructFromTopThenDefineAndRead()
    {
        $h1 = new Hub;
        $h2 = new Hub;
        $h3 = new Hub;
        $h4 = new Hub;

        $sh3 = new SubHub('h3.', $h3, $h4);
        $sh2 = new SubHub('h2.', $h2, $sh3);
        $sh1 = new SubHub('h1.', $h1, $sh2);

        $sh1->addDefinition(new ItemDefinition('i', 'v'));
        $this->assertEquals('v', $sh2->get('h1.i'));
        $this->assertEquals('v', $sh3->get('h2.h1.i'));
        $this->assertEquals('v', $h4->get('h3.h2.h1.i'));
    }
}
