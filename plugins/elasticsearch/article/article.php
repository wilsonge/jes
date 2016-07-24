<?php
/**
 * @package elasticsearch
 * @subpackage plg_elasticsearch_article
 * @author Jean-Baptiste Cayrou and Adrien Gareau
 * @copyright Copyright 2013 CRIM - Computer Research Institute of Montreal
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 **/

// No direct access
defined('_JEXEC') or die;

jimport( 'joomla.application.component.view' );

require_once JPATH_SITE.'/components/com_content/router.php';
require_once JPATH_SITE.'/components/com_content/helpers/route.php';

// Load the base adapter.
require_once JPATH_ADMINISTRATOR . '/components/com_elasticsearch/helpers/adapter.php';

/**
 * ElasticSearch adapter for com_content.
 *
 * @since  1.0
 */
class PlgElasticsearchArticle extends ElasticSearchIndexerAdapter
{
	/**
	 * The type ElasticSearch of content which will be indexed
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $type = 'article';

	/**
	 * The type ElasticSearch to display
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $type_display = 'Article';

	/**
	 * Constructor
	 *
	 * @param   object  &$subject  The object to observe
	 * @param   array   $config    An array that holds the plugin configuration
	 *
	 * @since   1.0
	 */
	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);

		// Set boost
		$this->boost=$this->params->get('boost');

		// Doc here : http://www.elasticsearch.org/guide/reference/mapping/core-types/
		$mapping= 	array(
			'id'      				 => array('type' => 'integer',
				'include_in_all' => FALSE,
				'index' => 'not_analyzed'),
			'title'    			 	 => array('type' => 'string',
				'include_in_all' => TRUE,
				'boost'=> 1.5),
			'introtext'    			 => array('type' => 'string', 'include_in_all' => TRUE),
			'fulltext'    			 => array('type' => 'string', 'include_in_all' => TRUE),
			'created_by_alias'       => array('type' => 'string', 'include_in_all' => TRUE),
			'categories' 			 => array('type' => 'string', 'include_in_all' => TRUE),
			'language' 				 => array('type' => 'string',
				'include_in_all' => FALSE,
				'index' => 'not_analyzed'),
			'href'   				 => array('type' => 'string', 'include_in_all' => FALSE),
			'created_at'  			 => array('type' => 'date', 'include_in_all' => FALSE),
			'feature'			 	 => array('type' => 'integer', 'include_in_all' => FALSE),
			'boost' 				 => array('type' => 'float', 'include_in_all' => FALSE),
		);

		$this->setMapping($mapping);
	}

	/**
	 * Method to remove an article in ElasticSearch when it is deleted
	 *
	 * @param   string  $context  The context of the action being performed.
	 * @param   JTable  $table    A JTable object containing the record to be deleted
	 *
	 * @return  boolean  True on success.
	 * @since   1.0
	 */
	public function onElasticSearchAfterDelete($context, $table)
	{
		// Skip plugin if we are deleting something other than article
		if ($context != 'com_content.article')
		{
			return false;
		}

		$this->delete($table->id);

		return true;
	}

	/**
	 * Method to determine if the access level of an item changed.
	 *
	 * @param   string   $context  The context of the content passed to the plugin.
	 * @param   JTable   $row      A JTable object
	 * @param   boolean  $isNew    If the content has just been created
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   1.0
	 * @throws  Exception on database error.
	 */
	public function onElasticSearchAfterSave($context, $row, $isNew)
	{
		// Skip plugin if we are saving something other than article
		if ($context != 'com_content.article')
		{
			return true;
		}

		// Delete the document in elasticsearch (if language changed)
		$this->delete($row->id);

		// If this article is published
		if($row->state == 1)
		{
			$document = $this->rowToDocument($row);
			$this->addDocument($document);
		}

		return true;
	}

	/**
	 * ElasticSearch change state content method
	 * Method to update the link information for items that have been changed
	 * from outside the edit screen. This is fired when the item is published,
	 * unpublished, archived, or unarchived from the list view.
	 *
	 * @param   string   $context  The context for the content passed to the plugin.
	 * @param   array    $pks      A list of primary key ids of the content that has changed state.
	 * @param   integer  $value    The value of the state that the content has been changed to.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function onElasticSearchChangeState($context, $pks, $value)
	{
		// Skip plugin if we are saving something other than article
		if ($context != 'com_content.article')
		{
			return;
		}

		// Get all articles modifies
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('*');
		$query->from('#__content c');
		$query->where('c.id IN ('.implode(',',$pks).')');
		$db->setQuery((string)$query);
		$articles = $db->loadObjectList();

		foreach($articles as $article)
		{
			$this->delete($article->id);

			// If this article is published
			if($article->state == 1)
			{
				$document = $this->rowToDocument($article);
				$this->pushDocument($document);
			}
		}

		$this->flushDocuments();
	}

	/**
	 * Convert an article to an elastica document
	 *
	 * @param   stdClass|JTable  $row  The entity object
	 *
	 * @return  \Elastica\Document
	 *
	 * @since  1.0
	 */
	private function rowToDocument($row)
	{
		$id = $row->id;

		// Create a date object
		$date = new DateTime($row->created);

		// Get the names of the categories
		$category = JCategories::getInstance('Content')->get($row->catid);
		$categories = array();

		while ($category && $category->id > 1)
		{
			$categories[] = $category->title;
			$category = $category->getParent();
		}

		// Create a document
		$entity = array(
			'id'               => $id,
			'title'            => html_entity_decode(strip_tags($row->title), ENT_COMPAT | ENT_HTML401,'UTF-8'),
			'introtext'        => html_entity_decode(strip_tags($row->introtext), ENT_COMPAT | ENT_HTML401,'UTF-8'),
			'fulltext'         => html_entity_decode(strip_tags($row->fulltext), ENT_COMPAT | ENT_HTML401,'UTF-8'),
			'created_by_alias' => $row->created_by_alias,
			'language'         => $row->language,
			'categories'       => implode(';',$categories),
			'created_at'       => $date->format('Y-m-d\Th:i:s'),
			'href'             => ContentHelperRoute::getArticleRoute($row->id),
			'feature'          => $row->featured
		);

		$document = new \Elastica\Document($id, $entity);

		return $document;
	}

	/**
	 * Method called to index all contents
	 *
	 * @param   array  $types  The array of types
	 *
	 * @return  string  The elastic type to display
	 *
	 * @since  1.0
	 */
	public function onElasticSearchIndexAll($types)
	{
		// Get all articles
		$db = JFactory::getDbo();

		$query = $db->getQuery(true);
		$query->select('*');
		$query->from($db->quoteName('#__content'));
		$db->setQuery($query);
		$articles = $db->loadObjectList();

		foreach ($articles as $article)
		{
			// If this article is published
			if ($article->state == 1)
			{
				$document = $this->rowToDocument($article);
				$this->pushDocument($document);
			}
		}

		$this->flushDocuments();

		return $this->type_display;
	}

	/**
	 * Return the type of content
	 *
	 * @return  array
	 * @since   1.0
	 */
	public function onElasticSearchType()
	{
		$infoType=array();
		$infoType['type'] = $this->type;
		$infoType['type_display'] = $this->type_display;

		return $infoType;
	}
}
