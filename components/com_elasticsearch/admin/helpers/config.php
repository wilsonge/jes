<?php
/**
 * @package elasticsearch
 * @subpackage com_elasticsearch
 * @author Jean-Baptiste Cayrou and Adrien Gareau
 * @copyright Copyright 2013 CRIM - Computer Research Institute of Montreal
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

/**
 * ElasticSearchConfig
 * Contains static methods to get configuration. 
 *
 * @since  1.0
 */
class ElasticSearchConfig
{
	/**
	 * Get the client object
	 *
	 * @var    \Elastica\Client
	 * @since  1.0
	 */
	public static $elasticaClient;

	public static function getHostServer()
	{
		return JComponentHelper::getParams('com_elasticsearch')->get('host');
	}
	
	public static function getPortServer()
	{
		return JComponentHelper::getParams('com_elasticsearch')->get('port');
	}

	public static function getIndexName()
	{
		return JComponentHelper::getParams('com_elasticsearch')->get('index', 'joomla');
	}

	/*
	 * Get the elastica library client object
	 *
	 * @return \Elastica\Client
	 *
	 * @since  1.0
	 */
	public static function getElasticSearchClient()
	{
		if (!static::$elasticaClient)
		{
			static::$elasticaClient = new \Elastica\Client(array(
				'host' => ElasticSearchConfig::getHostServer(),
				'port' => ElasticSearchConfig::getPortServer()
			));
		}

		return static::$elasticaClient;
	}

	/*
	 * Tag before a word highlighted
	 *
	 * @since  1.0
	 */
	public static function getHighlightPre()
	{
		return JComponentHelper::getParams('com_elasticsearch')->get('highlightPre', '<b>');
	}
	
	/*
	 * Tag after a word highlighted
	 *
	 * @since  1.0
	 */
	public static function getHighlightPost()
	{
		return JComponentHelper::getParams('com_elasticsearch')->get('highlightPost', '</b>');
	}

	/*
	 * Tag after a word highlighted
	 *
	 * @since  1.0
	 */
	public static function getHighlightEnable()
	{
		return JComponentHelper::getParams('com_elasticsearch')->get('highlightEnable', true);
	}
}
