<?php
/**
 * @package elasticsearch
 * @subpackage com_elasticsearch
 * @author Jean-Baptiste Cayrou and Adrien Gareau
 * @copyright Copyright 2013 CRIM - Computer Research Institute of Montreal
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
**/

defined('_JEXEC') or die;

// Load the ElasticSearch Conf.
JLoader::register('ElasticSearchConfig', JPATH_ADMINISTRATOR . '/components/com_elasticsearch/helpers/config.php');

/**
 * Adapter class for ElasticSearch plugins. 
 * All plugins must extend this class.
 *
 * @since  1.0
 */
abstract class ElasticSearchIndexerAdapter extends JPlugin
{
	/**
	 * The type of content the adapter indexes.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $type;

	/**
	 * The type ElasticSearch to display
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $type_display;

	/**
	 * Boost
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $boost = 1.0;


	/**
	 * The sublayout to use when rendering the results.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $layout;

	/**
	 * Client ElasticSearch
	 *
	 * @var    \Elastica\Client
	 * @since  1.0
	 */
	protected $elasticaClient;
	
	/**
	 * Type ElasticSearch
	 *
	 * @var    \Elastica\Type
	 * @since  1.0
	 */
	protected $elasticaType;

	/**
	 * The ElasticSearch index name
	 *
	 * @var   string
	 * @since 1.0
	 */
	private $index;

	/**
	 * Elastica index object
	 *
	 * @var   \Elastica\Index
	 * @since 1.0
	 */
	private $elasticaIndex;
	
	// current type= $type_+lang
	private $current_type;
	
	// Mapping of the type
	private $mapping;

	/**
	 * Exclude contents of the _source field after the document has been indexed, but before the _source field is stored
	 *
	 * @var   array
	 * @since 1.0
	 * @link  https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-source-field.html#include-exclude
	 */
	private $sourceExcludes;

	private $documents = array();

	/**
	 * The Joomla language. Must be a key of static::$langAnalyzer
	 *
	 * @var   string
	 * @since 1.0
	 */
	private $lang = array();

	/**
	 * Language mapping between Joomla and ElasticSearch
	 *
	 * @var   array
	 * @since 1.0
	 */
	private $langAnalyzer = array(
		'ar' => 'arabic',
		'da' => 'danish',
		'de' => 'german',
		'el' => 'greek',
		'en' => 'english',
		'es' => 'spanish',
		'fa' => 'persian',
		'fr' => 'french',
		'hu' => 'hungarian',
		'it' => 'italian',
		'nb' => 'Norwegian',
		'nl' => 'dutch',
		'pt' => 'portuguese',
		'ro' => 'Romanian',
		'ru' => 'russian',
		'sv' => 'swedish',
		'tr' => 'turkish',
		'zh' => 'chinese',
	);

	/**
	 * Constructor
	 *
	 * @param   object  &$subject  The object to observe
	 * @param   array   $config    An optional associative array of configuration settings.
	 *                             Recognized key values include 'name', 'group', 'params', 'language'
	 *                             (this list is not meant to be comprehensive).
	 *
	 * @since   1.0
	 */
	public function __construct(&$subject, $config)
	{
		// Call the parent constructor.
		parent::__construct($subject, $config);

		// Set configuration of ES
		$this->index = ElasticSearchConfig::getIndexName();

		//Check if type is set
		if($this->type==null)
		{
			throw new RuntimeException(
				JText::_('Error in an ElasticSearch Plugin, $this->type is null. It must be set in the plugin.')
			);
		}

		// Set ElasticSearch Client and Index
		$this->elasticaClient = ElasticSearchConfig::getElasticSearchClient();

		// Create Index if not exits
		$this->createIndex();

		// By default current_type = type
		$this->setLanguage(null);

		// Check for a layout override.
		if ($this->params->get('layout'))
		{
			$this->layout = $this->params->get('layout');
		}
	}

