<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\Db\Sql\Predicate;

class Like implements PredicateInterface
{

	protected $specification = '%1$s LIKE %2$s';
	protected $identifier = '';
	protected $like = '';

	/**
	 * @param string $identifier
	 * @param string $like
	 */
	public function __construct($identifier, $like)
	{
		$this->identifier = $identifier;
		$this->like = $like;
	}

	/**
	 * @return array
	 */
	public function getExpressionData()
	{
		return array(
			array(
				$this->specification, array($this->identifier, $this->like),
				array(self::TYPE_IDENTIFIER, self::TYPE_VALUE)
			)
		);
	}

}
