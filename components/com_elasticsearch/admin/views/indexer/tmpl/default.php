<?php
/**
 * @package elasticsearch
 * @subpackage com_elasticsearch
 * @author Jean-Baptiste Cayrou and Adrien Gareau
 * @copyright Copyright 2013 CRIM - Computer Research Institute of Montreal
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
**/

// No direct access to this file
defined('_JEXEC') or die('Restricted Access');
?>

<h2><?php echo JText::_('COM_ELASTICSEARCH_INDEXES_UPDATED'); ?></h2>
<ul>
	<?php
		if (empty($this->results))
		{
			echo '<li>' . JText::_('COM_ELASTICSEARCH_NO_RESULTS_INDEXED') . '</li>';
		}
		else
		{
			foreach ($this->results as $result){
				echo "<li>$result</li>";
			}
		}
	?>


</ul>

