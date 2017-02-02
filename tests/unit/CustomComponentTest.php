<?php

namespace Nayjest\DI\Test;

use Nayjest\DI\CustomComponent;
use Nayjest\DI\Hub;
use PHPUnit\Framework\TestCase;

class CustomComponentTest extends TestCase
{
    /** @var  Hub */
    private $hub;
    /** @var  CustomComponent */
    private $c;
    public function setUp()
    {
        $this->hub = new Hub();
        $this->c = new CustomComponent();
    }

    protected function register()
    {
        $this->hub->add($this->c);
        return $this;
    }

    protected function assertEq($key, $value)
    {
        $this->assertEquals($value, $this->hub->get($key));
        return $this;
    }

    public function testSimpleGet()
    {
        $this->c
            ->define('prop1', 1)
            ->define('secondProperty', 2)
        ;
        $this
            ->register()
            ->assertEq("prop1", 1)
            ->assertEq("secondProperty", 2);
    }

    public function testGetSet()
    {
        $this->c
            ->define('prop1')
            ->withSetter()
        ;
        $this->c->setProp1(1);
        $this
            ->register()
            ->assertEq("prop1", 1);
        $this->c->setProp1(2);
        $this->assertEq("prop1", 2);
    }

    public function testSetWithoutSetter()
    {
        $this->c->define('val', 1);
        $this->register();
        $hasException = false;
        try {
            $this->hub->set('val', 2);
        } catch (\Exception $e) {
            $hasException = true;
        }
        $this->assertEquals(true, $hasException);
    }

    public function testSetUsingHub()
    {
        $this->c->define('val')->withSetter();
        $this->register();
        $this->hub->set('val', 2);
        $this->assertEq('val', 2);
        $this->hub->set('val', 3);
        $this->assertEq('val', 3);
    }

    public function testCustomSetter()
    {
        $this->c
            ->define('prop1')
            ->withSetter(function($val) {
                return $val . '!';
            })
        ;
        $this->c->setProp1('1');

        $this
            ->register()
            ->assertEq("prop1", '1!');
        $this->c->setProp1('2');
        $this->assertEq("prop1", '2!');
    }

    public function testTracks()
    {
        $val1 = (object)['val' => 1];
        $val2 = (object)['val' => 2];
        $this->c
            ->define('val1', $val1)->withSetter()
            ->define('val2', $val2)->uses('val1', function($val2, $newVal1) {
                $val2->trackedVal = $newVal1->val;
            });
        $this->register();
        $this->assertEquals(1, $this->hub->get('val1')->val);
        $this->assertEquals(2, $this->hub->get('val2')->val);
        $this->assertEquals(1, $this->hub->get('val2')->trackedVal);
        $this->c->setVal1((object)['val' => 3]);
        $this->assertEquals(3, $this->hub->get('val2')->trackedVal);
    }

    public function testTrackBy()
    {
        $src = (object)['val' => 'initialSrc'];
        $receiver = (object)['trackedVal' => 'initialTrackedVal'];
        $this->c
            ->define('src', $src)
            ->usedBy('receiver', function($receiver, $src) {
                $receiver->trackedVal = $src->val;
            })
            ->withSetter()
            ->define('receiver', $receiver);
        $this->register();
        $this->assertEquals('initialSrc', $this->hub->get('src')->val);
        $this->assertEquals('initialSrc', $this->hub->get('receiver')->trackedVal);
        $this->c->setSrc((object)['val' => 'updatedSrc']);
        $this->assertEquals('updatedSrc', $this->hub->get('receiver')->trackedVal);
    }

    public function testTrackCollection()
    {
        $obj1 = (object)['val' => 1];
        $obj2 = (object)['val' => 2];
        $collection = (object)['items' => []];
        $injector = function($col, $newVal, $oldVal) {
            if (
                $oldVal
                && ($oldVal !== $newVal)
                && (($index = array_search($oldVal, $col->items, true)) !== false)
            ) {
                unset($col->items[$index]);
            }
            if ($newVal) {
                $col->items[] = $newVal;
            }
        };
        $this->c
            ->define('obj1', $obj1)
                ->usedBy('collection', $injector)
                ->withSetter()
            ->define('obj2', $obj2)
                ->usedBy('collection', $injector)
                ->withSetter()
            ->define('collection', $collection);
        $this->register();

        $this->c->setObj1((object)['val' => '1b']);
        $this->c->setObj2((object)['val' => '2b']);
        $this->c->setObj2((object)['val' => '2c']);
        // Removing object
        $this->c->setObj1(null);

        // Adding object to already initialized collection
        $res = $this->hub->get('collection');
        $obj3 = (object)['val' => 3];
        $this->hub->add((new CustomComponent())
            ->define('obj3', $obj3)
            ->usedBy('collection', $injector)
            ->withSetter()
        );
        $res = $this->hub->get('collection')->items;

        $this->assertEquals(2, count($res));
        foreach($res as $item) {
            $this->assertTrue(in_array($item->val, ['2c', 3], true));
        }
    }
}