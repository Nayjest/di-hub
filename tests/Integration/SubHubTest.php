<?php

namespace Nayjest\DI\Test\Integration;

use Nayjest\DI\Definition\Item;
use Nayjest\DI\Hub;
use Nayjest\DI\Definition\Value;
use Nayjest\DI\Definition\Relation;
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
            new Value('item2', null),
            new Relation('item2', 'item', function (&$target, $src) {
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
            new Value('item2', null),
            new Relation('item2', 'item', function (&$target, $src) {
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
            new Value('inner', 'val'),
            new Value('inner_dependant'),
            new Relation('inner_dependant', 'inner', function (&$target, $source) {
                $target = $source . '[i-i-rel]';
            })
        ]);

        $externalHub = new Hub();

        $id = 'sh';
        $subHub = new SubHub("$id.", $internalHub, $externalHub);

        $externalHub->addDefinitions([
            new Value('outer'),
            new Value('outer2'),
            new Relation('outer', "$id.inner_dependant", function (&$target, $source) {
                $target = $source . '[e-i-rel]';
            }),
            new Relation('outer2', 'outer', function (&$target, $source) {
                $target = $source . '[e-e-rel]';
            }),
        ]);

        $this->assertEquals('val[i-i-rel][e-i-rel]', $externalHub->get("outer"));

        #
        #  sh.inner => inner => inner_dependant => sh.inner_dependant => outer => outer2
        #
        $externalHub->set("$id.inner", '1');
        $this->assertEquals('1[i-i-rel]', $internalHub->get('inner_dependant'));
        $this->assertEquals('1[i-i-rel]', $externalHub->get("$id.inner_dependant"));

        $this->assertEquals('1[i-i-rel][e-i-rel]', $externalHub->get("outer"));
        $this->assertEquals('1[i-i-rel][e-i-rel][e-e-rel]', $externalHub->get("outer2"));

        $subHub->set("inner", '2');
        $this->assertEquals('2[i-i-rel][e-i-rel]', $externalHub->get("outer"));
        $this->assertEquals('2[i-i-rel][e-i-rel][e-e-rel]', $externalHub->get("outer2"));

        $externalHub->set('outer', 'will_be_rewritten');
        $this->assertEquals('2[i-i-rel][e-i-rel]', $externalHub->get("outer"));
        $this->assertEquals('2[i-i-rel][e-i-rel][e-e-rel]', $externalHub->get("outer2"));
    }

    public function testInternalOnExternalDependency()
    {
        $id = 's';
        $externalHub = new Hub;
        $internalHub = new Hub;

        $internalHub->addDefinition(new Value('item_i', 'val'));

        $subHub = new SubHub("$id.", $internalHub);
        $subHub->register($externalHub);

        $externalHub->builder()
            ->define('external', '1')->usedBy("$id.item_i", function (&$target, $src) {
                $target .= ".$src";
            });
        $this->assertEquals('val.1', $externalHub->get("$id.item_i"));
        $this->assertEquals('val.1', $subHub->get("item_i"));
        $this->assertEquals('val.1', $internalHub->get("item_i"));
        $externalHub->set('external', 2);
        $this->assertEquals('val.1.2', $externalHub->get("$id.item_i"));
        $this->assertEquals('val.1.2', $subHub->get("item_i"));
        $this->assertEquals('val.1.2', $internalHub->get("item_i"));
        $externalHub->set("$id.item_i", 'updated');
        $this->assertEquals('updated.2', $externalHub->get("$id.item_i"));
        $this->assertEquals('updated.2', $subHub->get("item_i"));
        $this->assertEquals('updated.2', $internalHub->get("item_i"));
        $subHub->set("item_i", 'updated2');
        $this->assertEquals('updated2.2', $externalHub->get("$id.item_i"));
        $this->assertEquals('updated2.2', $subHub->get("item_i"));
        $this->assertEquals('updated2.2', $internalHub->get("item_i"));
    }

    public function testClosurePrefix()
    {
        $e = new Hub();
        $i = new Hub([
            new Item('id', 'val')
        ]);
        $prefix = function ($id) {
            return 'internal' . ucfirst($id);
        };
        new SubHub($prefix, $i, $e);
        $this->assertEquals('val', $e->get('internalId'));
    }
}
