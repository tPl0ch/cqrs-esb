<?php
/*
 * This file is part of the Cqrs package.
 * (c) Manfred Weber <manfred.weber@gmail.com> and Alexander Miertsch <kontakt@codeliner.ws>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Test\Cqrs\Adapter;

use Cqrs\Command\ClassMapCommandHandlerLoader;
use Cqrs\Event\ClassMapEventListenerLoader;
use Cqrs\Gate;
use Cqrs\Gate\GateException;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Test\Mock\Bus\BusAnnotationMock;
use Test\Mock\Command\MockCommand;
use Test\TestCase;
use Cqrs\Adapter\AnnotationAdapter;

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.1 on 2013-09-16 at 23:33:58.
 */
class AnnotationAdapterTest extends TestCase
{

    /**
     * @var Gate
     */
    protected $gate;

    /**
     * @var AnnotationAdapter
     */
    protected $adapter;

    /**
     * @var BusInterface
     */
    protected $bus;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->gate = Gate::getInstance();
        if( !isset( $this->bus ) ){
            $classMapCommandHandlerLoader = new ClassMapCommandHandlerLoader();
            $classMapEventListenerLoader = new ClassMapEventListenerLoader();
            $this->bus = new BusAnnotationMock($classMapCommandHandlerLoader, $classMapEventListenerLoader);
        }
        if( !isset( $this->adapter ) ){
            $this->adapter = new AnnotationAdapter();
        }
    }

    /**
     * @covers Cqrs\Adapter\AnnotationAdapter::allow
     */
    public function testSendToMultiHandlers()
    {
        try {
            $this->gate->attach($this->bus);
        } catch( GateException $e){
            echo $e->getMessage();
        }
        $this->adapter->allow($this->bus,'Test\Mock\Handler\MockAnnotationFooHandler');
        $this->adapter->allow($this->bus,'Test\Mock\Handler\MockAnnotationBarHandler');
        $this->adapter->allow($this->bus,'Test\Mock\Handler\MockAnnotationOutputHandler');
        $this->bus->invokeCommand(new MockCommand());
    }

    /**
     * @covers Cqrs\Adapter\AnnotationAdapter::allow
     */
    public function testSendToMessageBus()
    {
        try {
            $this->gate->attach($this->bus);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        $this->gate->enableSystemBus();
        $this->adapter->allow($this->bus,'Test\Mock\Handler\MockAnnotationFooHandler');
        $this->adapter->allow($this->bus,'Test\Mock\Handler\MockAnnotationOutputHandler');
        $this->adapter->allow($this->gate->getBus('system-bus'),'Test\Mock\Handler\MockSystemBusHandler');
        $this->bus->invokeCommand(new MockCommand());
    }

}