<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\Db\Sql\Predicate;

class Literal implements PredicateInterface
{
    protected $literal = '';

    public function __construct($literal)
    {
        $this->literal = $literal;
    }

    /**
     * @return array
     */
    public function getExpressionData()
    {
        return array(
            array(
                str_replace('%', '%%', $this->literal),
                array(),
                array()
            )
        );
    }
}
