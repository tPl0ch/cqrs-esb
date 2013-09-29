<?php
/*
 * This file is part of the Cqrs package.
 * (c) Manfred Weber <crafics@php.net> and Alexander Miertsch <kontakt@codeliner.ws>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Cqrs\Bus;

use Cqrs\Command\CommandHandlerLoaderInterface;
use Cqrs\Command\CommandInterface;
use Cqrs\Command\InvokeCommandCommand;
use Cqrs\Command\PublishEventCommand;
use Cqrs\Event\CommandInvokedEvent;
use Cqrs\Event\EventInterface;
use Cqrs\Event\EventListenerLoaderInterface;
use Cqrs\Event\EventPublishedEvent;
use Cqrs\Gate;

/**
 * Class AbstractBus
 *
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 * @package Cqrs\Bus
 */
abstract class AbstractBus implements BusInterface
{
    const SYSTEMBUS = 'system-bus';

    /**
     * @var \Cqrs\Command\CommandHandlerLoaderInterface
     */
    protected $commandHandlerLoader;

    /**
     * @var \Cqrs\Event\EventListenerLoaderInterface
     */
    protected $eventListenerLoader;

    /**
     * @var array
     */
    protected $commandHandlerMap = array();

    /**
     * @var array
     */
    protected $eventListenerMap = array();

    /**
     * @var Gate
     */
    protected $gate;

    /**
     * @param CommandHandlerLoaderInterface $commandHandlerLoader
     * @param EventListenerLoaderInterface $eventListenerLoader
     */
    public function __construct(
        CommandHandlerLoaderInterface $commandHandlerLoader,
        EventListenerLoaderInterface $eventListenerLoader)
    {

        $this->commandHandlerLoader = $commandHandlerLoader;
        $this->eventListenerLoader = $eventListenerLoader;
    }

    /**
     * @param Gate $gate
     */
    public function setGate(Gate $gate)
    {
        $this->gate = $gate;
    }

    /**
     * @return Gate
     */
    public function getGate()
    {
        return $this->gate;
    }

    /**
     * @param $commandClass
     * @param $callableOrDefinition
     * @return bool|mixed
     */
    public function mapCommand($commandClass, $callableOrDefinition)
    {
        if (!isset($this->commandHandlerMap[$commandClass])) {
            $this->commandHandlerMap[$commandClass] = array();
        }
        $this->commandHandlerMap[$commandClass][] = $callableOrDefinition;
        return true;
    }

    /**
     * @return array
     */
    public function getCommandHandlerMap()
    {
        return $this->commandHandlerMap;
    }

    /**
     * @param CommandInterface $command
     * @return bool|void
     * @throws BusException
     */
    public function invokeCommand(CommandInterface $command)
    {
        $commandClass = get_class($command);

        // InvokeCommandCommand first! Because a commandClass _IS_ actually invoked.
        if (!is_null($this->gate->getSystemBus())) {
            $invokeCommandCommand = new InvokeCommandCommand();
            $invokeCommandCommand->setMessageClass(get_class($command));
            $invokeCommandCommand->setMessageVars($command->getMessageVars());
            $invokeCommandCommand->setBusName($this->getName());
            $this->gate->getSystemBus()->invokeCommand($invokeCommandCommand);
        }

        // Check if command exists after invoking the InvokeCommandCommand because
        // the InvokeCommandCommand tells that a command is invoked but does not care
        // if it succeeded. Later the CommandInvokedEvent can be used to check if a
        // command succeeded.
        if (!isset($this->commandHandlerMap[$commandClass])) {
            return false;
        }

        foreach ($this->commandHandlerMap[$commandClass] as $i => $callableOrDefinition) {

            if (is_callable($callableOrDefinition)) {
                call_user_func($callableOrDefinition, $command, $this->gate);
            }

            if (is_array($callableOrDefinition)) {
                $commandHandler = $this->commandHandlerLoader->getCommandHandler($callableOrDefinition['alias']);
                $method = $callableOrDefinition['method'];

                // instead of invoking the handler method directly
                // we call the execute function of the implemented trait and pass along a reference to the gate
                $usedTraits = class_uses($commandHandler);
                if (!isset($usedTraits['Cqrs\Adapter\AdapterTrait'])) {
                    throw BusException::traitError('Adapter Trait is missing! Use it!');
                }
                $commandHandler->executeCommand($this, $commandHandler, $method, $command);
            }
        }

        // Dispatch the CommandInvokedEvent here! If for example a command could not be invoked
        // because it does not exist in the commandHandlerMap[<empty>] this Event would never
        // be dispatched!
        if (!is_null($this->gate->getSystemBus())) {
            $commandInvokedEvent = new CommandInvokedEvent();
            $commandInvokedEvent->setMessageClass(get_class($command));
            $commandInvokedEvent->setMessageVars($command->getMessageVars());
            $commandInvokedEvent->setBusName($this->getName());
            $this->gate->getSystemBus()->publishEvent($commandInvokedEvent);
        }

        return true;
    }

