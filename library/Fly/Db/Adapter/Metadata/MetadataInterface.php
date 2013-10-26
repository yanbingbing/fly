<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\Db\Adapter\Metadata;

interface MetadataInterface
{
	/**
	 * @param string $table
	 * @param string $schema
	 * @return array
	 */
	public function getColumns($table, $schema = null);

	/**
	 * @param string $table
	 * @param string $schema
	 * @return array
	 */
	public function getPrimarys($table, $schema = null);
}
