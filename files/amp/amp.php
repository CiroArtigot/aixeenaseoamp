<?php
/*------------------------------------------------------------------------
# aixeenaseamp.php - Aixeena SEO AMP Pages (plugin)
# ------------------------------------------------------------------------
# version		1.0.0
# author    	Ciro Artigot for Aixeena.org
# copyright 	Copyright (c) 2018 CiroArtigot. All rights reserved.
# @license 		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
# Websites 		http://aixeena.org/
-------------------------------------------------------------------------
*/

// no direct access
defined('_JEXEC') or die('Restricted access');


class AixeenaAMP {
	
	public static function print_AMPPage($option, $view, $id, $params) {
	
	
		$app = JFactory::getApplication();
		$document = JFactory::getDocument();
		if ($app->isAdmin()) return;
		if ($document->getType() != 'html') return;
		require_once JPATH_SITE.'/components/com_content/helpers/route.php';
		JLoader::register('TagsHelperRoute', JPATH_BASE . '/components/com_tags/helpers/route.php');
		$user	= JFactory::getUser();
		$db = JFactory::getDbo();
		$groups	= implode(',', $user->getAuthorisedViewLevels());
		$nullDate	= $db->Quote($db->getNullDate());
		$nowDate	= $db->Quote(JFactory::getDate()->toSql());
		
		$query = $db->getQuery(true);
		$query->select('a.id, a.title, a.alias, a.introtext, a.fulltext, a.attribs, a.catid, a.created, a.created_by, a.created_by_alias, CASE WHEN a.publish_up = ' . $db->q($db->getNullDate()) . ' THEN a.created ELSE a.publish_up END as publish_up, a.images, a.urls, a.attribs, a.hits, a.modified');
		$query->from('#__content AS a');	
		$query->select('c.title AS category_title, c.path AS category_route, c.access AS category_access, c.alias AS category_alias');
		$query->join('LEFT', '#__categories AS c ON c.id = a.catid');
		$query->select("CASE WHEN a.created_by_alias > ' ' THEN a.created_by_alias ELSE ua.name END AS author");
		$query->select("ua.email AS author_email");
		$query->select("ua.username AS username");
		$query->select("ua.name AS autor");
		$query->join('LEFT', '#__users AS ua ON ua.id = a.created_by');
		$query->where('a.access IN ('.$groups.')');
		$query->where('c.access IN ('.$groups.')');
		$query->where(' a.state=1 AND (a.publish_up = '.$nullDate.' OR a.publish_up <= '.$nowDate.')');
		$query->where('(a.publish_down = '.$nullDate.' OR a.publish_down >= '.$nowDate.')');
		$query->where('a.id='.$id);	
		$db->setQuery($query);
		$article = $db->loadObject();	
		
		//echo $query; 
		
		if($article) {
			
			$link = str_replace('amp/', '', JFactory::getURI());
			$images  = json_decode($article->images);
			$attribs  = json_decode($article->attribs);
			
			$img = '';
			$img1 = '';
			$img2 = '';
			$alt = '';
			$alt1 = '';
			$alt2 = '';
			$caption = '';
			$caption1 = '';
			$caption2 = '';
			$width = 0;
			$height = 0;
			
			if(isset($images->image_intro) && $images->image_intro) {
				$img = $img1 = JURI::base().$images->image_intro;
				$imgpath = JPATH_SITE.'/'.$images->image_intro;
				$alt1 = $alt = $images->image_intro_alt;
				$caption = $caption1 = $images->image_intro_caption;
			}
			
			if(isset($images->image_fulltext) && $images->image_fulltext) {
				$img = $img2 = JURI::base().$images->image_fulltext;
				$imgpath = JPATH_SITE.'/'.$images->image_fulltext;
				$alt2 = $alt = $images->image_fulltext_alt;
				$caption = $caption2 = $images->image_fulltext_caption;
			}
			
			if($alt == '') $alt = $alt1 = $alt2 = $article->title;			
			
			// if there is not an intro and full image we must lok into the text
			if($img=='') {
				preg_match_all('/<img[^>]+>/i',$article->introtext, $imagesintro); 
				if($imagesintro) {
					$sw = 0;
					foreach($imagesintro[0] as $image) {
						$sw ++;			
						$doc = new DOMDocument();
						$doc->loadHTML($image);
						$tags = $doc->getElementsByTagName('img');
						foreach ($tags as $tag) {
							if($sw==1) {
							$img = '/'.$tag->getAttribute('src');
							$imgpath = JPATH_SITE.$img ;
							list($width, $height) = getimagesize($imgpath);					   
							}
						}		
					}
				}
			}
			
			// image size and weight
			if($img) list($width, $height) = getimagesize($imgpath);			
			
			$author = $article->created_by_alias ? $article->created_by_alias : $article->author;
			
			$thedate = JHtml::_('date', $article->publish_up, 'DATE_FORMAT_LC3');
			$thedate2 = JHtml::_('date', $article->publish_up, 'Y-m-d') . 'T' .JHtml::_('date', $article->publish_up, 'h-i-s') . 'Z';
			$thedate3 = JHtml::_('date', $article->modified, 'Y-m-d') . 'T' .JHtml::_('date', $article->modified, 'h-i-s') . 'Z';
			
			$tags = new JHelperTags;
			$tags->getItemTags('com_content.article', $article->id);
			
			if($document->getMetaData('description')) $metadescription = $document->getMetaData('description'); 
			else $metadescription = substr(strip_tags($article->introtext),0,250);
			
			$video = '';
			
			//echo 'holaaa2qwqeqwe22'; die();

			echo '<!DOCTYPE html>
	<html amp lang="'.$document->language.'">
		<head>
			<meta charset="utf-8">	
			<meta name="viewport" content="width=device-width,minimum-scale=1,initial-scale=1">
			<title>'.$document->getTitle().'</title>
			<link href="'.str_replace('/amp','',JURI::current()).'" rel="canonical">
			<style amp-boilerplate>body{-webkit-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-moz-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-ms-animation:-amp-start 8s steps(1,end) 0s 1 normal both;animation:-amp-start 8s steps(1,end) 0s 1 normal both}@-webkit-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-moz-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-ms-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-o-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}</style>
			<noscript><style amp-boilerplate>body{-webkit-animation:none;-moz-animation:none;-ms-animation:none;animation:none}</style></noscript>';

			echo '
			<style amp-custom>';
			require_once JPATH_SITE.'/plugins/system/aixeenaseoamp/files/amp/style.css';
			echo '
			</style>';
			
			echo '
			<script type="application/ld+json">
	{	
        "@context": "http://schema.org",
        "@type": "NewsArticle",
        "mainEntityOfPage": "http://cdn.ampproject.org/article-metadata.html",
        "headline": "'.$article->title.'",
        "datePublished": "'.$thedate2.'",
        "dateModified": "'.$thedate3.'",
        "description": "'.$metadescription.'",
        "author": {
          "@type": "Person",
          "name": "'.$author.'"
        },
        "publisher": {
          "@type": "'.$params->get('amp_organization','Organization').'",
          "name": "'.$params->get('amp_name','').'",
          "logo": {
            "@type": "ImageObject",
            "url": "'.$params->get('amp_logo','').'",
            "width": 500,
            "height": 78
          }
        },
        "image": {
          "@type": "ImageObject",
          "url": "'.$img.'",
            "width": '.$width.',
            "height": '.$height.'
        }
      }
 
    </script>';
	
	require_once JPATH_SITE.'/plugins/system/aixeenaseoamp/files/amp/fonts.php';

	if($params->get('amp_analytics',1)) echo '
			<script async custom-element="amp-analytics" src="https://cdn.ampproject.org/v0/amp-analytics-0.1.js"></script>';
		
	echo '
			<script async src="https://cdn.ampproject.org/v0.js"></script>';
			
	if($video) echo  '<script async custom-element="amp-youtube" src="https://cdn.ampproject.org/v0/amp-youtube-0.1.js"></script>';
			
	echo '		
		</head>
		<body>';
	
	if($params->get('amp_analytics',1) && $params->get('amp_analytics_code','')) echo ' 
		
		<amp-analytics type="googleanalytics" id="analytics1">
		<script type="application/json">
		{ "vars": {
			"account": "'.$params->get('amp_analytics_code','').'"
		  },
		  "triggers": {
			"trackPageview": {
			  "on": "visible",
			  "request": "pageview"
			}
		  }
		}
		</script>
		</amp-analytics>';
		
	echo '
				
		<div class="contenedor">
			<main class="principal">
				<header class="cabecera" id="cabecera">
					<div itemtype="http://data-vocabulary.org/Breadcrumb" itemscope="" class="logo">
						<a href="'.$params->get('amp_logo_link','').'" target="_blank" itemprop="url">
							<span class="logo"><amp-img src="'.JURI::base().$params->get('amp_logo','').'" width="500" height="78" layout="fixed"></amp-img> 
							</span>
						</a> 
					</div>
					<div class="seccion-migas">
						<span itemtype="http://data-vocabulary.org/Breadcrumb" itemscope="" class="miga miga_seccion"> 
							<a href="'.JRoute::_(ContentHelperRoute::getCategoryRoute($article->catid)) .'" itemprop="url"  target="_blank"  ><span itemprop="title">'.$article->category_title.'</span></a>
						</span>
					</div>
				</header>
				
				<article itemscope="" itemtype="'.$params->get('amp_schema','http://schema.org/NewsArticle').'">
					
					<header class="articulo-titulares">
						<h1 itemprop="headline">'.$article->title.'</h1>
					</header>
					
					<aside class="compartir compartir--fijo">
						<div class="botones_compartir">
						<a class="boton_whatsapp" target="_blank"   href="whatsapp://send?text='.urlencode($article->title. ' '.$link).'">
						<span class="icono"><svg xmlns="http://www.w3.org/2000/svg" width="16mm" height="16mm" viewBox="0 0 56.692914 56.692913"><g fill="#fff"><path d="M28.433 50.076c-4.522 0-8.723-1.355-12.246-3.693L7.65 49.127l2.778-8.266c-2.676-3.657-4.234-8.163-4.234-13.024 0-12.246 9.96-22.223 22.223-22.223 12.263 0 22.24 9.96 22.24 22.223 0 12.263-9.977 22.24-22.224 22.24zm0-48.663c-14.617 0-26.44 11.822-26.44 26.423 0 4.997 1.39 9.672 3.794 13.652L1.01 55.682l14.652-4.692c3.777 2.1 8.13 3.286 12.77 3.286 14.602 0 26.425-11.84 26.425-26.457 0-14.585-11.806-26.424-26.424-26.424v.017z" fill-rule="evenodd"/><path d="M22.234 16.91c-.44-1.032-.762-1.066-1.423-1.1-.22-.017-.473-.034-.744-.034-.847 0-1.728.254-2.27.813-.643.643-2.27 2.2-2.27 5.402 0 3.185 2.32 6.267 2.643 6.69.32.44 4.538 7.08 11.077 9.79 5.098 2.1 6.622 1.915 7.79 1.678 1.695-.373 3.83-1.626 4.37-3.134.543-1.524.543-2.81.39-3.1-.152-.253-.61-.423-1.236-.745-.643-.337-3.844-1.896-4.454-2.1-.592-.22-1.117-.15-1.592.475-.643.864-1.236 1.78-1.744 2.304-.39.422-1.017.473-1.576.236-.71-.305-2.727-1-5.183-3.2-1.93-1.73-3.234-3.846-3.606-4.49-.39-.643-.034-1.033.254-1.39.338-.405.643-.693.965-1.066.322-.372.49-.576.71-1.016.222-.424.07-.88-.083-1.203-.17-.32-1.457-3.505-2-4.81h-.016z"/></g></svg></span><span class="boton-nombre">WhatsApp</span></a>
						<a class="boton_facebook"  target="_blank"  href="https://www.facebook.com/share.php?u='.urlencode($link).'">
						<span class="icono"><svg xmlns="http://www.w3.org/2000/svg" width="16mm" height="16mm" viewBox="0 0 56.692914 56.692913"><path d="M25.497 7.343c1.812-1.71 4.384-2.295 6.81-2.368 2.573-.03 5.145-.015 7.703-.015.015 2.704.015 5.423 0 8.14h-4.955c-1.067-.072-2.148.732-2.338 1.77-.03 1.812 0 3.624 0 5.437h7.308c-.19 2.63-.527 5.232-.892 7.834-2.163.03-4.297 0-6.446.015-.03 7.732 0 15.464-.03 23.195-3.2.015-6.386 0-9.587.015-.058-7.732.015-15.478-.03-23.21-1.563-.014-3.127.015-4.676-.014v-7.79c1.564-.03 3.113 0 4.677-.014.045-2.53-.043-5.057.045-7.586.16-2.002.92-4.02 2.412-5.407z" fill="#fff"/></svg></span><span class="boton-nombre">Facebook</span></a>
						<a class="boton_twitter"  target="_blank"  href="http://twitter.com/share?url='.urlencode($link).'&text='.urlencode($article->title).'&via='.$params->get('cardsite','@aixeena').'">
						<span class="icono"><svg xmlns="http://www.w3.org/2000/svg" width="16mm" height="16mm" viewBox="0 0 56.692914 56.692913"><path d="M54.13 12.957c-1.872.836-3.903 1.407-6.015 1.66 2.165-1.302 3.825-3.36 4.608-5.804-2.018 1.195-4.276 2.072-6.666 2.55-1.913-2.045-4.635-3.32-7.663-3.32-5.79 0-10.478 4.7-10.478 10.49 0 .825.08-1.83.266-1.074-8.725-.44-16.454-1.144-21.633-7.505-.904 1.554-1.422 3.36-1.422 5.273 0 3.65 1.86 6.865 4.66 8.738-1.712-.053-3.332-.53-4.753-1.315v.134c0 5.073 3.625 9.322 8.42 10.292-.877.24-1.807.372-2.763.372-.677 0-1.34-.08-1.98-.2 1.343 4.17 5.22 7.21 9.802 7.29-3.586 2.816-8.114 4.49-13.028 4.49-.85 0-1.686-.04-2.51-.146 4.648 2.974 10.16 4.714 16.096 4.714 19.296 0 29.853-15.99 29.853-29.853 0-.452-.013-.903-.04-1.355 2.06-1.474 3.838-3.32 5.246-5.43z" fill="#fff"/></svg></span><span class="boton-nombre">Twitter</span></a>
						</div>
					</aside> 
					
					<div class="articulo-apertura">
					';
					
					echo $video;
					
					if($img) echo '
	
					<figure class="foto" itemprop="image" itemscope="" itemtype="http://schema.org/ImageObject">
					<amp-img src="'.$img.'" width="'.$width.'" height="'.$height.'" layout="responsive"></amp-img>
					<meta itemprop="url"    content="'.$img.'">
					<meta itemprop="width"  content="'.$width.'">
					<meta itemprop="height" content="'.$height.'">
					<figcaption class="foto-pie" itemprop="caption">'.$caption.'</figcaption>
					</figure>';
					
					echo'
					<div class="firma ">
						<div class="autor" itemprop="author" itemscope="" itemtype="http://schema.org/Person">
							<div class="autor-texto"><span class="autor-nombre" itemprop="name">'.$author.'</span>
							</div>
						</div> 
					</div>
			</div> 
		
		<div id="articulo-introduccion" class="articulo-introduccion">'.AixeenaAMP::clean_htmlPage($article->introtext,1, $params).'</div>
		<div class="articulo-cuerpo" id="articulo-cuerpo" itemprop="articleBody">'.AixeenaAMP::clean_htmlPage($article->fulltext,2, $params).'</div>	
	';
		
		
			if($tags->itemTags && count($tags->itemTags) > 0) {
		
				echo '
		<section class="articulo-tags">
		<header class="articulo-tags-encabezado">
		<h3 class="articulo-tags-titulo">'.$params->get('amp_tags_title','Tags:').'</h3>
		</header>
		<div class="articulo-tags__interior">
		<ul class="listado">';
				foreach($tags->itemTags as $tag) {
					$linktag = JRoute::_(TagsHelperRoute::getTagRoute($tag->id . ':' . $tag->alias));
				 	echo '<li itemprop="keywords"><a href="'.$linktag.'" target="_blank">'.$tag->title.'</a></li>'; }
				echo '
		</ul>
	</div>
	</section>';

			}
			
			echo  '<nav class="menu-footer">	
				<div class="custom logos-pie">
					<a href="'.$params->get('amp_logo_link','').'" target="_blank" itemprop="url">
						<amp-img src="'.JURI::base().$params->get('amp_pielogo','').'" width="100" height="75"></amp-img>	
					</a>
				</div>
				<div class="legal">
					<a href="'.$params->get('amp_legal_link','').'" target="_blank" itemprop="url">'.$params->get('amp_legal_text','').'</span>
				</div>
			</nav>';

		echo '
	</main>
</div> 
</body>
</html>';
	
		}
	
