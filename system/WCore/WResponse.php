<?php 
/**
 * WResponse.php
 */

defined('IN_WITY') or die('Access denied');

/**
 * WResponse does...
 *
 * @package System\WCore
 * @author Johan Dufau <johandufau@gmail.com>
 * @version 0.4-27-05-2013
 */
class WResponse {
	/**
	 * @var WTemplate Instance of WTemplate
	 */
	private $tpl;
	
	/**
	 * @var string Name of the theme used for the response
	 */
	private $theme_name;
	
	/**
	 * @var string Directory of the theme used for the response
	 */
	private $theme_dir;
	
	public function __construct($default_theme) {
		$this->tpl = WSystem::getTemplate();
		
		$this->setTheme($default_theme);
		
		// Default vars
		$site_name = WConfig::get('config.site_name');
		$this->tpl->assign('site_name', $site_name);
		$this->tpl->assign('page_title', $site_name);
	}
	
	/**
	 * Assigns a theme
	 * 
	 * @param string $theme theme name (must a be an existing directory in /themes/)
	 */
	public function setTheme($theme) {
		if ($theme == '_blank') {
			$this->theme_name = '_blank';
		} else if (is_dir(THEMES_DIR.$theme)) {
			$this->theme_name = $theme;
			$this->theme_dir = str_replace(WITY_PATH, '', THEMES_DIR).$theme.DS;
		} else {
			WNote::error('view_set_theme', "WView::setTheme(): The theme \"".$theme."\" does not exist.", 'plain');
		}
	}
	
	/**
	 * Returns current theme name
	 * 
	 * @return string current theme name
	 */
	public function getTheme() {
		return $this->theme_name;
	}
	
	/**
	 * Final render of the response
	 * Displays a valid HTML5 to the screen
	 * 
	 * @param WView $view The view to render as a main instance
	 */
	public function render(WView $view) {
		// Check headers
		$headers = $view->getHeaders();
		foreach ($headers as $name => $value) {
			header($name.': '.$headers['location']);
		}
		if (isset($headers['location'])) {
			return true;
		}
		
		// Check theme
		if (empty($this->theme_name)) {
			WNote::error('response_theme', "WResponse::render(): No theme given or it was not found.", 'plain');
		}
		
		// Flush the notes waiting for their own view
		$plain_view = WNote::getPlainView();
		if (!is_null($plain_view)) {
			$view = $plain_view;
			$this->setTheme('_blank');
		}
		
		// Select Theme main template
		if ($this->theme_name == '_blank') {
			$themeMainFile = str_replace(WITY_PATH, '', THEMES_DIR).'system'.DS.'_blank.html';
		} else {
			$themeMainFile = str_replace(WITY_PATH, '', THEMES_DIR).$this->theme_name.DS.'templates'.DS.'index.html';
		}
		
		// Handle notes
		$this->tpl->assign('notes', WNote::parse(WNote::get('*')));
		
		// Define {$include} tpl's var
		$this->tpl->assign('include', $view->render());
		
		$dir = WRoute::getDir();
		try {
			if (empty($dir)) {
				// Direct render
				$this->tpl->display($themeMainFile);
			} else {
				// Absolute links fix
				// If $dir is not the root file, then change links
				$html = $this->tpl->parse($themeMainFile);
				echo str_replace(
					array('src="/', 'href="/', 'action="/', 'data-link-modal="/'),
					array('src="'.$dir.'/', 'href="'.$dir.'/', 'action="'.$dir.'/', 'data-link-modal="'.$dir.'/'),
					$html
				);
			}
		} catch (Exception $e) {
			WNote::error(empty($dir) ? 'response_tpl_display' : 'response_tpl_parse', $e->getMessage(), 'die');
			return false;
		}
		
		return true;
	}
}

?>