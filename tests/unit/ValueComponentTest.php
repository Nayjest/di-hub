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
}
