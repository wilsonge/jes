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

JLoader::register('JHtmlString', JPATH_LIBRARIES.'/joomla/html/html/string.php');

// Load the ElasticSearch Conf.
require_once JPATH_ADMINISTRATOR . '/components/com_elasticsearch/helpers/config.php';

/**
 * Helper for ElasticSearch Component
 *
 * @since  1.0
 */
class ElasticSearchHelper
{
	/**
	 * Return HTML of a single result Elastic in argument
	 * 
	 * @var   \Elastica\Result  $item        The Elastic Result
	 * @var   string            $searchword  The string searched for
	 *
	 * @return   string  The html of the item
	 *
	 * @since  1.0
	 */
	public static function getHTML($item, $searchword)
	{
		$html='';
		$type = $item->getType();

		// Remove the suffix _*
		$pos= strrpos($type,'_');

		if($pos)
		{
			$type = substr($type,0,$pos);
		}

		JPluginHelper::importPlugin('elasticsearch');

		// Trigger the index event.
		$results = JEventDispatcher::getInstance()->trigger('onElasticSearchDisplay', array($type, $item, $searchword));

		foreach ($results as $result)
		{
			if ($result)
			{
				$html=$result;
			}
		}

		return $html;
	}

	/**
	 * Function to truncate the highlight result of elasticsearch
	 * It will search the first highlighted word and start to this sentence to the limit characters param
	 *
	 * @param   string  $text   The text to be displayed
 	 * @param   int     $limit  Total character limit
	 *
	 * @return string html of the item
	 *
	 * @since  1.0
	 */
	public static function truncateHighLight($text,$limit)
	{
		preg_match ('/'.preg_quote(ElasticSearchConfig::getHighlightPre()).'/', $text ,$matches, PREG_OFFSET_CAPTURE);

		if($matches)
		{
			$posPreTag = $matches[0][1];

			// If pos if at the beginning it is not necessary to truncate the left part
			if($posPreTag>$limit*0.75)
			{
				$pos = strpos($text," ",$posPreTag-$limit*0.3);

				$text = substr($text,$pos);
				$text='... '.$text;
			}

			return JHtmlString::truncateComplex($text, $limit + 4, true);
		}

		return $text;
	}
}
