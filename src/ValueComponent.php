<?php

namespace Nayjest\DI;

use Nayjest\DI\Exception\ReadonlyException;

class ValueComponent extends AbstractComponent
{
    /** @var string */
    private $id;

    /** @var mixed */
    private $value;

    /**
     * @var bool
     */
    private $readonly;

    /**
     * Constructor.
     *
     * @param string $id
     * @param mixed $value
     * @param bool $readonly
     */
    public function __construct($id, $value = null, $readonly = false)
    {
        $this->id = $id;
        $this->value = $value;
        $this->readonly = $readonly;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    public function setValue($value)
    {
        if ($this->readonly && $this->hub !== null) {
            throw new ReadonlyException;
        }
        $this->value = $value;
        $this->notifyHub($this->id);
        return $this;
    }

    protected function register(ComponentDefinitions $definitions, HubInterface $hub)
    {
        $definition = $definitions
            ->define($this->id)
            ->namedLocallyAs('value');
        if ($this->readonly) {
            $definition->readonly();
        }
    }
}
