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

// Load the ElasticSearch Conf.
require_once JPATH_ADMINISTRATOR . '/components/com_elasticsearch/helpers/config.php';

/**
 * Default model
 *
 * @since  1.0
 */
class ElasticSearchModelDefault extends JModelItem
{
	/**
	 * Client ElasticSearch
	 *
	 * @var    \Elastica\Client
	 * @since  1.0
	 */
	protected $elasticaClient;

	/**
	 * The items returned from the search
	 *
	 * @var    array
	 * @since  1.0
	 */
	protected $items;

	/**
	 * Constructor
	 *
	 * @param   array  $config  An array of configuration options (name, state, dbo, table_path, ignore_request).
	 *
	 * @since   1.0
	 */
	public function __construct($config = array())
	{
		$this->elasticaClient = ElasticSearchConfig::getElasticSearchClient();

		parent::__construct($config);
	}

	/**
	 * Check if the elastic search server is connected
	 *
	 * @return  bool
	 *
	 * @since   1.0
	 */
	public function getIsConnected()
	{
		try
		{
			$this->elasticaClient->getStatus();
		}
		catch(Exception $e)
		{
			return false;
		}

		return true;
	}

	/**
	 * Get the status of the elastic search server
	 *
	 * @return  array  Response data array
	 *
	 * @since   1.0
	 */
	public function getStatus()
	{
		$status = $this->elasticaClient->getStatus();

		return $status->getServerStatus();
	}

	/**
	 * Get the items
	 *
	 * @return  array
	 *
	 * @since   1.0
	 */
	public function getItems()
	{
		if(!$this->items)
		{
			$this->items =array();

			$index = $this->elasticaClient->getIndex(ElasticSearchConfig::getIndexName());

			if($index->exists())
			{
				// First we get all types
				JPluginHelper::importPlugin('elasticsearch');
				$types = JEventDispatcher::getInstance()->trigger('onElasticSearchType', array());

				// Prepare an array index by type name
				foreach($types as $type)
				{
					$this->items[$type['type']] = array();
				}

				$mapping = $index->getMapping();

				//  Add language by type. Example : array('article' => array('en','fr'))
				$lang = array();

				// Array to count doc by type
				$counts = array();

				// Boost
				$boosts = array();

				foreach($mapping[ElasticSearchConfig::getIndexName()] as $key => $map)
				{
					$elasticType= $index->getType($key);
					$mapping = $elasticType->getMapping();
					$pos= strrpos($key,'_');

					if($pos)
					{
						// Add the language
						$type_base = substr($key,0,$pos);
						$type_lang = substr($key,$pos+1);
						$lang[$type_base][] = $type_lang;

						// Count
						$sum = (array_key_exists($type_base, $counts)) ? $counts[$type_base] : 0;
						$counts[$type_base] = $sum + $elasticType->count('');

						$boosts[$type_base] = $mapping[$key]["_boost"]["null_value"];

						if (!array_key_exists($type_base,$mapping[ElasticSearchConfig::getIndexName()]))
						{
							$mapping[ElasticSearchConfig::getIndexName()][$type_base];
						}
					}
					else
					{
						$boosts[$key] = $mapping[$key]["_boost"]["null_value"];
					}
				}

				// Get all types of the index
				foreach($this->items as $key => $map)
				{
					$elasticType= $index->getType($key);

					$count = $elasticType->count('');
					$langs = (array_key_exists($key, $lang)) ? $lang[$key] : array();
					$total = (array_key_exists($key, $lang)) ? $counts[$key]+$count :$count;
					$boost = (array_key_exists($key, $boosts)) ? $boosts[$key]: 1.0;
					$this->items[$key] = array('name' => $key, 'count' =>$total,'lang' =>$langs,"boost"=>$boost);
				}
			}
		}

		return $this->items;
	}

	/**
	 * Method to get the state of the ElasticSearch plug-ins.
	 *
	 * @return  stdClass[]  Array of relevant plug-ins and whether they are enabled or not.
	 *
	 * @since   1.0
	 */
	public function getPluginState()
	{
		$db = $this->getDbo();
		$query = $db->getQuery(true);

		$query->select($db->quoteName(array('name', 'enabled')));
		$query->from($db->quoteName('#__extensions'));
		$query->where($db->quoteName('type') . ' = ' .  $db->quote('plugin'));
		$query->where($db->quoteName('folder') . ' IN(' .  $db->quote('system') . ',' . $db->quote('content') . ')');
		$query->where($db->quoteName('element') . ' IN(' . $db->quote('elastic'). ',' . $db->quote('elasticaLib'). ')');
		$db->setQuery($query);

		return $db->loadObjectList('name');
	}
}
