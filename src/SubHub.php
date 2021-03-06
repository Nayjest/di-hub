<?php

namespace Nayjest\DI;

use Closure;
use Nayjest\DI\Definition\DefinitionInterface;
use Nayjest\DI\Definition\Item;
use Nayjest\DI\Definition\Value;
use Nayjest\DI\Definition\Relation;
use Nayjest\DI\Exception\InternalErrorException;
use Nayjest\DI\Internal\AbstractHub;
use Nayjest\DI\Internal\ItemControllerWrapper;
use SplObjectStorage;

/**
 * Class SubHub
 *
 * This class allows to organize hierarchy of hubs.
 * It acts as full-featured decorator for nested hub and exposes it's data to parent(external) hub.
 *
 * All data of nested hub will be accessible in parent hub with keys prefixed by SubHub prefix.
 *
 * SubHub maintains relations between items of parent and nested hubs
 * (relations with target or source from parent hub must be defined ).
 *
 */
class SubHub extends HubWrapper
{
    const EXTERNAL_RELATION_ITEM_PREFIX = '@';

    /**
     * @var SplObjectStorage SubHubs indexed by its external hubs
     */
    private static $subHubs;

    /** @var string|Closure */
    private $prefix;

    /** @var  AbstractHub */
    protected $externalHub;

    /**
     * Relations having target or source in external hub.
     * ID's of external items must be prefixed by '@' in relation to recognize it as external.
     *
     * @var Relation[]
     */
    protected $externalRelations = [];

    /**
     * Makes identifier of item from external hub for external relations.
     *
     * @param string $id
     * @return string
     */
    public static function externalItemId($id)
    {
        return self::EXTERNAL_RELATION_ITEM_PREFIX . $id;
    }

    /**
     * SubHub constructor.
     *
     * @param string|Closure $namePrefix
     * @param AbstractHub $internalHub
     * @param AbstractHub|null $externalHub
     */
    public function __construct($namePrefix, AbstractHub $internalHub, AbstractHub $externalHub = null)
    {
        parent::__construct($internalHub);
        $this->prefix = $namePrefix;
        # required for multilevel hierarchy
        $this->relationController = $internalHub->relationController;
        if ($externalHub) {
            $this->register($externalHub);
        }
    }

    /**
     * @param HubInterface $externalHub
     */
    public function register(HubInterface $externalHub)
    {
        $this->externalHub = $externalHub;
        $this->replaceExternalHubsToThis();
        $externalHub->addDefinition(new Value($this->getId(), $this));
        foreach ($this->hub->getIds() as $id) {
            $this->exposeItem($id);
        }
        foreach ($this->externalRelations as $relation) {
            $this->exposeExternalRelation($relation);
        }
    }

    /**
     * @return string
     */
    protected function getId()
    {
        return $this->prefixedId('hub');
    }

    /**
     * Set's item value.
     * Throws exception if item isn't defined or has no setter.
     *
     * @api
     * @param string $id
     * @param mixed $value
     * @return $this
     */
    public function set($id, $value)
    {
        if ($this->externalHub) {
            $externalId = $this->prefixedId($id);
            $this->externalHub->set($externalId, $value);
        } else {
            parent::set($id, $value);
        }
        return $this;
    }

    /**
     * @param DefinitionInterface $definition
     * @return $this
     */
    public function addDefinition(DefinitionInterface $definition)
    {
        // Add item relation directly via sub-hub to process external relations
        // and remove relation from item to avoid adding it to internal hub
        if ($definition instanceof Item && $definition->relation) {
            $this->addDefinition($definition->relation);
            $definition->relation = null;
        }

        if ($this->isExternalRelation($definition)) {
            /** @var Relation $definition */
            $this->addExternalRelation($definition);
            return $this;
        }

        parent::addDefinition($definition);

        if ($this->externalHub && $definition instanceof Value) {
            $this->exposeItem($definition->id);
        }

        return $this;
    }

