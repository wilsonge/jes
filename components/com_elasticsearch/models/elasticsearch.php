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
require_once JPATH_ADMINISTRATOR . '/components/com_search/helpers/search.php';

/**
 * ElasticSearch Model
 * This model makes all search request to ElasticSearch.
 *
 */
class ElasticSearchModelElasticSearch extends JModelItem
{
	/**
	 * Client ElasticSearch
	 *
	 * @var    \Elastica\Client
	 * @since  1.0
	 */
	protected $elasticaClient;

	/**
	 * Index ElasticSearch
	 *
	 * @var    \Elastica\Index
	 * @since  1.0
	 */
	protected $elasticaIndex;

	protected $searchFields;

	protected $results;

	protected $totalHits;

	protected $areas;
	
	protected $elements = array();

	public function __construct($config = array())
	{
		parent::__construct($config);

		// Create a elastica Client
		$this->elasticaClient = ElasticSearchConfig::getElasticSearchClient();

		// Set Index
		$this->elasticaIndex = $this->elasticaClient->getIndex(ElasticSearchConfig::getIndexName());
	}

	/**
	 * Method to get the different types enabled in plugins
	 *
	 * @return array  Array format of array( 0 => Array('type'=>'foo','type_display'=>'bar') ....)
	 *
	 * @since  1.0
	 */
	public function getSearchAreas()
	{
		if(!$this->areas)
		{
			JPluginHelper::importPlugin('elasticsearch');
			$this->areas = JEventDispatcher::getInstance()->trigger('onElasticSearchType', array());
		}

		return $this->areas;
	}

	/**
	 * Method to get the different types which will be highlighted in enabled in plugins
	 *
	 * @return mixed
	 *
	 * @since version
	 */
	public function getSearchFields()
	{
		if(!$this->searchFields)
		{
			$this->searchField = array();

			foreach ($this->getHighlightFields() as $fields)
			{
				foreach ($fields as $field)
				{
					$this->searchFields[] = $field;
				}
			}
		}

		return $this->searchFields;
	}

	/**
	 * Request the plugins enabled to know what is fields we need to active the highlighting
	 *
	 * @since  1.0
	 */
	private function getHighlightFields()
	{
		JPluginHelper::importPlugin('elasticsearch');
		$fields = JEventDispatcher::getInstance()->trigger('onElasticSearchHighlight');

		return $fields;
	}

	/**
	 * Main method to make a search
	 *
	 * @since  1.0
	 */
	public function search()
	{
		$this->getSearchFields();
		$input = JFactory::getApplication()->input;
		$word = $input->get->getString('searchword', null);
		$offset = $input->get->getInt('start', 0);
		$limit = $input->get->getInt('limit', 10);

		// No limit
		if($limit==0)
		{
			$limit=10000;
		}

		$this->getSearchAreas();

		$elasticaQueryString = new \Elastica\Query\QueryString();

		// Log search word only on the first page
		if($offset==0)
		{
			JSearchHelper::logSearch($word, 'com_elasticsearch');
		}

		// Convert accents
		$word = htmlentities($word, ENT_NOQUOTES, 'utf-8');
		$word = preg_replace('#\&([A-za-z])(?:uml|circ|tilde|acute|grave|cedil|ring)\;#', '\1', $word);
		$word = preg_replace('#\&([A-za-z]{2})(?:lig)\;#', '\1', $word);
		$word = preg_replace('#\&[^;]+\;#','', $word);

		// Check if there are quotes ( for exact search )
		$exactSearch=false;

		if (strlen($word)>1&&$word[0]=='"'&&$word[strlen($word)-1]=='"')
		{
			$exactSearch=true;
			$word=substr($word, 1,strlen($word)-2); // Remove external "
		}

		$word = Elastica\Util::replaceBooleanWordsAndEscapeTerm($word); // Escape ElasticSearch specials char

		if($exactSearch)
		{
			$word='"'.$word.'"';
		}

		if($word=="") // Empty search
		{
			$word= "*";
		}

		$elasticaQueryString->setQuery($word);

		// Create the actual search object with some data.
		$elasticaQuery = new Elastica\Query();
		$elasticaQuery->setQuery($elasticaQueryString);

		if (ElasticSearchConfig::getHighlightEnable())
		{
			$fields= $this->getHighlightFields();

			$hlfields=array();
			foreach($fields as $field)
			{
				foreach($field as $highlight)
				{
					$hlfields[] = array(
						$highlight => array(
							'fragment_size' => 1000,
							'number_of_fragments' => 1,
						)
					);
				}
			}

			$highlightFields = array(
				'pre_tags' => array(ElasticSearchConfig::getHighlightPre()),
				'post_tags' => array(ElasticSearchConfig::getHighlightPost()),
				"order" => "score",
				'fields'    => $hlfields
			);

			$elasticaQuery->setHighlight($highlightFields);
		}

		// Set offset and limit for pagination
		$elasticaQuery->setFrom($offset);
		$elasticaQuery->setLimit($limit);

		//Create a filter for _type
		$elasticaFilterype=$this->createFilterType($this->areas);

		// Add filter to the search object.
		$elasticaQuery->setFilter($elasticaFilterype);

		// Search on the index.
		$elasticaResultSet = $this->elasticaIndex->search($elasticaQuery);

		$this->results = $elasticaResultSet->getResults();

		$this->totalHits = $elasticaResultSet->getTotalHits();
	}

	public function getHighlightElem()
	{
		return $this->elements;
	}
		
	public function getResults()
	{
		return $this->results;
	}


	public function getTotalHits()
	{
		return $this->totalHits;
	}

	/**
	 * Method to create a filter by ES Type for all type.
	 * For example if the search if just for article,
	 * the filter we be on article, article_en-GB etc for each language
	 *
	 * @since  1.0
	 */
	private function createFilterType($plg_types)
	{
		// By default search in all types
		$all_types=true;

		$elasticaFilterType = new \Elastica\Filter\Terms("_type");

		//  Foreach existing ES types
		foreach($plg_types as $area )
		{
			// Check if this type is enable for the search
			$check = JFactory::getApplication()->input->get->getString($area['type'], null);

			if($check !="") // If enabled
			{
				$all_types=false;

				// Generate all type with language extension type_en-GB etc.
				$langTypes = $this->getTypesWithLang($area['type']);

				foreach($langTypes as $type)
				{
					$elasticaFilterType->addTerm($type);
				}
			}
		}

		// If all_type still true, the search is for all content type
		if($all_types)
		{
			foreach($this->areas as $area )
			{
				$langTypes =  $this->getTypesWithLang($area['type']);

				foreach($langTypes as $type)
				{
					$elasticaFilterType->addTerm($type);
				}
			}
		}

		return $elasticaFilterType;
	}
		
	/**
	 * Method to generate all (language) types of a type
	 *
	 * @since  1.0
	 */
	private function getTypesWithLang($type)
	{
		$Jlang = JFactory::getLanguage();

		$types = array();
		$types[] = $type; // Add the type for " * " language
		$explode = explode('-',$Jlang->getTag());
		$types[] = $type."_".$explode[0]; // And the current language

		return $types;
	}
}
