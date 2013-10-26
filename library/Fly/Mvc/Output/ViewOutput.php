<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\Mvc\Output;

use Fly\Mvc\Input\InputInterface;
use Fly\Mvc\Sender\SenderInterface;
use Fly\View\View;
use Fly\View\ViewManager;

class ViewOutput implements OutputInterface
{
	/**
	 * @var View
	 */
	protected $view = null;

	/**
	 * @var ViewManager
	 */
	protected $viewManager = null;

	/**
	 * @var InputInterface
	 */
	protected $input = null;

	/**
	 * Constructor
	 *
	 * @param View $view
	 * @param ViewManager $viewManager
	 * @param InputInterface $input
	 */
	public function __construct(View $view, ViewManager $viewManager, InputInterface $input)
	{
		$this->setView($view);
		$this->setViewManager($viewManager);
		$this->setInput($input);
	}

	/**
	 * @param  View $view
	 * @return $this
	 */
	public function setView(View $view)
	{
		$this->view = $view;
		return $this;
	}

	/**
	 * @return null|View
	 */
	public function getView()
	{
		return $this->view;
	}

	/**
	 * @param ViewManager $viewManager
	 * @return $this;
	 */
	public function setViewManager(ViewManager $viewManager)
	{
		$this->viewManager = $viewManager;
		return $this;
	}

	/**
	 * @return ViewManager
	 */
	public function getViewManager()
	{
		return $this->viewManager;
	}

	/**
	 * @param InputInterface $input
	 * @return $this
	 */
	public function setInput(InputInterface $input)
	{
		$this->input = $input;
		return $this;
	}

	/**
	 * @return InputInterface
	 */
	public function getInput()
	{
		return $this->input;
	}

	/**
	 * Output the content
	 */
	public function __invoke(SenderInterface $sender)
	{
		$view = $this->getView();
		$view->setVariable('_INPUT', $this->getInput());
		$sender->setContent($this->getViewManager()->render($view));
	}
}
