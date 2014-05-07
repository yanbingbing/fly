<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\Db\Sql\Predicate;

class IsNotNull extends IsNull
{
    protected $specification = '%1$s IS NOT NULL';
}
