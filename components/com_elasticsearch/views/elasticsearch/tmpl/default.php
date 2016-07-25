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

/** @var ElasticSearchViewElasticSearch $this */

?>
<div class="search">
	<form id="searchForm" action="<?php echo JRoute::_('index.php?option=com_elasticsearch');?>" method="get">

		<div class="btn-toolbar">
			<div class="btn-group pull-left">
				<input type="text" name="searchword" placeholder="<?php echo JText::_('COM_ELASTICSEARCH_SEARCH_KEYWORD'); ?>" id="search-searchword" size="30" maxlength="200" value="<?php echo $this->searchword; ?>" class="inputbox">
			</div>
			<div class="btn-group pull-left">
				<button name="Search" onclick="this.form.submit()" class="btn" title="" data-original-title="Search"><span class="icon-search"></span><?php echo JText::_('COM_SEARCH_SEARCH');?></button>
			</div>
			<div class="clearfix"></div>
		</div>
		<div class="searchintro">
			<?php if (!empty($this->searchword)):?>
				<p><?php echo JText::plural('COM_ELASTICSEARCH_SEARCH_KEYWORD_N_RESULTS', '<span class="badge badge-info">' . $this->totalResults . '</span>');?></p>
			<?php endif;?>
		</div>

		<?php if ($this->areas) : ?>
			<fieldset class="only">
			<legend><?php echo JText::_('COM_SEARCH_SEARCH_ONLY');?></legend>
			<?php foreach ($this->areas as $area) :
				$val = $area['type_display'];
				echo $val;
				//$checked = is_array($this->areas['type']) && in_array($val, $this->areas['active']) ? 'checked="checked"' : '';
			?>
			<input type="checkbox" name="<?php echo $area['type'];?>" value="<?php echo $val;?>" id="area-<?php echo $val;?>" />
				<label for="area-<?php echo $val;?>">
					<?php //echo JText::_($txt); ?>
				</label>
			<?php endforeach; ?>
			</fieldset>
		<?php endif; ?>

		<?php if ($this->totalResults > 0) : ?>

			<div class="form-limit">
				<label for="limit">
					<?php echo JText::_('JGLOBAL_DISPLAY_NUM'); ?>
				</label>
				<?php echo $this->pagination->getLimitBox(); ?>
			</div>
			<p class="counter">
				<?php echo $this->pagination->getPagesCounter(); ?>
			</p>

		<?php endif; ?>

	</form>

	<?php
	if ($this->totalResults > 0) :
		echo $this->loadTemplate('results');
	endif; ?>
</div>