	/**
	 * Creates an analyser array
	 *
	 * @return array
	 *
	 * @since  1.0
	 */
	private function createAnalyzerArray()
	{
		$analyzers = array();

		foreach($this->langAnalyzer as $key => $ESlang)
		{
			$analyzers[$key . "_Analyzer"] = array(
				'tokenizer'	=> 'standard',
				'filter'  	=> array("standard", "lowercase", "delimiter", "edge", $key."_stop", "asciifolding"),
			);
		}

		$analyzers["default"] = array(
			'tokenizer'	=> 'standard',
			'filter'  	=> array("standard", "lowercase", "delimiter", "asciifolding"),
		);

		return $analyzers;
	}

	/**
	 * Creates a filter array
	 *
	 * @return array
	 *
	 * @since  1.0
	 */
	private function createFilterArray()
	{
		$filters = array();
		$filters["delimiter"] = array(
			'type' 		=> 'word_delimiter',
			'catenate_all'  => 'true',
			'preserve_original' =>'true',
			'split_on_numerics' =>'false',
		);

		$filters["edge"] = array(
			'type' 		=> 'edgeNGram',
			'min_gram'  => '3',
			'max_gram'  => '18',
			'side'      => 'front'
		);
			
		foreach($this->langAnalyzer as $key => $ESlang)
		{
			$filters[$key."_filter"] = array(
				'type' 		=> 'snowball',
				'language'  => $ESlang,
			);
			
			$filters[$key."_stop"] = array(
				'type' 		=> 'stop',
				'stopwords'  => '_'.$ESlang.'_',
			);
		}
			
		return $filters;
	}
	
	/**
	 * Create the ElasticSearch index if it does not exist
	 *
	 * @since  1.0
	 */
	private function createIndex()
	{
		$this->elasticaIndex = $this->elasticaClient->getIndex($this->index);

		// Get all Indexes
		$status = $this->elasticaClient->getStatus();

		if(!$status->indexExists($this->index))
		{
			$indexMapping= array(
				'number_of_shards' => 4,
				'number_of_replicas' => 1,
				'analysis' => array(
					'analyzer' 	=> $this->createAnalyzerArray(),
					'filter'	=> $this->createFilterArray(),
				)
			);

			$this->elasticaIndex->create($indexMapping);
		}
	}
	
	private function changeType()
	{
		$this->elasticaType = $this->elasticaIndex->getType($this->current_type);
	}
	
	/**
	 * Search with the $lang parameter, the name of this language in Joomla
	 *
	 * @param  string  $lang  The Joomla language constant (e.g. en-GB)
	 *
	 * @return string  The base language (e.g. en)
	 *
	 * @since  1.0
	 */
	private function getJoomlaLanguage($lang)
	{
		$Jlang = JFactory::getLanguage();

		foreach ($Jlang->getKnownLanguages() as $key => $knownLang)
		{
			// Check if $lang is a key else look into local list
			if($key==$lang || in_array($lang, explode(", ",$knownLang["locale"])))
			{
				// Slip to get the first part. en-GB becomes en
				$lang = explode('-',$key);
				return $lang[0];
			}
		}
		
		return "*";
	}
	
	/**
	 * Set the language
	 * Call this function before all indexing
	 * 
	 * @param   string  $lang  The Joomla language
	 *
	 * @since   1.0
	 */
	 protected function setLanguage($lang)
	 {
		 //Try to Transform $lang in Joomla Standard, return "*" if it failed
		 $this->lang = $this->getJoomlaLanguage($lang);

		if($this->lang != "*")
		{
			 $this->current_type = $this->type . '_' . $this->lang;
		}
		else
		{
			$this->current_type = $this->type;
		}

		// Change current type
		$this->changeType();
	 }
	 
	/**
	 * Set a mapping for a type
	 *
	 * @since  1.0
	 */
	protected function setMapping($mapping)
	{
		$this->mapping=$mapping;
	}

	protected function setSourceExclude($exclude)
	{
		$this->sourceExcludes = $exclude;
	}

