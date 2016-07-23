<?php
/**
 * @package elasticsearch
 * @subpackage com_elasticsearch
 * @author Jean-Baptiste Cayrou and Adrien Gareau
 * @copyright Copyright 2013 CRIM - Computer Research Institute of Montreal
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
**/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\Registry\Registry;

require_once JPATH_ADMINISTRATOR.'/components/com_elasticsearch/helpers/render.php';

/**
 * HTML View class for the ElasticSearch Component
 *
 * @since  1.0
 */
class ElasticSearchViewElasticSearch extends JViewLegacy
{
	// Overwriting JView display method
	public function display($tpl = null)
	{
		// TODO: Load this in our own language file
		JFactory::getLanguage()->load('com_search');

		/** @var JApplicationSite $app */
		$app	= JFactory::getApplication();
		$params = $app->getParams();
		$menus	= $app->getMenu();
		$menu	= $menus->getActive();
		$model = $this->getModel();
		$this->elements=array();

		// Because the application sets a default page title, we need to get it
		// right from the menu item itself
		if (is_object($menu))
		{
			$menu_params = new Registry;
			$menu_params->loadString($menu->params);

			if (!$menu_params->get('page_title'))
			{
				$params->set('page_title',	JText::_('COM_SEARCH_SEARCH'));
			}
		}
		else
		{
			$params->set('page_title',	JText::_('COM_SEARCH_SEARCH'));
		}

		$title = $params->get('page_title');

		if ($app->get('sitename_pagetitles', 0) == 1)
		{
			$title = JText::sprintf('JPAGETITLE', $app->getCfg('sitename'), $title);
		}
		elseif ($app->get('sitename_pagetitles', 0) == 2)
		{
			$title = JText::sprintf('JPAGETITLE', $title, $app->getCfg('sitename'));
		}

		$this->document->setTitle($title);

		if ($params->get('menu-meta_description'))
		{
			$this->document->setDescription($params->get('menu-meta_description'));
		}

		if ($params->get('menu-meta_keywords'))
		{
			$this->document->setMetaData('keywords', $params->get('menu-meta_keywords'));
		}

		if ($params->get('robots'))
		{
			$this->document->setMetaData('robots', $params->get('robots'));
		}

		$this->assignRef('params', $params);


		$input = JFactory::getApplication()->input;

		// Get the offset and the limit for pagination
		$offset = $input->get->getInt('start',0);
		$limit = $input->get->getInt('limit', 10);
		$this->searchword = $input->get->getString('searchword', null);

		// Get search areas to limit search on selected types
		$this->areas = $model->getSearchAreas();

		$this->totalResults=0;

		// Search only if searchword or start GET parameter exists
		if($this->searchword || isset($_GET['start']) || isset($_GET['limitstart']))
		{
			//Call the model to make the search and get results and the number of it
			$model->search();
			$this->totalResults = $model->getTotalHits();
			$this->results = $model->getResults();
		}

		//Pagination : utiliser splice pour couper l'array en plusieurs parties
		$this->pagination = new JPagination($this->totalResults,$offset,$limit);

		// Display the view
		return parent::display($tpl);
	}
}
