<?php
/**
 * @package		Joomla.Plugin
 * @subpackage	Content.loadmodule
 * @copyright	Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die;

class plgContentWufoo extends JPlugin
{
	protected static $options = array();
		
	/**
	 * Plugin that loads module positions within content
	 *
	 * @param	string	The context of the content being passed to the plugin.
	 * @param	object	The article object.  Note $article->text is also available
	 * @param	object	The article params
	 * @param	int		The 'page' number
	 */
	public function onContentPrepare($context, &$article, &$params, $page = 0)
	{
		// Don't run this plugin when the content is being indexed
		if ($context == 'com_finder.indexer') {
			return true;
		}

		// simple performance check to determine whether bot should process further
		if (strpos($article->text, 'wufoo') === false) {
			return true;
		}

		//$regex		= '/\[wufoo\s+(.*?)\]/i';
		$regex		= '/{wufoo\s+(.*?)}/i';
		
		// Find all instances of plugin and put in $matches for loadposition
		// $matches[0] is full pattern match, $matches[1] is the position
		preg_match_all($regex, $article->text, $matches, PREG_SET_ORDER);
		// No matches, skip this
		if ($matches) {
			
			foreach ($matches as $match) {
				
				$this->setOptions($match[1]);
				
				if(!$this->options['username'] || $this->options['formhash'] == '') {
					$output = '<strong style="color: red">Form Error - Something is wrong with your shortcode! 
						If you copied it from the <a href="http://wufoo.com/docs/code-manager/">Wufoo Code Manager</a>, all should be well.</strong>';
				} else {
					$output = $this->_loadForm();
				}
				// We should replace only first occurrence
				//var_dump($match[0]);
				$article->text = preg_replace("|$match[0]|", $output, $article->text, 1);
			}
		}
		
	}
	
	protected function _loadForm()
	{
		$output = '<div id="wufoo-'.$this->options['formhash'].'">'.
					'<a href="http://'.$this->options['username'].'.wufoo.com/forms/">Please click here to fill out the form</a>.'.
				'</div>';
		$output .= $this->loadJs();
		return $output;
	}
	
	protected function setOptions($options) {
		
		$this->options = array(
			'formhash'		=> '',
			'height'		=> '500',
			'autoresize'	=> true,
			'header'		=> 'show',
			'ssl'			=> true,
			'defaultv'		=> ''	
		);
		
		$newoptions = preg_replace("/\s+/", "|", $options);
		$newoptions = explode("|", trim($newoptions));
		foreach($newoptions as $option) {
			if($option = explode('=', str_replace('"', '', $option))) {
				$this->options[$option[0]] = $option[1];
			}
		}
		if(!array_key_exists('username', $this->options)) {
			$this->options['username'] = $this->params->def('username', null);
		}
		
	}
	
	protected function loadJs() { 
		
		$use_ssl = $this->params->def('use_ssl', 'true') == 'true' ? true : false;
		ob_start(); ?>
		<script type="text/javascript">var <?php echo $this->options['formhash'] ?>;(function(d, t) {
		var s = d.createElement(t), options = {
			'userName':'<?php echo $this->options['username'] ?>', 
			'formHash':'<?php echo $this->options['formhash'] ?>', 
			'autoResize':<?php echo $this->options['autoresize'] ?>,
			'height':'<?php echo $this->options['height'] ?>',
			'async':true,
			'header':'<?php echo $this->options['header'] ?>'<?php echo $use_ssl ? ',': '};' ?>
<?php if($use_ssl) { ?>
'ssl':<?php echo $this->options['ssl'] ?>};
<?php } ?> 
		s.src = ('https:' == d.location.protocol ? 'https://' : 'http://') + 'wufoo.com/scripts/embed/form.js';
		s.onload = s.onreadystatechange = function() {
		var rs = this.readyState; if (rs) if (rs != 'complete') if (rs != 'loaded') return;
		try { <?php echo $this->options['formhash']; ?> = new WufooForm();<?php echo $this->options['formhash']; ?>.initialize(options);<?php echo $this->options['formhash']; ?>.display(); } catch (e) {}};
		var scr = d.getElementsByTagName(t)[0], par = scr.parentNode; par.insertBefore(s, scr);
})(document, 'script');</script> 
		<?php 
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}
	
}