	protected function typeExist($type)
	{
		$mapping = $this->elasticaIndex->getMapping();

		// If we haven't indexed before mappings don't exist. So return false.
		if (empty($mapping))
		{
			return false;
		}

		foreach ($mapping as $key => $map)
		{
			if ($key == $type)
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Method to define mapping
	 * 
	 * @return   void
	 * @since    1.0
	 */
	 protected function mapping()
	 {
		 // Set mapping only if mapping is set and if type does not exist
		 if ($this->mapping && !$this->typeExist($this->current_type))
		 {
		 	$ESmapping = new \Elastica\Type\Mapping($this->elasticaType, $this->mapping);

//			// If Language supported by ES
//			if (array_key_exists($this->lang, $this->langAnalyzer))
//			{
//				$ESmapping->setParam("index_analyzer", $this->lang . "_Analyzer");
//				$ESmapping->setParam("search_analyzer", $this->lang . "_Analyzer");
//			}
//			else
//			{
//				$ESmapping->setParam("index_analyzer", "default");
//				$ESmapping->setParam("search_analyzer", "default");
//			}
//
//			if ($this->boost == "")
//			{
//				$this->boost = 1.0;
//			}
//
//			$ESmapping->setParam('_boost', array('name' => 'boost', 'null_value' => $this->boost));

			if ($this->sourceExcludes)
			{
				$ESmapping->setSource(array('excludes' => $this->sourceExcludes));
			}

			// Send mapping to type
			$ESmapping->send();
		}
	 }

	
	/**
	 * Add a document
	 * 
	 * @param   \Elastica\Document   $document  The document to be added
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function addDocument($document)
	{
		// Auto dectect the language of the document zith the field language
		$lang=($document->__isset('language')) ? $document->language : '*' ;

		//Add boost field if does not exist
		if (!$document->__isset('boost')){
			$document->set('boost',$this->boost);
		}

		// Set the lang
		$this->setLanguage($lang);

		// Create mapping if not exists
		$this->mapping();
		
		// Add article to type
		$this->elasticaType->addDocument($document);

		// Refresh Index
		$this->elasticaType->getIndex()->refresh();
	}

	/**
	 * Add a document to the list. To index all the list execute flushDocuments
	 * 
	 * @param   \Elastica\Document  $document  The document to be added
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function pushDocument($document)
	{
		// Get language if exists
		$lang = ($document->__isset('language')) ? $document->language : '*' ;

		// Add boost field if does not exist
		if (!$document->__isset('boost')&&$this->boost)
		{
			$document->set('boost',$this->boost);
		}

		// Extract the first letter of the langue (en for en-GB)
		$explode = explode('-',$lang);
		$lang = $explode[0];

		// If it is the first of this language initialise array
		if (!array_key_exists($lang, $this->documents))
		{
			$this->documents[$lang] = array();
		}

		// Add document to the list
		$this->documents[$lang][] = $document;

		$mem_limit_bytes = trim(ini_get('memory_limit')) * 1024 * 1024;

		// Check memory use
		if (memory_get_usage() > $mem_limit_bytes*0.20)
		{
			// If documents array is too big we flush it
			$this->flushDocuments();
		}
	}

	/**
	 * Index all documents
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function flushDocuments()
	{
		foreach ($this->documents as $lang =>$list)
		{
			$this->setLanguage($lang);
			$this->addDocuments($list);
			unset($this->documents[$lang]); // Delete array
		}
	}

	/**
	 * Add documents
	 * 
	 * @param   \Elastica\Document[]  $documents
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	private function addDocuments(&$documents)
	{
		if (!$documents || sizeof($documents)==0)
		{
			return;
		}

		// Create mapping if it does not exist
		$this->mapping();
		
		// Add article to type
		$this->elasticaType->addDocuments($documents);

		// Refresh Index
		$this->elasticaType->getIndex()->refresh();
	}

	/**
	 * Check if a document exists in Elastica
	 *
	 * @return  bool  True if the document exists
	 *
	 * @since  1.0
	 */
	private function documentExists($id,$type)
	{
		$queryTerm = new \Elastica\Query\Terms();
		$queryTerm->setTerms('_id', array($id));
		$elasticaFilterType = new \Elastica\Filter\Type($type);
		$query = Elastica\Query::create($queryTerm);
		$query->setFilter($elasticaFilterType);

		// Perform the search
		$count = $this->elasticaIndex->count($query);

		return ($count>0) ? true : false;
	}

	/**
	 * Method to remove a document in ElasticSearch.
	 * The document will be removed in types of all language
	 *
	 * @param   int  $id  TODO
	 *
	 * @since  1.0
	 */
	protected function delete($id)
	{
		$jlang = JFactory::getLanguage();
	
		$EStype = $this->elasticaIndex->getType($this->type);

		if ($this->documentExists($id,$EStype->getName()))
		{
			$EStype->deleteById($id); // * language
		}

		$this->elasticaType->getIndex()->refresh();

		foreach ($jlang->getKnownLanguages() as $key => $knownLang)
		{	
			$explode = explode('-',$key);
			$key = $explode[0]; // Slip to get the first part. example : en-GB becomes en
			$EStype = $this->elasticaIndex->getType($this->type . "_" . $key);

			if ($this->documentExists($id,$EStype->getName()))
			{
				$EStype->deleteById($id);
			}

			$this->elasticaType->getIndex()->refresh();
		}
	}

	/**
	 * Intelligent highlighting
	 *
	 * @param   \Elastica\Result  $resultES  The data to display
	 *
	 * @return  string html correctly highlighted
	 *
	 * @since  1.0
	 */
	private function smartHighlight($resultES)
	{
		$data = $resultES->getData();
		$highlights = $resultES->getHighlights();
		$smart = array();

		// Get all field elasticsearch
		foreach($this->mapping as $field=>$map)
		{
			if(array_key_exists($field, $highlights))
			{
				// Get the first element because sort by score
				$text = $highlights[$field][0];

				// Remove include joomla
				$text = preg_replace('/{.+?}/', '', $text);

				// It is not the beginning
				if(substr_compare($data[$field],strip_tags($text),0,5))
				{
					$smart[$field]='... '.$text;
				}
				else{
					$smart[$field]=$text;
				}
				
			}
		}

		return $smart;
	}

	/**
	 * Method called to display elements of ElasticSearch result
	 * The view must be in the file elasticsearch/plg_name/view/type_name/default.php
	 *
	 * @param   string            $type        Result type
	 * @param   \Elastica\Result  $data        The result to display
	 * @param   string            $searchword  The searchword
	 * 
	 * @return  string  Html display of the element
	 *
	 * @since  1.0
	 */
	public function onElasticSearchDisplay($type, $data, $searchword)
	{
		// Check the type
		if ($type != $this->type)
		{
			return '';
		}
		
		$highlight = $this->smartHighlight($data);

		$path = JPATH_SITE . '/plugins/elasticsearch/' . $type;
		
		$view = new JViewLegacy(
			array(
				'name'=>'plg_' . $type,
				'base_path'=>$path,
			)
		);
		
		// Pass data to the view
		$view->assign('data', $data->getData());

		$view->assign('searchword', $searchword);

		$view->assign('highlight',$highlight);

		// Pass type to the view
		$view->assign('type', $type);
		
		return $view->loadTemplate();
	}


	/*
	 * Method to get types which will be highlighted 
	 * 
	 * @param   string  $context  The context of the content
	 * 
	 * @return array $field array which contains all types who will be highlight
	 *
	 * @since  1.0
	 */
	public function onElasticSearchHighlight()
	{
		$field=array();

		foreach ($this->mapping as $key=>$map)
		{
			if ($map['type'] == 'string')
			{
				$field[]=$key;
			}
		}

		return $field;
	}
	
	/**
	 * Return the type of content
	 * 
	 * @return array
	 * @since  1.0
	 */
	public function onElasticSearchType()
	{
		$infoType=array();
		$infoType['type'] = $this->type;
		$infoType['type_display'] = $this->type_display;
		
		return $infoType;
	}	
	
}
