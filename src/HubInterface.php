<?php

namespace Nayjest\DI;

use Interop\Container\ContainerInterface;

interface HubInterface extends ContainerInterface
{
    /**
     * @param string $id
     * @return $this
     */
    public function update($id);

    /**
     * Set's item value.
     * Throws exception if item isn't defined or has no setter.
     *
     * @api
     * @param string $id
     * @param mixed $value
     * @return $this
     */
    public function set($id, $value);

    /**
     * Adds component to hub.
     *
     * @api
     * @param ComponentInterface $component
     * @return $this
     */
    public function add(ComponentInterface $component);

    /**
     * @param ComponentInterface[] $components
     * @return $this
     */
    public function addMany(array $components);
}
