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
 * General Controller of ElasticSearch component
 *
 * @since  1.0
 */
class ElasticSearchController extends JControllerLegacy
{
	/**
	 * Typical view method for MVC based architecture
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached
	 * @param   array    $urlparams  An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *
	 * @return  static
	 *
	 * @since   1.0
	 */
	public function display($cachable = false, $urlparams = array())
	{
		// Set default view if not set
		$this->input->set('view', $this->input->getCmd('view', 'default'));

		return parent::display($cachable);
	}
}
