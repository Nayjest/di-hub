<?php

namespace Nayjest\DI\Test\Integration;

use Nayjest\DI\Hub;
use Nayjest\DI\Definition\ItemDefinition;
use Nayjest\DI\Definition\RelationDefinition;
use Nayjest\DI\SubHub;
use PHPUnit\Framework\TestCase;

class SubHubTest extends TestCase
{
    public function testRegister()
    {
        $externalHub = new Hub;
        $subHub = new SubHub('nested_', new Hub, $externalHub);
        $this->assertTrue($externalHub->has('nested_hub'));
        $this->assertEquals($subHub, $externalHub->get('nested_hub'));
    }

    public function testAccessPredefined()
    {
        $externalHub = new Hub;
        $id = 'subhub';
        $internalHub = new Hub;
        $internalHub->builder()->define('item', 'value');
        $subHub = new SubHub("$id.", $internalHub);
        $this->assertTrue($subHub->has("item"));
        $this->assertEquals('value', $subHub->get("item"));

        $subHub->register($externalHub);
        $this->assertTrue($externalHub->has("$id.item"));
        $this->assertTrue($subHub->has("item"));
        $this->assertEquals('value', $externalHub->get("$id.item"));
        $this->assertEquals('value', $subHub->get("item"));

        $externalHub->set("$id.item", 'valueFromExternalHub');
        $this->assertEquals('valueFromExternalHub', $externalHub->get("$id.item"));

        $subHub->set("item", 'valueFromSubHub');
        $this->assertEquals('valueFromSubHub', $externalHub->get("$id.item"));

        $subHub->set("item", 'valueFromInternalHub');
        $this->assertEquals('valueFromInternalHub', $externalHub->get("$id.item"));
    }

