<?php

namespace Nayjest\DI\Test\Integration;

use Nayjest\DI\Hub;
use Nayjest\DI\HubInterface;
use Nayjest\DI\ItemDefinition;
use Nayjest\DI\RelationDefinition;
use Nayjest\DI\SubHub;
use PHPUnit\Framework\TestCase;

class SubHubTest extends TestCase
{
    /** @var  HubInterface */
    private $hub;
    /** @var  HubInterface */

    public function setUp()
    {
        $this->hub = new Hub();
    }

    public function testRegister()
    {
        $id = 'subhub';
        new SubHub($id, new Hub, $this->hub);
        $this->assertTrue($this->hub->has($id));
    }

    public function testAccessPredefined()
    {
        $id = 'subhub';
        $internalHub = new Hub;
        $internalHub->builder()->define('item', 'value');
        $subHub = new SubHub($id, $internalHub);
        $this->assertTrue($subHub->has("item"));
        $this->assertEquals('value', $subHub->get("item"));

        $subHub->register($this->hub);
        $this->assertTrue($this->hub->has("$id.item"));
        $this->assertTrue($subHub->has("item"));
        $this->assertEquals('value', $this->hub->get("$id.item"));
        $this->assertEquals('value', $subHub->get("item"));

        $this->hub->set("$id.item", 'valueFromExternalHub');
        $this->assertEquals('valueFromExternalHub', $this->hub->get("$id.item"));

        $subHub->set("item", 'valueFromSubHub');
        $this->assertEquals('valueFromSubHub', $this->hub->get("$id.item"));

        $subHub->set("item", 'valueFromInternalHub');
        $this->assertEquals('valueFromInternalHub', $this->hub->get("$id.item"));
    }

    public function testSubHubInternalPredefining()
    {
        $subHub = new SubHub('s', $hub = new Hub);
        $subHub->builder()->define('item', 'val');
        $this->assertEquals('val', $subHub->get("item"));
        $this->assertEquals('val', $hub->get("item"));
        $subHub->addDefinitions([
            new ItemDefinition('item2', null),
            new RelationDefinition('item2', 'item', function(&$target, $src){
                $target = "$src!";
            })
        ]);
        $this->assertEquals('val!', $subHub->get("item2"));
        $this->assertEquals('val!', $hub->get("item2"));

        $subHub->set('item', 'val2');
        $this->assertEquals('val2!', $subHub->get("item2"));
        $this->assertEquals('val2!', $hub->get("item2"));
    }

    public function testSubHubDelegation()
    {
        $subHub = new SubHub('s', $hub = new Hub);
        $subHub->register($this->hub);
        $subHub->builder()->define('item', 'val');
        $this->assertEquals('val', $subHub->get("item"));
        $this->assertEquals('val', $this->hub->get("s.item"));
        $subHub->addDefinitions([
            new ItemDefinition('item2', null),
            new RelationDefinition('item2', 'item', function(&$target, $src){
                $target = "$src!";
            })
        ]);
        $this->assertEquals('val!', $subHub->get("item2"));
        $this->assertEquals('val!',  $this->hub->get("s.item2"));

        $subHub->set('item', 'val2');
        $this->assertEquals('val2!', $subHub->get("item2"));
        $this->assertEquals('val2!',  $this->hub->get("s.item2"));


        $this->hub->set('s.item', 'val3');
        $this->assertEquals('val3!', $subHub->get("item2"));
        $this->assertEquals('val3!',  $this->hub->get("s.item2"));
    }

    public function testInternalRelations()
    {
        $id = 'subhub';
        $internalHub = new Hub;
        $internalHub
            ->builder()
            ->define('item', 'val')
            ->usedBy('title', function(&$target, $src) {
                $target = ucfirst($src);
            })
            ->define('title', null, true);

        $subHub = new SubHub($id, $internalHub);
        $subHub->register($this->hub);

        $this->hub->set("$id.item", 'val1');
        $this->assertEquals('Val1', $this->hub->get("$id.title"));

        $subHub->set("item", 'val2');
        $this->assertEquals('Val2', $this->hub->get("$id.title"));

        $internalHub->set("item", 'val3');
        $this->assertEquals('Val3', $this->hub->get("$id.title"));
    }

    public function testExternalDependency()
    {
        $id = 'subhub';
        $internalHub = new Hub;
        $internalHub->builder()->define('item', 'val');
        $subHub = new SubHub($id, $internalHub);
        $subHub->register($this->hub);

        $this->hub->builder()
            ->define('final', null, true)
            ->uses(
                "$id.item",
                function(&$target, $src) {
                    $target = $src . '!';
                }
            );
        $this->assertEquals('val!', $this->hub->get("final"));

        $this->hub->set("$id.item", 'val1');
        $this->assertEquals('val1!', $this->hub->get("final"));

        $subHub->set("item", 'val2');
        $this->assertEquals('val2!', $this->hub->get("final"));

        /** @limitation
         * Following code will not work because internal hub don't tracks external dependencies.
         * Therefore always replace internal hub instance to SubHub wrapper where it used.
         */
        # $internalHub->set("item", 'val3');
        # $this->assertEquals('Val3!', $this->hub->get("final"));
    }

    public function testExternalOnInternalDependency()
    {
        $id = 'subhub';
        $internalHub = new Hub;
        $internalHub
            ->builder()
            ->define('item', 'val')
            ->usedBy('title', function(&$target, $src) {
                $target = ucfirst($src);
            })
            ->define('title', null, true);

        $subHub = new SubHub($id, $internalHub);
        $subHub->register($this->hub);

        $this->hub->builder()
            ->define('final', null, true)
            ->uses(
            "$id.title",
            function(&$target, $src) {
                $target = $src . '!';
            }
        );
        $this->assertEquals('Val!', $this->hub->get("final"));

        $this->hub->set("$id.item", 'val1');
        $this->assertEquals('Val1!', $this->hub->get("final"));

        $subHub->set("item", 'val2');
        $this->assertEquals('Val2!', $this->hub->get("final"));
    }

    public function testInternalOnExternalDependency()
    {
        $id = 'subhub';
        $internalHub = new Hub;
        $internalHub
            ->builder()
            ->define('item', 'val');
        $subHub = new SubHub($id, $internalHub);
        $subHub->register($this->hub);

        $this->hub->builder()
            ->define('external', '1')
            ->usedBy(
                "$id.item",
                function(&$target, $src) {
                    $target .= ".$src";
                }
            );
        $this->assertEquals('val.1', $this->hub->get("$id.item"));
        $this->assertEquals('val.1', $subHub->get("item"));
        $this->assertEquals('val.1', $internalHub->get("item"));
        $this->hub->set('external', 2);
        $this->assertEquals('val.1.2', $this->hub->get("$id.item"));
        $this->assertEquals('val.1.2', $subHub->get("item"));
        $this->assertEquals('val.1.2', $internalHub->get("item"));
        $this->hub->set("$id.item", 'updated');
        $this->assertEquals('updated.2', $this->hub->get("$id.item"));
        $this->assertEquals('updated.2', $subHub->get("item"));
        $this->assertEquals('updated.2', $internalHub->get("item"));
        $internalHub->set("item", 'updated2');
        $this->assertEquals('updated2.2', $this->hub->get("$id.item"));
        $this->assertEquals('updated2.2', $subHub->get("item"));
        $this->assertEquals('updated2.2', $internalHub->get("item"));
    }
}