		die();
		return;
	}
	
	
	
	public static function clean_htmlPage($txt, $type, $params) {
		
			$remove =  explode(',', $params->get('remove', null));	
			if (!empty($remove)) {
				foreach ($remove  as $r) {
					$txt = str_replace($r,'',$txt);
				}
			}
	
			$remove2 =  explode(',', $params->get('remove2', null));
			
			if(!empty($remove2)) {
				foreach ($remove2  as $r) {
					$regex = "#{".$r."}(.*?){/".$r."}#is";
					preg_match_all($regex, $txt, $matches);
					foreach ($matches[0] as $key => $match){
						$txt = str_replace($match,'',$txt);
					}
				}
			}
			
			$output = preg_replace('/(<[^>]+) style=".*?"/i', '$1', $txt);
			$output = preg_replace('/(<[^>]*) style=("[^"]+"|\'[^\']+\')([^>]*>)/i', '$1$3', $output);
			$output = str_replace('<p>&nbsp;</p>','',$output);
			
			if($type==1) {
				return strip_tags($output,'<br><br/><p></p>'); 
			} else {
			
				preg_match_all('/<img[^>]+>/i',$output, $images); 
				
				if($images) {
					foreach($images[0] as $image) {			
						$doc = new DOMDocument();
						$doc->loadHTML($image);
						$tags = $doc->getElementsByTagName('img');
						foreach ($tags as $tag) {
							$src = '/'.$tag->getAttribute('src');			
							if(strpos($src,'http')!== false) {
								$output = str_replace($image, '', $output); 
							} else {
								$imgpath = JPATH_SITE.$src ;
								list($width, $height) = getimagesize($imgpath);					   
								$output = str_replace($image, '<amp-img src='.$src.' alt="'.$tag->getAttribute('alt').'" width="'.$width.'" height="'.$height.'"  layout="responsive"></amp-img>', $output); 
							}
						}		
					}
				}
			
				return $output;
			
			}
		
		}
	

	
}
?>