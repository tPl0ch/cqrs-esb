<?php
/*
 * This file is part of the Cqrs package.
 * (c) Manfred Weber <crafics@php.net> and Alexander Miertsch <kontakt@codeliner.ws>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Cqrs\Configuration;

use Cqrs\Command\CommandHandlerLoaderInterface;
use Cqrs\Event\EventListenerLoaderInterface;
use Cqrs\Query\QueryHandlerLoaderInterface;
use Cqrs\Gate;

/**
 * Class Setup
 *
 * Description of CQRS Setup class
 *
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 * @package Cqrs\Configuration
 */
class Setup
{
    /**
     *
     * @var Gate
     */
    protected $gate;

    /**
     *
     * @var CommandHandlerLoaderInterface
     */
    protected $commandHandlerLoader;

    /**
     *
     * @var EventListenerLoaderInterface
     */
    protected $eventListenerLoader;
    
    /**
     *
     * @var QueryHandlerLoaderInterface 
     */
    protected $queryHandlerLoader;


    /**
     * set gate
     *
     * @param Gate $gate
     */
    public function setGate(Gate $gate)
    {
        $this->gate = $gate;
    }

    /**
     * get gate
     *
     * @return Gate
     */
    public function getGate()
    {
        return $this->gate;
    }

    /**
     * Set CommandHandlerLoaderInterface
     *
     * @param CommandHandlerLoaderInterface $commandHandlerLoader
     */
    public function setCommandHandlerLoader(CommandHandlerLoaderInterface $commandHandlerLoader)
    {
        $this->commandHandlerLoader = $commandHandlerLoader;
    }

    /**
     * Get CommandHandlerLoaderInterface
     *
     * @return CommandHandlerLoaderInterface
     */
    public function getCommandHandlerLoader()
    {
        return $this->commandHandlerLoader;
    }

    /**
     * Set EventListenerLoaderInterface
     *
     * @param EventListenerLoaderInterface $eventListenerLoader
     */
    public function setEventListenerLoader(EventListenerLoaderInterface $eventListenerLoader)
    {
        $this->eventListenerLoader = $eventListenerLoader;
    }

    /**
     * Get EventListenerLoader
     *
     * @return EventListenerLoaderInterface
     */
    public function getEventListenerLoader()
    {
        return $this->eventListenerLoader;
    }
    
    /**
     * 
     * @return QueryHandlerLoaderInterface
     */
    public function getQueryHandlerLoader()
    {
        return $this->queryHandlerLoader;
    }

    /**
     * 
     * @param QueryHandlerLoaderInterface $queryHandlerLoader
     */
    public function setQueryHandlerLoader(QueryHandlerLoaderInterface $queryHandlerLoader)
    {
        $this->queryHandlerLoader = $queryHandlerLoader;
    }

    
    /**
     * initialize
     *
     * @param array $configuration
     * @throws ConfigurationException
     */
    public function initialize(array $configuration)
    {

        if (is_null($this->gate)) {
            throw ConfigurationException::initializeError('Gate not initialized. Create a new Gate() and pass it to setGate()');
        }

        foreach ($configuration['adapters'] as $adapterConfiguration) {
            $adapter = $this->loadAdapter($adapterConfiguration);

            foreach ($adapterConfiguration['buses'] as $busClass => $busAdapterConfiguration) {
                $bus = $this->loadBus($busClass);
                $adapter->pipe($bus, $busAdapterConfiguration);
            }
        }
    }

    /**
     * load adapter
     *
     * @param array $configuration
     * @return mixed
     */
    protected function loadAdapter(array $configuration)
    {
        $adapterClass = $configuration['class'];
        $config = isset($configuration['options']) ? $configuration['options'] : null;

        return new $adapterClass($config);
    }

    /**
     * load bus
     *
     * @param $busClass
     * @return mixed
     * @throws ConfigurationException
     */
    protected function loadBus($busClass)
    {
        if (is_null($this->getCommandHandlerLoader())) {
            throw ConfigurationException::initializeError('CommandHandlerLoaderInterface not initialized. Create a new CommandHandlerLoader() and pass it to setCommandHandlerLoader()');
        }
        if (is_null($this->getEventListenerLoader())) {
            throw ConfigurationException::initializeError('EventListenerLoaderInterface not initialized. Create a new EventListenerLoader() and pass it to setEventListenerLoader()');
        }

        $bus = new $busClass(
            $this->getCommandHandlerLoader(), 
            $this->getEventListenerLoader(),
            $this->getQueryHandlerLoader()
        );
        $this->gate->attach($bus);
        return $bus;
    }
}