    protected function isExternalRelationField($field)
    {
        return strpos($field, self::EXTERNAL_RELATION_ITEM_PREFIX) === 0;
    }

    protected function isExternalRelation(DefinitionInterface $definition)
    {
        if (!$definition instanceof Relation) {
            return false;
        }
        if ($this->isExternalRelationField($definition->target)) {
            return true;
        }
        if ($definition->isMultiSource()) {
            foreach ($definition->source as $src) {
                if ($this->isExternalRelationField($src)) {
                    return true;
                }
            }
            return false;
        } else {
            return $this->isExternalRelationField($definition->source);
        }
    }

    protected function addExternalRelation(Relation $definition)
    {
        if (!$definition->isMultiSource()) {
            parent::addDefinition($definition);
        }
        if (!$this->externalHub) {
            $this->externalRelations[] = $definition;
        } else {
            $this->exposeExternalRelation($definition);
        }
    }

    protected function makeExternalRelationFieldId(&$field)
    {
        $field = $this->isExternalRelationField($field) ? substr($field, 1) : $this->prefixedId($field);
    }

    protected function exposeExternalRelation(Relation $definition)
    {
        if (!$definition->isMultiSource()) {
            $this->hub->remove($definition);
        }
        $this->makeExternalRelationFieldId($definition->target);
        if ($definition->isMultiSource()) {
            foreach ($definition->source as &$source) {
                $this->makeExternalRelationFieldId($source);
            }
        } else {
            $this->makeExternalRelationFieldId($definition->source);
        }
        $this->externalHub->addDefinition($definition);
    }

    /**
     * @param string $id
     * @return string
     */
    protected function prefixedId($id)
    {
        if (is_string($this->prefix)) {
            return $this->prefix . $id;
        } elseif (is_callable($this->prefix)) {
            return call_user_func($this->prefix, $id);
        } else {
            throw new InternalErrorException("Invalid SubHub prefix");
        }
    }

    /**
     * @param $internalId
     * @return Value
     */
    protected function makeExternalItemDefinition($internalId)
    {
        $definition = new Value($this->prefixedId($internalId));
        $definition->controller = new ItemControllerWrapper($internalId, $this->hub);
        return $definition;
    }

    protected function exposeItem($internalId)
    {
        $itemDefinition = $this->makeExternalItemDefinition($internalId);
        $this->externalHub->addDefinition($itemDefinition);
        $this->hub->addDefinition($this->makeExternalInitCallerRelation($itemDefinition));
    }

    protected function makeExternalInitCallerRelation(Value $item)
    {
        /** @var ItemControllerWrapper $wrapper */
        $wrapper = $item->controller;
        $handler = function () use ($item, $wrapper) {
            if ($wrapper->initializingNow) {
                return;
            }
            $prevSource = func_get_arg(2);
            $this->externalHub->relationController->initialize($item->id, $prevSource);
        };
        return new Relation(null, $wrapper->internalId, $handler);
    }

    /**
     * If its internal hub already used in other SubHubs as external hub,
     * replaces it in other SubHubs to $this.
     *
     * This functionality allows building hub hierarchy staring from internal hubs.
     * However, it not helps when building hub hierarchy staring from internal hubs and some hubs has multiple parents
     * (external hubs).
     * Therefore it's recommended to build hub hierarchy starting from top (external hubs, root hubs).
     *
     * Test (fails without this code): \Nayjest\DI\Test\Integration\SubSubHubTest::testConstructFromBottomThenRead()
     */
    protected function replaceExternalHubsToThis()
    {
        if (self::$subHubs === null) {
            self::$subHubs = new SplObjectStorage();
        }
        self::$subHubs[$this->externalHub] = $this;

        if (self::$subHubs->contains($this->hub)) {
            self::$subHubs[$this->hub]->externalHub = $this;
        }
    }
}
