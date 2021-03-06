<?php
/*
 * This file is part of the Cqrs package.
 * (c) Manfred Weber <crafics@php.net> and Alexander Miertsch <kontakt@codeliner.ws>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Malocher\Cqrs\Query;

/**
 * Class ClassMapQueryHandlerLoader
 *
 * Can be used as default, if command-handler-aliases are passed as full qualified classnames
 *
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 * @package Malocher\Cqrs\Query
 */
class ClassMapQueryHandlerLoader implements QueryHandlerLoaderInterface
{
    /**
     * get command handler
     *
     * @param string $alias
     * @throws QueryException
     * @return callable
     */
    public function getQueryHandler($alias)
    {
        if (class_exists($alias)) {
            return new $alias;
        }
        throw QueryException::handlerError(sprintf('alias <%s> does not exist', $alias));
    }
}
