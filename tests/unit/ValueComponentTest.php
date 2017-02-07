<?php
/**
 * Created by PhpStorm.
 * User: Vitaliy Stepanenko
 * Date: 02.02.2017
 * Time: 19:56
 */

namespace Nayjest\DI\Test;


use Nayjest\DI\Hub;
use Nayjest\DI\ValueComponent;
use PHPUnit\Framework\TestCase;

class ValueComponentTest extends TestCase
{
    /**
     * @var ValueComponent
     */
    private $component;

    /**
     * @var Hub
     */
    private $hub;

    public function setUp()
    {
        $this->component = new ValueComponent('prop', 'initial_val');
        $this->hub = new Hub();
    }

    public function testGet()
    {
        $this->assertTrue($this->component->getValue() === 'initial_val');
    }

    public function testSet()
    {
        $this->assertEquals($this->component, $this->component->setValue('new_val'));
        $this->assertEquals('new_val', $this->component->getValue());
    }

    public function testRegister()
    {
        $this->hub->add($this->component);

        $this->assertTrue($this->hub->has('prop'));
    }

    public function testGetFromHub()
    {
        $this->hub->add($this->component);
        $this->assertTrue($this->hub->get('prop') === 'initial_val');
    }

    public function testSetFromHub()
    {
        $this->hub->add($this->component);
        $this->assertTrue($this->hub->get('prop') === 'initial_val');
        $this->hub->set('prop', 'new_val');
        $this->assertTrue($this->hub->get('prop') === 'new_val');
    }

    public function testUses()
    {
        $this->component->uses('other_item', function(&$receiver, $other) {
            $receiver .= $other;
        });
        $this->hub
            ->add($this->component)
            ->add(new ValueComponent('other_item', '_updated'));
        $this->assertEquals('initial_val_updated', $this->hub->get('prop'));
        $this->hub->set('other_item', '_again');
        $this->assertEquals('initial_val_updated_again', $this->hub->get('prop'));
    }

    public function testUsedBy()
    {
        $prop2 = new ValueComponent('prop2', '_updated');
        $prop2->usedBy('prop', function(&$receiver, $other) {
            $receiver .= $other;
        });
        $this->hub
            ->add($this->component)
            ->add($prop2);
        $this->assertEquals('initial_val_updated', $this->hub->get('prop'));
        $this->hub->set('prop2', '_again');
        $this->assertEquals('initial_val_updated_again', $this->hub->get('prop'));
    }

    public function testUsesChain()
    {
        $this->component->uses('prop2', function(&$receiver, $other) {
            $receiver .= $other;
        });
        $this->hub
            ->add($this->component)
            ->add(new ValueComponent('prop2', '_updated'));

        $this->assertEquals('initial_val_updated', $this->hub->get('prop'));
        $this->hub->get('prop2');
        $this->hub->add((new ValueComponent('prop3', '_twice'))->usedBy('prop2', function(&$receiver, $other) {
            $receiver .= $other;
        }));
        $this->assertEquals('_updated_twice', $this->hub->get('prop2'));
        //$this->assertEquals('initial_val_updated_updated_twice', $this->hub->get('prop'));
    }
}
