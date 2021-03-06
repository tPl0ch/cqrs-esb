<?php
/*
 * This file is part of the Cqrs package.
 * (c) Manfred Weber <crafics@php.net> and Alexander Miertsch <kontakt@codeliner.ws>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Malocher\Cqrs\Command;

/**
 * Class CommandException
 *
 * @author Manfred Weber <crafics@php.net>
 * @package Malocher\Cqrs\Command
 */
class CommandException extends \Exception
{
    /**
     * Creates a new CommandException describing a handler error.
     *
     * @param string $message Exception message
     * @return CommandException
     */
    public static function handlerError($message)
    {
        return new self('[Handler Error] ' . $message . "\n");
    }

}
