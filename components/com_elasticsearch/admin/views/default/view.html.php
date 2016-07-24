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

JLoader::register('ElasticSearchConfig', JPATH_ADMINISTRATOR . '/components/com_elasticsearch/helpers/config.php');

/**
 * ElasticSearch View
 *
 * @since  1.0
 */
class ElasticSearchViewDefault extends JViewLegacy
{
	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise an Error object.
	 *
	 * @since   1.0
	 */
	public function display($tpl = null)
	{
		$this->pluginStatus = $this->get('pluginState');

		if ($this->pluginStatus["System - ElasticaLib"]->enabled)
		{
			if ($this->get('isConnected'))
			{
				$this->items = $this->get('Items');
			}

			$this->indexName = ElasticSearchConfig::getIndexName();
		}

		// Set the toolbar
		$this->addToolBar();

		// Display the template
		parent::display($tpl);
	}

	/**
	 * Setting the toolbar
	 *
	 * @since  1.0
	 */
	protected function addToolBar()
	{
		JToolbarHelper::title(JText::_('COM_ELASTICSEARCH_MANAGER_INDEX'));

		$toolbar = JToolbar::getInstance('toolbar');
		JToolbarHelper::preferences('com_elasticsearch');
		$toolbar->appendButton('Popup', 'archive', 'COM_ELASTICSEARCH_SERVER_INDEX', 'index.php?option=com_elasticsearch&view=indexer&tmpl=component', 500, 210);
		$toolbar->appendButton('Popup', 'delete', 'COM_ELASTICSEARCH_SERVER_PURGE', 'index.php?option=com_elasticsearch&view=delete&tmpl=component', 500, 210);

	}
}