    public function testSubHubInternalPredefining()
    {
        $subHub = new SubHub('s.', $hub = new Hub);
        $subHub->builder()->define('item', 'val');
        $this->assertEquals('val', $subHub->get("item"));
        $this->assertEquals('val', $hub->get("item"));
        $subHub->addDefinitions([
            new ItemDefinition('item2', null),
            new RelationDefinition('item2', 'item', function (&$target, $src) {
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
        $externalHub = new Hub();
        $subHub = new SubHub('s.', $hub = new Hub);
        $subHub->register($externalHub);
        $subHub->builder()->define('item', 'val');
        $this->assertEquals('val', $subHub->get("item"));
        $this->assertEquals('val', $externalHub->get("s.item"));
        $subHub->addDefinitions([
            new ItemDefinition('item2', null),
            new RelationDefinition('item2', 'item', function (&$target, $src) {
                $target = "$src!";
            })
        ]);
        $this->assertEquals('val!', $subHub->get("item2"));
        $this->assertEquals('val!', $externalHub->get("s.item2"));

        $subHub->set('item', 'val2');
        $this->assertEquals('val2!', $subHub->get("item2"));
        $this->assertEquals('val2!', $externalHub->get("s.item2"));


        $externalHub->set('s.item', 'val3');
        $this->assertEquals('val3!', $subHub->get("item2"));
        $this->assertEquals('val3!', $externalHub->get("s.item2"));
    }

    public function testInternalRelations()
    {
        $externalHub = new Hub();
        $id = 'subhub';
        $internalHub = new Hub;
        $internalHub
            ->builder()
            ->define('item', 'val')
            ->usedBy('title', function (&$target, $src) {
                $target = ucfirst($src);
            })
            ->define('title', null, true);

        $subHub = new SubHub("$id.", $internalHub);
        $subHub->register($externalHub);

        $externalHub->set("$id.item", 'val1');
        $this->assertEquals('Val1', $externalHub->get("$id.title"));

        $subHub->set("item", 'val2');
        $this->assertEquals('Val2', $externalHub->get("$id.title"));

        $internalHub->set("item", 'val3');
        $this->assertEquals('Val3', $externalHub->get("$id.title"));
    }

    public function testExternalDependency()
    {
        $externalHub = new Hub();
        $id = 'subhub';
        $internalHub = new Hub;
        $internalHub->builder()->define('item', 'val');
        $subHub = new SubHub("$id.", $internalHub);
        $subHub->register($externalHub);

        $externalHub->builder()
            ->define('final', null, true)
            ->uses(
                "$id.item",
                function (&$target, $src) {
                    $target = $src . '!';
                }
            );
        $this->assertEquals('val!', $externalHub->get("final"));

        $externalHub->set("$id.item", 'val1');
        $this->assertEquals('val1!', $externalHub->get("final"));

        $subHub->set("item", 'val2');
        $this->assertEquals('val2!', $externalHub->get("final"));

        /** @limitation
         * Following code will not work because internal hub don't tracks external dependencies.
         * Therefore always replace internal hub instance to SubHub wrapper where it used.
         */
        # $internalHub->set("item", 'val3');
        # $this->assertEquals('Val3!', $this->hub->get("final"));
    }

    public function testExternalOnInternalDependency()
    {

        $internalHub = new Hub([
            new ItemDefinition('item', 'val'),
            new ItemDefinition('title'),
            new RelationDefinition('title', 'item', function (&$target, $source) {
                $target = ucfirst($source);
            })
        ]);

        $externalHub = new Hub();

        $id = 'subhub';
        $subHub = new SubHub("$id.", $internalHub, $externalHub);

        $fn = function (&$target, $source) {
            $target = $source . '!';
        };
        $externalHub->addDefinitions([
            new ItemDefinition('final'),
            new ItemDefinition('final2'),
            new RelationDefinition('final', "$id.title", $fn),
            new RelationDefinition('final2', 'final', $fn),
        ]);

        $this->assertEquals('Val!', $externalHub->get("final"));

        $externalHub->set("$id.item", 'val1');
        $this->assertEquals('Val1!', $externalHub->get("final"));
        $this->assertEquals('Val1!!', $externalHub->get("final2"));

        $subHub->set("item", 'val2');
        $this->assertEquals('Val2!!', $externalHub->get("final2"));
        $this->assertEquals('Val2!', $externalHub->get("final"));

        $externalHub->set('final', 'will_be_rewritten');
        $this->assertEquals('Val2!!', $externalHub->get("final2"));
        $this->assertEquals('Val2!', $externalHub->get("final"));

    }

    public function testInternalOnExternalDependency()
    {
        $id = 'subhub';
        $externalHub = new Hub;
        $internalHub = new Hub;
        $internalHub
            ->builder()
            ->define('item', 'val');
        $subHub = new SubHub("$id.", $internalHub);
        $subHub->register($externalHub);

        $externalHub->builder()
            ->define('external', '1')
            ->usedBy(
                "$id.item",
                function (&$target, $src) {
                    $target .= ".$src";
                }
            );
        $this->assertEquals('val.1', $externalHub->get("$id.item"));
        $this->assertEquals('val.1', $subHub->get("item"));
        $this->assertEquals('val.1', $internalHub->get("item"));
        $externalHub->set('external', 2);
        $this->assertEquals('val.1.2', $externalHub->get("$id.item"));
        $this->assertEquals('val.1.2', $subHub->get("item"));
        $this->assertEquals('val.1.2', $internalHub->get("item"));
        $externalHub->set("$id.item", 'updated');
        $this->assertEquals('updated.2', $externalHub->get("$id.item"));
        $this->assertEquals('updated.2', $subHub->get("item"));
        $this->assertEquals('updated.2', $internalHub->get("item"));
        $internalHub->set("item", 'updated2');
        $this->assertEquals('updated2.2', $externalHub->get("$id.item"));
        $this->assertEquals('updated2.2', $subHub->get("item"));
        $this->assertEquals('updated2.2', $internalHub->get("item"));
    }
}
