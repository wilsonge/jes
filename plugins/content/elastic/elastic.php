<?php
/**
 * @package elasticsearch
 * @subpackage plg_content_elastic
 * @author Jean-Baptiste Cayrou and Adrien Gareau
 * @copyright Copyright 2013 CRIM - Computer Research Institute of Montreal
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
**/
/**
 * Curl for php must be installed.
 * sudo apt-get install php5-curl for ubuntu
 **/

// no direct access
defined('_JEXEC') or die;

/**
 * ElasticSearch Content Plugin
 *
 * @since  1.0
 */		
class plgContentElastic extends JPlugin
{
	/**
	 * Elastic Search after save content method.
	 * Content is passed by reference, but after the save, so no changes will be saved.
	 * Method is called right after the content is saved.
	 *
	 * @param   string  $context  The context of the content passed to the plugin (added in 1.6)
	 * @param   object  $article  A JTableContent object
	 * @param   bool    $isNew    If the content has just been created
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function onContentAfterSave($context, $article, $isNew)
	{
		$dispatcher = JEventDispatcher::getInstance();
		JPluginHelper::importPlugin('elasticsearch');

		// Trigger the onFinderAfterSave event.
		$dispatcher->trigger('onElasticSearchAfterSave', array($context, $article, $isNew));
	}

	/**
	 * Elastic Search before save content method.
	 * Content is passed by reference. Method is called before the content is saved.
	 *
	 * @param   string  $context  The context of the content passed to the plugin (added in 1.6).
	 * @param   object  $article  A JTableContent object.
	 * @param   bool    $isNew    If the content is just about to be created.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function onContentBeforeSave($context, $article, $isNew)
	{
		$dispatcher = JEventDispatcher::getInstance();
		JPluginHelper::importPlugin('elasticsearch');

		// Trigger the onFinderBeforeSave event.
		$dispatcher->trigger('onElasticSearchBeforeSave', array($context, $article, $isNew));
	}

	/**
	 * Elastic Search after delete content method.
	 * Content is passed by reference, but after the deletion.
	 *
	 * @param   string  $context  The context of the content passed to the plugin (added in 1.6).
	 * @param   object  $article  A JTableContent object.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function onContentAfterDelete($context, $article)
	{
		$dispatcher = JEventDispatcher::getInstance();
		JPluginHelper::importPlugin('elasticsearch');

		// Trigger the onFinderAfterDelete event.
		$dispatcher->trigger('onElasticSearchAfterDelete', array($context, $article));
	}

	/**
	 * Elastic Search content state change method.
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
	public function onContentChangeState($context, $pks, $value)
	{
		$dispatcher = JEventDispatcher::getInstance();
		JPluginHelper::importPlugin('elasticsearch');

		// Trigger the onFinderChangeState event.
		$dispatcher->trigger('onElasticSearchChangeState', array($context, $pks, $value));
	}

	/**
	 * Elastic Search change category state content method.
	 * Method is called when the state of the category to which the
	 * content item belongs is changed.
	 *
	 * @param   string   $extension  The extension whose category has been updated.
	 * @param   array    $pks        A list of primary key ids of the content that has changed state.
	 * @param   integer  $value      The value of the state that the content has been changed to.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function onCategoryChangeState($extension, $pks, $value)
	{
		$dispatcher = JEventDispatcher::getInstance();
		JPluginHelper::importPlugin('elasticsearch');

		// Trigger the onFinderCategoryChangeState event.
		$dispatcher->trigger('onElasticSearchCategoryChangeState', array($extension, $pks, $value));
	}
}

