<?php

	/*------------------------------------------------------------------------
	# aixeena_clean_code.php - Aixeena CLean Code (plugin)
	# ------------------------------------------------------------------------
	# version		3.0.0
	# author    	Ciro Artigot for Aixeena.org
	# copyright 	Copyright (c) 2013 CiroArtigot. All rights reserved.
	# @license 		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
	# Websites 		http://aixeena.org/
	-------------------------------------------------------------------------
	*/
	
	
	// no direct access
	defined('_JEXEC') or die('Restricted access');

	jimport('joomla.plugin.plugin');
	$files = glob(JPATH_PLUGINS . '/system/aixeenaseoamp/classes/*.php');
	foreach ($files as $file) {
		require($file);   
	}

	class plgSystemAixeenaSeoAmp extends JPlugin {

	
		// ........................................................................................**** onBeforeCompileHead()
		function onBeforeCompileHead() {  
		
			$app	= JFactory::getApplication();
			if ($app->isAdmin()) return;
			$doc = JFactory::getDocument();
			$jinput = JFactory::getApplication()->input;
			$view = $jinput->get('view','');
			$option = $jinput->get('option','');
			$id = (int) $jinput->get('id',0);
			$uri 	= JFactory::getURI();
			
			$amp = $this->params->get('amppages',1);
			$isarticle = 0;
			if($view=='article' && $option=='com_content') $isarticle = 1;
			
	
			if($isarticle && $amp) {
				
				$segments = explode('/', trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'));
				$ampuri = '';
			
				if(isset($segments))  {
					$sw = 0;
					$lastsegment = '';
					foreach($segments as $segment) {
						$sw++;
						$lastsegment = $segment;
					}
			
					if($sw) $ampuri = str_replace($lastsegment, 'amp/'. $lastsegment, $uri->toString());
				} 
			
				if($ampuri) $doc->addHeadLink( $ampuri, 'amphtml', 'rel');
			}
	
			return true;
		}
		
		
		function onAfterRoute() {
	
	
			$app	= JFactory::getApplication();
			if ($app->isAdmin()) return;
			$jinput = JFactory::getApplication()->input;
			$option = JRequest::getVar('option','');
			$view = JRequest::getVar('view','');
			$id = (int) JRequest::getVar('id','');
			if($this->params->get('amppages',1)==0) return;
			
			$amp = 0;	
			$segments = explode('/', trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'));
			
			if(isset($segments))  {
				foreach($segments as $segment) {
					if(trim($segment)=='amp') $amp = 1;
				}
			}
		
			if($amp) {
				AixeenaAMP::print_AMPPage($option, $view, $id, $this->params);
				die();		
			}
			
			//echo $this->params->get('amppages',1); die();
	
			return;
		}
		
	
	
		
	}
?>