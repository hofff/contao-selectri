<?php

declare(strict_types=1);

namespace Hofff\Contao\Selectri\Model;

use Hofff\Contao\Selectri\Widget;

abstract class AbstractDataDecoratorFactory implements DataFactory
{
    /** @var DataFactory */
    private $factory;

    public function __construct(DataFactory $factory)
    {
        $this->factory = $factory;
    }

    public function getDecoratedDataFactory(): DataFactory
    {
        return $this->factory;
    }

    /** {@inheritDoc} */
    public function setParameters(array $params): void
    {
        $this->getDecoratedDataFactory()->setParameters($params);
    }

    public function createData(?Widget $widget = null): Data
    {
        $decoratedData = $this->getDecoratedDataFactory()->createData($widget);

        return $this->createDecorator($decoratedData);
    }

    abstract protected function createDecorator(Data $decoratedData): Data;
}
