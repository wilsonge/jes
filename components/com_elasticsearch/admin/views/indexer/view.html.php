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

/**
 * ElasticSearch View
 *
 * @since  1.0
 */
class ElasticSearchViewIndexer extends JViewLegacy
{
	/**
	 * The results of the indexing to be displayed
	 *
	 * @type  string[]
	 * @since 1.0
	 */
	protected $results;

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
		JPluginHelper::importPlugin('elasticsearch');

		$types = array();

		// Trigger the index event.
		$this->results = JEventDispatcher::getInstance()->trigger('onElasticSearchIndexAll', array($types));

		// Display the template
		parent::display($tpl);
	}
}
