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
 * ElasticSearch Component Controller
 *
 * @since  2.0
 */
class ElasticSearchController extends JControllerLegacy
{
	public function search()
	{
		$keyword = $this->input->post->get('searchword', null);
	}
}
