<?php namespace Gil\ZenRouter\Adapters;

use Gil\ZenRouter\Contracts\ContainerInterface;
use Orno\Di\Container;

class OrnoContainerAdapter implements ContainerInterface {

    protected $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function get($alias)
    {
        return $this->container->get($alias);
    }
}