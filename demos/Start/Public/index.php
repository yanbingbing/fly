<?php
/**
 * Start bootstrap file
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

include dirname(__DIR__) . '/Config/Define.php';
include SHARED_DIR . '/Core.php';

$app = new Fly\Mvc\Application(APPLICATION_DIR . '/Config/Application.php');

$app->run();