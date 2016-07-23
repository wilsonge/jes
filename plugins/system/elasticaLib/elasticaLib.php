<?php
/**
 * @package elasticsearch
 * @subpackage plg_system_elasticaLib
 * @author Jean-Baptiste Cayrou and Adrien Gareau
 * @copyright Copyright 2013 CRIM - Computer Research Institute of Montreal
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
**/

defined('_JEXEC') or die;

/**
 * Elastic plugin class.
 * This class register and load Elastic library
 *
 * @since  1.0
 */
class plgSystemElasticaLib extends JPlugin
{
    /**
     * Method to register custom library.
     *
     * @return  void
	 *
	 * @since   1.0
     */
    public function onAfterInitialise()
    {
		require_once JPATH_LIBRARIES . '/Elastica/vendor/autoload.php';
    }
}
