<?php

namespace Nayjest\DI;

class ValueAbstractComponent extends AbstractComponent
{
    /**
     * @var
     */
    private $id;
    /**
     * @var
     */
    private $value;

    /**
     * SimpleValueComponent constructor.
     * @param $id
     * @param $value
     */
    public function __construct($id, $value)
    {
        $this->id = $id;
        $this->value = $value;
    }

    public function register(ComponentDefinitions $definitions)
    {
        $definitions
            ->define($this->id)
            ->namedLocallyAs('value')
            ->withSetter();
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
        $this->value = $value;
        $this->notifyHub($this->id);
        return $this;
    }
}
