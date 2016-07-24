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
 
// Get an instance of the controller prefixed by ElasticSearch
$controller =  JControllerLegacy::getInstance('ElasticSearch');

// Get the task
$task = JFactory::getApplication()->input->getString('task', "");
 
// Perform the Request task
$controller->execute($task);
 
// Redirect if set by the controller
$controller->redirect();
