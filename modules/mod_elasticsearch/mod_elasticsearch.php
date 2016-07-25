<?php
/**
 * @package elasticsearch
 * @subpackage mod_elasticsearch
 * @author Jean-Baptiste Cayrou and Adrien Gareau
 * @copyright Copyright 2013 CRIM - Computer Research Institute of Montreal
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

// no direct access
defined('_JEXEC') or die;

/** @var \Joomla\Registry\Registry $params */

// Include the syndicate functions only once
require_once dirname(__FILE__).'/helper.php';

$lang = JFactory::getLanguage();
$app = JFactory::getApplication();

if ($params->get('opensearch', 1))
{
	/** @var JDocumentHtml $doc */
	$doc = JFactory::getDocument();

	$ostitle = $params->get('opensearch_title', JText::_('MOD_SEARCH_SEARCHBUTTON_TEXT') . ' ' . $app->get('sitename'));
	$doc->addHeadLink(JUri::getInstance()->toString(array('scheme', 'host', 'port')) . JRoute::_('index.php?option=com_elasticsearch&format=opensearch'), 'search', 'rel', array('title' => htmlspecialchars($ostitle), 'type' => 'application/opensearchdescription+xml'));
}

$upper_limit = $lang->getUpperLimitSearchWord();

$button          = $params->get('button', '');
$imagebutton     = $params->get('imagebutton', '');
$button_pos      = $params->get('button_pos', 'left');
$button_text     = htmlspecialchars($params->get('button_text', JText::_('MOD_SEARCH_SEARCHBUTTON_TEXT')));
$width           = intval($params->get('width', 20));
$maxlength       = $upper_limit;
$text            = htmlspecialchars($params->get('text', JText::_('MOD_SEARCH_SEARCHBOX_TEXT')));
$label           = htmlspecialchars($params->get('label', JText::_('MOD_SEARCH_LABEL_TEXT')));
$set_Itemid      = intval($params->get('set_itemid', 0));
$moduleclass_sfx = htmlspecialchars($params->get('moduleclass_sfx'));

if ($imagebutton)
{
	$img = ModSearchHelper::getSearchImage($button_text);
}

$mitemid = $set_Itemid > 0 ? $set_Itemid : $app->input->getInt('Itemid');

require JModuleHelper::getLayoutPath('mod_elasticsearch', $params->get('layout', 'default'));
