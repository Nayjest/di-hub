<?php

namespace Nayjest\DI\Test\Unit;

use Nayjest\DI\Definition\DefinitionInterface;
use Nayjest\DI\Exception\UnsupportedDefinitionTypeException;
use Nayjest\DI\Definition\ItemDefinition;
use Nayjest\DI\Exception\AlreadyDefinedException;
use Nayjest\DI\Exception\NotFoundException;
use Nayjest\DI\Hub;
use Nayjest\DI\HubInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;

class HubTest extends TestCase
{
    /** @var  HubInterface */
    private $hub;

    public function setUp()
    {
        $this->hub = new Hub;
    }

    public function testNotHas()
    {
        $this->assertFalse($this->hub->has('non-existent-item'), '$hub->has($id) returns true for non-existent $id');
    }

    public function testGetNonExistent()
    {
        $this->expectException(NotFoundException::class);
        $this->hub->get('something');
    }

    public function testSetNonExistent()
    {
        $this->expectException(NotFoundException::class);
        $this->hub->set('something', 'value');
    }

    /**
     * @param string $id
     * @param null $value
     * @return PHPUnit_Framework_MockObject_MockObject|\Nayjest\DI\Definition\ItemDefinition
     */
    protected function mockDefinition($id, $value = null)
    {
        /** @var PHPUnit_Framework_MockObject_MockObject|ItemDefinition $definition */
        $definition = $this->createMock(ItemDefinition::class);
        $definition->id = $id;
        $definition->relations = [];
        if ($value!== null) {
            $definition->source = function() use($value) {
                return $value;
            };
        }
        return $definition;
    }

    public function testAddDefinition()
    {
        $result = $this->hub->addDefinition($this->mockDefinition('test-id'));
        $this->assertEquals($this->hub, $result, 'Method "adDefinition" not supports method chaining');
        $this->assertTrue($this->hub->has('test-id'));
    }

    public function testConstructWithDefinitions()
    {
        $hub = new Hub([
            $this->mockDefinition('def1'),
            $this->mockDefinition('def2')
        ]);
        $this->assertTrue($hub->has('def1'));
        $this->assertTrue($hub->has('def2'));
    }

    public function testUnsupportedDefinition()
    {
        /** @var DefinitionInterface $definition */
        $definition = $this->createMock(DefinitionInterface::class);

        $this->expectException(UnsupportedDefinitionTypeException::class);
        $this->hub->addDefinition($definition);
    }

    public function testAddExistingDefinition()
    {
        $this->expectException(AlreadyDefinedException::class);
        $this->hub
            ->addDefinition($this->mockDefinition('test-id'))
            ->addDefinition($this->mockDefinition('test-id'));
    }

    public function testGet()
    {
        $definition = $this->mockDefinition('test-id', 'test-val');
        $this->hub->addDefinition($definition);
        $this->assertEquals('test-val', $this->hub->get('test-id'));
    }
}