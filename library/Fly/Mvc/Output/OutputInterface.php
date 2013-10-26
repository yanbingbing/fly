<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\Mvc\Output;

use Fly\Mvc\Sender\SenderInterface;

interface OutputInterface
{
	/**
	 * Output the content
	 */
	public function __invoke(SenderInterface $sender);

}