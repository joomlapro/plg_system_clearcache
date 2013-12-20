<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.Clearcache
 *
 * @author      Bruno Batista <bruno.batista@ctis.com.br>
 * @copyright   Copyright (C) 2013 CTIS IT Services. All rights reserved.
 * @license     Commercial License
 */

// No direct access.
defined('_JEXEC') or die;

/**
 * Joomla Clearcache plugin.
 *
 * @package     Joomla.Plugin
 * @subpackage  System.Clearcache
 * @author      Bruno Batista <bruno.batista@ctis.com.br>
 * @since       3.2
 */
class PlgSystemClearcache extends JPlugin
{
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  3.2
	 */
	protected $autoloadLanguage = true;

	/**
	 * After the framework has dispatched the application.
	 *
	 * @return  void
	 *
	 * @since   3.2
	 */
	public function onAfterDispatch()
	{
		// Get the configuration object.
		$config = JFactory::getConfig();

		if (!$config->get('caching'))
		{
			return;
		}

		// User has to be authorised to see the clear cache information.
		if (!$this->isAuthorisedDisplayClearCache())
		{
			return;
		}

		// Get the application.
		$app = JFactory::getApplication();

		// Check that we are in the admin application.
		if ($app->isSite())
		{
			return;
		}

		if ($app->input->getString('clearcache') == 1)
		{
			// Include dependancies.
			JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_cache/models', 'CacheModel');

			// Get an instance of the generic cache model.
			$model = JModelLegacy::getInstance('Cache', 'CacheModel', array('ignore_request' => true));

			$items = $model->getData();
			$cid   = array();

			foreach ($items as $item)
			{
				$cid[] = $item->group;
			}

			$model->cleanlist($cid);

			// Get the full current URI.
			$uri = JUri::getInstance();
			$uri->setQuery(str_replace('&clearcache=1', '', $uri->getQuery()));

			$app->redirect($uri->toString(), true);
		}

		$this->displayLink();

		return;
	}

	/**
	 * Method to display the link with clear cache call.
	 *
	 * @return  void
	 *
	 * @since   3.2
	 */
	private function displayLink()
	{
		// Add JavaScript Frameworks.
		JHtml::_('jquery.framework');

		// Get the full current URI.
		$uri = JUri::getInstance();
		$uri->setQuery($uri->getQuery() . '&clearcache=1');

		// Get the document object.
		$doc = JFactory::getDocument();

		$doc->addScriptDeclaration(
'jQuery(document).ready(function($) {
	$(\'#menu\').append(\'<li><a href="' . htmlspecialchars($uri->toString()) . '">' . JText::_('PLG_SYSTEM_CLEARCACHE_CLEAR_CACHE') . '</a></li>\');
});'
		);
	}

	/**
	 * Method to check if the current user is allowed to see the clear cache information or not.
	 *
	 * @return  boolean  True is access is allowed
	 *
	 * @since   3.2
	 */
	private function isAuthorisedDisplayClearCache()
	{
		static $result = null;

		if (!is_null($result))
		{
			return $result;
		}

		// If the user is not allowed to view the output then end here
		$filterGroups = (array) $this->params->get('filter_groups', null);

		if (!empty($filterGroups))
		{
			$userGroups = JFactory::getUser()->get('groups');

			if (!array_intersect($filterGroups, $userGroups))
			{
				$result = false;

				return false;
			}
		}

		$result = true;

		return true;
	}
}
