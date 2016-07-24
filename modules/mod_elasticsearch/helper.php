<?php
/**
 * @package		ElasticSearch
 * @subpackage	mod_elasticsearc
 * @copyright	Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die;

/**
 * @package		Joomla.Site
 * @subpackage	mod_elasticsearch
 * @since		1.0
 */
class ModElasticSearchHelper
{
	/**
	 * Display the search button as an image.
	 *
	 * @param	string	$button_text	The alt text for the button.
	 *
	 * @return	string	The HTML for the image.
	 * @since	1.0
	 */
	public static function getSearchImage($button_text)
	{
		$img = JHtml::_('image', 'searchButton.gif', $button_text, NULL, true, true);
		return $img;
	}
}