    /**
     * @param $eventClass
     * @param $callableOrDefinition
     * @return bool|mixed
     */
    public function registerEventListener($eventClass, $callableOrDefinition)
    {
        if (!isset($this->eventListenerMap[$eventClass])) {
            $this->eventListenerMap[$eventClass] = array();
        }
        $this->eventListenerMap[$eventClass][] = $callableOrDefinition;
        return true;
    }

    /**
     * @return array
     */
    public function getEventListenerMap()
    {
        return $this->eventListenerMap;
    }

    /**
     * @param EventInterface $event
     * @return bool|void
     * @throws BusException
     */
    public function publishEvent(EventInterface $event)
    {
        $eventClass = get_class($event);

        // Check if event exists after invoking the PublishEventCommand because
        // the PublishEventCommand tells that a event is dispatched but does not care
        // if it succeeded. Later the EventPublishedEvent can be used to check if a
        // event succeeded.
        if (!is_null($this->gate->getSystemBus())) {
            $publishEventCommand = new PublishEventCommand();
            $publishEventCommand->setMessageClass(get_class($event));
            $publishEventCommand->setMessageVars($event->getMessageVars());
            $publishEventCommand->setBusName($this->getName());
            $this->gate->getSystemBus()->invokeCommand($publishEventCommand);
        }

        if (!isset($this->eventListenerMap[$eventClass])) {
            return false;
        }

        foreach ($this->eventListenerMap[$eventClass] as $i => $callableOrDefinition) {
            if (is_callable($callableOrDefinition)) {
                call_user_func($callableOrDefinition, $event);
            }

            if (is_array($callableOrDefinition)) {
                $eventListener = $this->eventListenerLoader->getEventListener($callableOrDefinition['alias']);
                $method = $callableOrDefinition['method'];

                // instead of invoking the handler method directly
                // we call the execute function of the implemented trait and pass along a reference to the gate
                $usedTraits = class_uses($eventListener);
                if (!isset($usedTraits['Cqrs\Adapter\AdapterTrait'])) {
                    throw BusException::traitError('Adapter Trait is missing! Use it!');
                }
                $eventListener->executeEvent($this, $eventListener, $method, $event);
            }
        }

        // Dispatch the EventPublishedEvent here! If for example a event could not be dispatched
        // because it does not exist in the eventListenerMap[<empty>] this Event would never
        // be dispatched!
        if (!is_null($this->gate->getSystemBus())) {
            $eventPublishedEvent = new EventPublishedEvent();
            $eventPublishedEvent->setMessageClass(get_class($event));
            $eventPublishedEvent->setMessageVars($event->getMessageVars());
            $eventPublishedEvent->setBusName($this->getName());
            $this->gate->getSystemBus()->publishEvent($eventPublishedEvent);
        }

        return true;
    }
}
