<?php
/*
 * This file is part of the Cqrs package.
 * (c) Manfred Weber <manfred.weber@gmail.com> and Alexander Miertsch <kontakt@codeliner.ws>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Test\Mock\Service;

use Cqrs\Adapter\AdapterTrait;
use Test\Mock\Event\MockEvent;

class MockWrongCommandAnnotationService {

    use AdapterTrait;

    /**
     * @Cqrs\Annotation\Command("Test\Mock\Command\MockMissingOrWrongCommand")
     */
    public function getFoo(MockCommand $command)
    {
        $mockEvent = new MockEvent();
        $mockEvent->edit();
        $this->getBus( "mock-annotation-bus" )->publishEvent( $mockEvent );
    }
}