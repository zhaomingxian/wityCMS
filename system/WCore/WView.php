<?php defined('IN_WITY') or die('Access denied');
/**
 * Wity CMS
 * Système de gestion de contenu pour tous.
 *
 * @desc Classe responsable de l'affichage final
 * @version $Id: WCore/WView.php 0001 11-07-2012 Fofif $
 */

class WView {
	public $tpl;
	
	// Theme to be loaded
	private $themeName;
	
	// Template file to execute
	private $tplFile;
	
	// Variables with a special treatment
	private $specialVars = array('css', 'js');
	
	// Template variables
	private $vars = array();
	
	public function __construct() {
		$this->tpl = WSystem::getTemplate();
		
		// Default page name = siteName
		$this->assign('pageTitle', WConfig::get('config.siteName'));
		
		// Détection du thème à charger
		$theme = WConfig::get('config.theme');
		if (!empty($theme)) {
			$this->setTheme($theme);
		}
	}
	
	/**
	 * Assign a theme
	 * Must a be an existing directory in /themes/
	 */
	public function setTheme($theme) {
		if ($theme == 'null') {
			$this->themeName = 'null';
		} else if (is_dir(THEMES_DIR.$theme)) {
			$this->themeName = $theme;
		} else {
			throw new Exception("WView::setTheme() : Theme '".$theme."' does not exist.");
		}
	}
	
	public function getTheme() {
		return $this->themeName;
	}
	
	public function assignOne($name, $value) {
		// $name is a Special var
		if (in_array($name, $this->specialVars)) {
			if (!isset($this->vars[$name])) {
				$this->vars[$name] = array($value);
			} else if (!in_array($value, $this->vars[$name])) {
				$this->vars[$name][] = $value;
			}
		} else { // Normal case
			$this->vars[$name] = $value;
		}
	}
	
	public function assign($a, $b = null) {
		if (is_string($a)) {
			$this->assignOne($a, $b);
		} else if (is_array($a)) {
			foreach ($a as $key => $value) {
				$this->assignOne($key, $value);
			}
		}
	}
	
	/**
	 * Retourne une variable en "stack" avec un traitement particulier
	 * @param $stackName Nom du stack
	 * @return string
	 */
	public function getStack($stackName) {
		if (empty($this->vars[$stackName])) {
			return '';
		}
		
		switch ($stackName) {
			case 'css':
				$css = $this->tpl->getVar('css');
				foreach ($this->vars['css'] as $file) {
					$css .= sprintf(
						'<link href="%s%s" rel="stylesheet" type="text/css" />'."\n", 
						(dirname($file) == '.') ? THEMES_DIR.$this->themeName.DS.'css'.DS : '',
						$file
					);
				}
				return $css;
				break;
			
			case 'js':
				$script = $this->tpl->getVar('js');
				foreach ($this->vars['js'] as $file) {
					$script .= sprintf(
						'<script type="text/javascript" src="%s%s"></script>'."\n", 
						(dirname($file) == '.') ? THEMES_DIR.$this->themeName.DS.'js'.DS : '',
						$file
					);
				}
				return $script;
				break;
			
			default:
				return $this->tpl->getVar($stackName).$this->vars[$stackName];
				break;
		}
	}
	
	public function setTplFile($file) {
		if (file_exists($file)) {
			$this->tplFile = $file;
		} else {
			throw new Exception("WView::setTpl() : le fichier '".$file."' est introuvable.");
		}
	}
	
	/**
	 * Recherche un fichier template en fonction du nom de l'appli et de l'action
	 * Le fichier sera cherché en priorité dans les fichiers du thème puis dans les fichiers de l'appli
	 * @return string adresse du fichier
	 */
	public function findTplFile($appName, $action, $admin) {
		$tplHref = THEMES_DIR.$this->getTheme().DS.'templates'.DS.$appName.DS.$action.'.html';
		if (!$admin && file_exists($tplHref)) {
			$this->setTplFile($tplHref);
			return true;
		} else {
			$endDir = $admin ? 'admin' : 'front';
			$tplHref = $appView = APPS_DIR.$appName.DS.$endDir.DS.'templates'.DS.$action.'.html';
			if (file_exists($tplHref)) {
				$this->setTplFile($tplHref);
				return true;
			}
		}
		return false;
	}
	
	public function getTpl() {
		return $this->tplFile;
	}
	
	/**
	 * Render the view
	 */
	public function render() {
		if (empty($this->tplFile)) {
			throw new Exception("WView::render() : No template file given.");
		}
		
		if (empty($this->themeName)) {
			throw new Exception("WView::render() : No theme given or it was not found.");
		}
		
		// Treat "special vars"
		foreach ($this->specialVars as $stack) {
			if (!empty($this->vars[$stack])) {
				$this->vars[$stack] = $this->getStack($stack);
			} else {
				unset($this->vars[$stack]);
			}
		}
		
		// Assign variables
		$this->tpl->assign($this->vars);
		
		if ($this->themeName == 'null') {
			$themeMainFile = $this->tplFile;
		} else {
			// Define {$include} tpl's var
			$this->tpl->assign('include', $this->tplFile);
			
			$themeMainFile = THEMES_DIR.$this->themeName.DS.'templates'.DS.'index.html';
		}
		
		$base = WRoute::getDir();
		if ($base == '/') {
			// Direct render
			$this->tpl->display($themeMainFile);
		} else {
			// Absolute links fix
			// If $base is not the root file, then change links
			$html = $this->tpl->parse($themeMainFile);
			echo str_replace(
				array('src="/', 'href="/', 'action="/'),
				array('src="'.$base, 'href="'.$base, 'action="'.$base),
				$html
			);
		}
	}
}
?>
