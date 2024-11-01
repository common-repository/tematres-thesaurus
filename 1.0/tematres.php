<?php
/*
Plugin Name: WP-TemaTres
Plugin URI: http://www.r020.com.ar/tematres/worpress/
Description: WP-TemaTres is plug in for exploit vocabulary and thesarus services provided by TemaTres, web aplication for manage controlled vocabularies, thesauri and taxonomies
Author: diego ferreyra
Author URI: http://www.r020.com.ar/tematres/
Version: 0.3

 *      tematres.php
 *      
 *      Copyright 2009 diego ferreyra<tematres@r020.com.ar>
 *      
 *      This program is free software; you can redistribute it and/or modify
 *      it under the terms of the GNU General Public License as published by
 *      the Free Software Foundation; either version 2 of the License, or
 *      (at your option) any later version.
 *      
 *      This program is distributed in the hope that it will be useful,
 *      but WITHOUT ANY WARRANTY; without even the implied warranty of
 *      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *      GNU General Public License for more details.
 *      
 *      You should have received a copy of the GNU General Public License
 *      along with this program; if not, write to the Free Software
 *      Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 *      MA 02110-1301, USA.
 */


include_once('vocabularyservices.php');


//define default path
if ( ! defined( 'WP_TEMATRES_PLUGIN_DIR' ) )
	define( 'WP_TEMATRES_PLUGIN_DIR', WP_PLUGIN_DIR . '/' . plugin_basename( dirname( __FILE__ ) ) );

//define default lang
define( 'WP_TEMATRES_LANG_DEFAULT','es');	





// function ho call another function and present data
function wp_tematres_content($content)
{

GLOBAL $message;

$_URL_BASE=wp_tematres_get_url_base();

	/* Run the input check. */		
	if(false === strpos($content, '<!-- tematres -->')) 
		{
			return $content;
		}
		

	/* Get data about service */
	$arrayVocabulary=wp_tematres_get_service();

	$lang_path=(file_exists(WP_TEMATRES_PLUGIN_DIR.'/lang/'.$arrayVocabulary["result"]["lang"].'.php')) ? WP_TEMATRES_PLUGIN_DIR.'/lang/'.$arrayVocabulary["result"]["lang"].'.php' : WP_TEMATRES_PLUGIN_DIR.'/lang/'.WP_TEMATRES_LANG_DEFAULT.'.php';

	//Include lang file
	require_once($lang_path) ;
		
	/* Run the URI check. */
	switch ($arrayVocabulary["resume"]["status"]) 
		{
			case 'disable':
			return '<p style="color:#FF0000;">'.$message["service_disable"].' '.$arrayVocabulary["tematres_uri"].'</p>';
			break;

			case 'available':
			
			break;
			
			default:
			return '<p style="color:#FF0000;">'.$message["tematres_uri_error"].' '.$arrayVocabulary["tematres_uri"].'</p>';
		}

	$task=($_GET[task]) ? $_GET[task] : $_POST[task];

	$arg=($_GET[arg]) ? $_GET[arg] : $_POST[arg];
	
	$rows='<h2 id="t3"><a id="'.FixEncoding($arrayVocabulary["result"]["title"]).'" href="'.get_permalink().'" title="'.FixEncoding($arrayVocabulary["result"]["title"]).'">'.FixEncoding($arrayVocabulary["result"]["title"]).'</a></h2>';

	$rows.=wp_tematres_search_form();

/*	Alternative method
if(get_option('wp_dir_permalinks')=='1')	 
/*

*/

if(strpos($_URL_BASE,'?')>0)
	{
		$rows.=str_replace('#TEMATRES_URL_BASE#', $_URL_BASE.'&',wp_tematres_get_data($arrayVocabulary["tematres_uri"],$task,$arg));
	}
	else
	{	
		$rows.=str_replace('#TEMATRES_URL_BASE#', $_URL_BASE.'?',wp_tematres_get_data($arrayVocabulary["tematres_uri"],$task,$arg));
	}
		
	$rows.='<hr><p  style="font-size: 8pt;" align="right">Proudly powered by <a href="http://www.r020.com.ar/tematres/index.en.html" title="Proudly powered by '.$arrayVocabulary["resume"]["version"].'">'.$arrayVocabulary["resume"]["version"].'</a></p>';

	return str_replace('<!-- tematres -->',$rows, $content);
}


/*
 * 
 * HTML presentation search form
 * 
*/
function wp_tematres_search_form($string="")
{
	GLOBAL $message;
	$rows='<div class="temaTresSearch"><fieldset style="background-color: #e8f4ff;  padding: 20px;"> <legend> '.ucfirst($message['search_form']).' </legend>';
	$rows.='<form id="TemaTresSearchForm" name="TemaTresSearchForm"	  method="POST"	  action="'.get_permalink().'#t3"	/>';
	$rows.='<input 	type="hidden" 	id="task"	name="task"	value="search"		/>	';
	$rows.='<input 	type="text" 	id="arg"	name="arg"	class="keyword" 	value="'.$string.'"		/>';
	$rows.='<input type="submit" name="searchButton"  value="'.ucfirst($message['search']).'" alt="'.ucfirst($message['search']).'" />';
	$rows.='</form>  </fieldset></div>';

	return $rows;
}



/*
 * 
 * Retrieve data from TemaTres web service
 * 
 * 
*/
function wp_tematres_get_data($tematres_uri,$task,$arg)
{

switch ($task) 
	{
		//datos de un término == term dada
		case 'fetchTerm':
		
		$arrayTerm=xmlVocabulary2array($tematres_uri,$task,urlencode($arg));
		
		$array=arrayTerm2html($tematres_uri,$arrayTerm);
    	
		//Term
		$rows.=$array[1]["htmltermdata"];

		//Notes
		$rows.=$array[1]["N"];

		//Broader terms
		$rows.=$array[1]["BT"];    
		
		//Non-prefered terms
		$rows.=$array[1]["UF"];
		
		
		//Narrower terms
		$rows.=$array[1]["NT"];
		
		//Related terms
		$rows.=$array[1]["RT"];
		
		break;
	
		//búsqueda  == search
		case 'search':
		$arrayTerm=xmlVocabulary2array($tematres_uri,$task,urlencode(utf8_decode($arg)));
		$rows=arrayVocabulary2htmlSearch($tematres_uri,$arrayTerm);	
		break;
		
		
		default :	
		$arrayTerm=xmlVocabulary2array($tematres_uri,"fetchTopTerms","");
	    $rows=arrayVocabulary2htmlCustom($arrayTerm,"ul");
		break;
	};

return $rows;
}


/*
 * 
 * Check and retrieve URI base service and data about vocabulary
 * 
*/
function wp_tematres_get_service()
{
	$tematres_uri=get_post_meta(get_the_ID(), "tematres_uri", 1);
	
	$arrayVocabulary=xmlVocabulary2array($tematres_uri,"fetchVocabularyData","");
	
	$arrayVocabulary["tematres_uri"] = $tematres_uri;
	
	return $arrayVocabulary;
}


function wp_tematres_get_url_base() 
{
/*
		$base = $_SERVER['REQUEST_URI'];
		$base = $_SERVER['REQUEST_URI'];
		$base = rtrim($base, "/");
		$base = $base."/";
*/
		return get_permalink();
}

add_filter('the_content', 'wp_tematres_content', 3);


?>
