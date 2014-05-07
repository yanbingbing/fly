<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\Db\Adapter\Driver;

use Fly\Db\Adapter\Parameters;

interface StatementInterface
{

    /**
     * Get resource
     *
     * @return resource
     */
    public function getResource();

    /**
     * Prepare sql
     *
     * @param string $sql
     */
    public function prepare($sql = null);

    /**
     * Check if is prepared
     *
     * @return bool
     */
    public function isPrepared();

    /**
     * Execute
     *
     * @param null|array|Parameters $parameters
     * @return ResultInterface
     */
    public function execute($parameters = null);

    /**
     * Set sql
     *
     * @param $sql
     * @return mixed
     */
    public function setSql($sql);

    /**
     * Get sql
     *
     * @return mixed
     */
    public function getSql();

    /**
     * Set parameters container
     *
     * @param Parameters $parameters
     * @return mixed
     */
    public function setParameters(Parameters $parameters);

    /**
     * Get parameters container
     *
     * @return Parameters
     */
    public function getParameters();

}
