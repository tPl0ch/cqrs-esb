<?php
/*
 * This file is part of the Cqrs package.
 * (c) Manfred Weber <manfred.weber@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dschini\System\Services;

use Dschini\System\Cqrs\Bus;
use Dschini\System\Interfaces\ServiceInterface;

/**
 * Service
 *
 * @author Manfred Weber <manfred.weber@gmail.com>
 */
class Service implements ServiceInterface {

    private $bus;

    public function subscribe(Bus $bus){
        $this->bus = $bus;
    }

    public function raise($event){
        $this->bus->dispatchEvent($event);
    }
}