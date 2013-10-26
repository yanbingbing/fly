<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\Db\Sql;

use Fly\Db\Adapter\Driver;
use Fly\Db\Adapter\Platform\PlatformInterface;

interface SqlInterface
{
	/**
	 * @param PlatformInterface $platform
	 * @return $this
	 */
	public function setPlatform(PlatformInterface $platform);

	/**
	 * @param Driver\DriverInterface $driver
	 * @return $this
	 */
	public function setDriver(Driver\DriverInterface $driver);

	/**
	 * @param PlatformInterface|null $platform
	 * @param Driver\DriverInterface|null $driver
	 * @return Driver\StatementInterface
	 */
	public function prepareStatement(PlatformInterface $platform = null, Driver\DriverInterface $driver = null);

	/**
	 * @param PlatformInterface $platform
	 * @return string
	 */
	public function getSqlString(PlatformInterface $platform = null);

	/**
	 * @param null|array|\Fly\Db\Adapter\Parameters $parameters
	 * @return Driver\ResultInterface
	 */
	public function execute($parameters = null);
}
