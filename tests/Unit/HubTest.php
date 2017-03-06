<?php

namespace Nayjest\DI\Test\Unit;

use Nayjest\DI\Definition\DefinitionInterface;
use Nayjest\DI\Definition\RelationDefinition;
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
    protected function mockItemDefinition($id, $value = null)
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
        $result = $this->hub->addDefinition($this->mockItemDefinition('test-id'));
        $this->assertEquals($this->hub, $result, 'Method "adDefinition" not supports method chaining');
        $this->assertTrue($this->hub->has('test-id'));
    }

    public function testConstructWithDefinitions()
    {
        $hub = new Hub([
            $this->mockItemDefinition('def1'),
            $this->mockItemDefinition('def2')
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

    public function testAddExistingItemDefinition()
    {
        $this->expectException(AlreadyDefinedException::class);
        $this->hub
            ->addDefinition($this->mockItemDefinition('test-id'))
            ->addDefinition($this->mockItemDefinition('test-id'));
    }

    public function testAddExistingRelationDefinition()
    {
        /** @var PHPUnit_Framework_MockObject_MockObject|RelationDefinition $relation */
        $relation = $this->createMock(RelationDefinition::class);
        // should be ok
        $this->hub->addDefinition($relation);

        // and now should fail
        $this->expectException(AlreadyDefinedException::class);
        $this->hub->addDefinition($relation);
    }

    public function testImmediateRelationProcessing()
    {
        $this->hub->addDefinitions([
            $this->mockItemDefinition('src', 'initial_src_val'),
            $this->mockItemDefinition('initialized_target', 'initial_target_val'),
        ]);
        // target should be initialized
        $res = $this->hub->get('initialized_target');
        $this->assertEquals('initial_target_val', $res);

        /** @var PHPUnit_Framework_MockObject_MockObject|RelationDefinition $relation */
        $relation = $this->createMock(RelationDefinition::class);
        $relation->source = 'src';
        $relation->target = 'initialized_target';
        $callLog = [];
        $relation->handler = function(&$target, $source, $prev) use (&$callLog) {
            $callLog[] = "target:$target|src:$source|prev:$prev";
        };

        $this->hub->addDefinition($relation);
        // should be called immediately
        $this->assertEquals(1, count($callLog));
        $this->assertEquals("target:initial_target_val|src:initial_src_val|prev:", $callLog[0]);

        # ADDITIONAL TESTS
        # (not related directly to Immediate-Relation-Processing)
        # todo: move it to another test

        $this->hub->set('src', 'new_src_val');
        // should be called again
        $this->assertEquals(2, count($callLog));
        $this->assertEquals("target:initial_target_val|src:new_src_val|prev:initial_src_val", $callLog[1]);

        $this->hub->get('src');
        $this->hub->get('initialized_target');
        // should not be called again
        $this->assertEquals(2, count($callLog));


        $this->hub->set('initialized_target', 'new_target_val');
        // handler not called again
        // todo: review this behavior, possible bugs
        $this->assertEquals(2, count($callLog));

        $res = $this->hub->get('initialized_target');
        $this->assertEquals('new_target_val', $res);
        $this->assertEquals(3, count($callLog));
        // prev = null
        // todo: review this behavior, possible bugs
        $this->assertEquals(
            "target:new_target_val|src:new_src_val|prev:",
            $callLog[2]
        );
    }

    public function testGet()
    {
        $definition = $this->mockItemDefinition('test-id', 'test-val');
        $this->hub->addDefinition($definition);
        $this->assertEquals('test-val', $this->hub->get('test-id'));
    }
}