<?php
/*
Plugin Name: WP-TemaTres
Plugin URI: https://wordpress.org/plugins/tematres-thesaurus/
Description: WP-TemaTres is plug in for exploit vocabulary and thesarus services provided by TemaTres, web aplication for manage controlled vocabularies, thesauri and taxonomies
Author: diego ferreyra
Author URI: http://www.vocabularyserver.com/
Version: 1.0

 *      tematres.php
 *      
 *      Copyright 2018 diego ferreyra<tematres@r020.com.ar>
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
if (!defined( 'ABSPATH' )) {   die; };
require_once('vocabularyservices.php');

/*Defines*/
define( 'WP_TEMATRES_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
//define default lang
define( 'WP_TEMATRES_LANG_DEFAULT','es');	

// Define symbols to type of relations
$T3_CFG["REL_SYMBOLS"]=array("BT"=>'↑',"NT"=>'↓',"UF"=>'↸',"RT"=>'⇆');
//Local label for notes
$T3_CFG["LOCAL_NOTES"]= array("DEF" => "Nota de definición" );

add_action('wp_head', 't3_js' );
add_action("wp_enqueue_scripts", "addJS");
add_action('the_content', 'wp_tematres_content' );
add_action( 'the_post', 'fetchTematresSource' , 10);




/* Retorna los datos, acorde al formato de autocompleter */
function getData4Autocompleter($URL_BASE,$searchq,$callback=""){

	add_action( 'the_post', 'fetchTematresSource' , 10);


  $data=getURLdata($URL_BASE.'?task=suggestDetails&arg='.$searchq);       
  $arrayResponse=array("query"=>$searchq,
                       "suggestions"=>array(),
                       "data"=>array());
  if($data->resume->cant_result > 0)  {   
      foreach ($data->result->term as $value) {
          array_push($arrayResponse["suggestions"], (string) $value->string);
          array_push($arrayResponse["data"], (int) $value->term_id);
      }
  }  

$output = json_encode($arrayResponse);
if (strlen($callback)>0) {
    $output = $callback . "($output);";
    }

return $output;
};



function t3_js() {
	$rows='<script type="text/javascript">';
	//rows.=' //<![CDATA[ ';
	$rows.=' var t3_suggest_url = "' . plugins_url( '/proxy.php',  __FILE__  ).'"';
	//rows.='//]]>';
	$rows.='</script>';

echo $rows;
}


function addJS(){    
	    wp_register_script('tematres-autocomplete-script', plugins_url( '/js/jquery.autocomplete.js', __FILE__), array('jquery'), '1.2.7', true );
	    wp_register_script('tematres-script', plugins_url( '/js/t3scripts.js', __FILE__), array('jquery','tematres-autocomplete-script'), '1.2.7', true );

		wp_deregister_script('jquery');
		wp_enqueue_script('jquery', plugins_url( '/js/jquery-3.3.1.min.js', __FILE__), array(), '3.3.1', true);

//	    wp_enqueue_script( 'jquery' );
	    wp_enqueue_script('tematres-autocomplete-script');
	    wp_enqueue_script('tematres-script');
	    wp_enqueue_style( 'tematres-autocomplete-style', plugins_url( '/css/jquery.autocomplete.css', __FILE__) ) ;
	    wp_enqueue_style( 'tematres-style', plugins_url( '/css/style.css', __FILE__) ) ;
}
 

// function ho call another function and present data
function wp_tematres_content($content){

	GLOBAL $T3_messages;
	GLOBAL $T3_CFG;

	/* Run the input check. */		
	if(false === strpos($content, '<!-- tematres -->')) return $content;

	$post_url=get_permalink();
		
	/* Get data about service */
	$vocabularyMetadata=wp_tematres_get_service(get_the_ID());

	$chekService=checkTemaTresService($vocabularyMetadata);

	if($chekService["flag"]==0) return $chekService["T3_messages"];

	$lang_path=(file_exists(WP_TEMATRES_PLUGIN_DIR.'/lang/'.$vocabularyMetadata["lang"].'.php')) ? WP_TEMATRES_PLUGIN_DIR.'/lang/'.$vocabularyMetadata["lang"].'.php' : WP_TEMATRES_PLUGIN_DIR.'/lang/'.WP_TEMATRES_LANG_DEFAULT.'.php';

	//Include lang file
	require_once($lang_path) ;


	if((isset($_GET["task"])) && (in_array($_GET["task"],array('fetchTerm','fetchLast','search','letter')))){

		$task=$_GET["task"];

		$arg=(isset($_GET["arg"])) ? sanitize_text_field($_GET["arg"]) : '';
		
	}elseif((isset($_POST["task"])) && (in_array($_POST["task"],array('search')))){

		$task=($_POST["task"]);
		$arg=(isset($_POST["arg"])) ? sanitize_text_field($_POST["arg"]) : '';
	}else{
		$task='';
		$arg='';

	}
		



	$rows='<h2 id="t3"><a id="'.FixEncoding($vocabularyMetadata["title"]).'" href="'.get_permalink().'" title="'.FixEncoding($vocabularyMetadata["title"]).'">'.FixEncoding($vocabularyMetadata["title"]).'</a></h2>';

	$rows.=wp_tematres_search_form($vocabularyMetadata["tematres_uri"]);


	if(strpos($post_url,'?')>0)	{
		$rows.=str_replace('#TEMATRES_SERVICES_URI#', $post_url.'&',wp_tematres_get_data($vocabularyMetadata["tematres_uri"],array("vocabularyMetadata"=>$vocabularyMetadata,"task"=>$task,"arg"=>$arg)));
		}	else	{	
		$rows.=str_replace('#TEMATRES_SERVICES_URI#', $post_url.'?',wp_tematres_get_data($vocabularyMetadata["tematres_uri"],array("vocabularyMetadata"=>$vocabularyMetadata,"task"=>$task,"arg"=>$arg)));
		}
			
		
		$rows.='<hr><p  style="font-size: 8pt;" align="right">Powered by <a href="http://vocabularyserver.com" title="Powered by '.$vocabularyMetadata["version"].'">'.$vocabularyMetadata["version"].'</a></p>';

	return str_replace('<!-- tematres -->',$rows, $content);
	
}




function checkTemaTresService($vocabularyMetadata){

	if((!is_array($vocabularyMetadata)) || (!$vocabularyMetadata["status"])) return array("flag"=>0,"T3_messages"=>'<p style="color:#FF0000;">'.$T3_messages["service_error"].'</p>');

	/* Run the URI check. */
	switch ($vocabularyMetadata["status"]){
			case 'disable':
			return array("flag"=>0,"T3_messages"=>'<p style="color:#FF0000;">'.$T3_messages["service_disable"].' '.$vocabularyMetadata["tematres_uri"].'</p>');
			break;

			case 'available':
			return array("flag"=>1,"T3_messages"=>'<p style="color:#FF0000;">'.$vocabularyMetadata["status"].'</p>');
			break;
			
			default:
			return array("flag"=>0,"T3_messages"=>'<p style="color:#FF0000;">'.$T3_messages["tematres_uri_error"].' '.$vocabularyMetadata["tematres_uri"].'</p>');		
		}
}

/*
 * 
 * HTML presentation search form
 * 
*/
function wp_tematres_search_form($tematres_uri)
{
	GLOBAL $T3_messages;

	$rows='<div class="temaTresSearch"><fieldset style="background-color: #e8f4ff;  padding: 20px;"> <legend> '.ucfirst($T3_messages['search_form']).' </legend>';
	$rows.='<form id="t3_searchform" name="TemaTresSearchForm"	  method="POST"	  action="'.get_permalink().'#t3"	/>';
	$rows.='<input 	type="hidden" 	id="task"	name="task"	value="search"		/>	';
	$rows.='<input 	type="hidden" 	id="tematres_uri"	name="tematres_uri"	value="'.$tematres_uri.'"/>	';
	$rows.='<input 	type="text" 	id="t3_search_input"	name="arg"	class="keyword" autocomplete="off"	placeholder="'.ucfirst($T3_messages['search']).'" value=""/>';

	$rows.='<input type="submit" name="searchButton"  value="'.ucfirst($T3_messages['search']).'" alt="'.ucfirst($T3_messages['search']).'" />';
	$rows.='</form>  </fieldset></div>';

	return $rows;
}



/*
 * Retrieve data from TemaTres web service
*/
function wp_tematres_get_data($tematres_uri,$param=array()){

$vocabularyMetadata=$param["vocabularyMetadata"];
$task=$param["task"];
$arg=$param["arg"];

switch ($task){
		//datos de un término == term dada
		case 'fetchTerm':
		$tema_id = is_numeric($param['arg']) ? intval($param['arg']) : 0;
        $dataTerm=getURLdata($tematres_uri.'?task=fetchTerm&arg='.$tema_id);
        $htmlTerm=data2htmlTerm($dataTerm,array('vocabularyMetadata' => $vocabularyMetadata));
        $term= (string) FixEncoding($dataTerm->result->term->string);
        $term_id= (int) $dataTerm->result->term->term_id;
        $task='fetchTerm';
		$rows=HTMLtermDetaills($htmlTerm,$dataTerm,$vocabularyMetadata);
		break;
	
		//búsqueda  == search
		case 'search':
        //sanitiar variables
        $string = isset($param['arg']) ? sanitize_text_field($param['arg']) : null;
        if (strlen($string) > 0) {
            $dataTerm = getURLdata($tematres_uri.'?task=search&arg='.urlencode($string));
            $htmlSearchTerms = data2html4Search($dataTerm,/*ucfirst($T3_messages["searchExpresion"]).' : <i>'.*/$string/*.'</i>'*/,$param);
            $task = 'search';
        }

		$rows=$htmlSearchTerms;
		break;
		
		
		default :	
		$data=getURLdata($tematres_uri.'?task=fetchTopTerms');
		$rows='<ul class="treeTerm">';
		if($data->resume->cant_result > 0) {	
			foreach ($data->result->term as $value) {
				$rows.='<li><a href="index.php?task=fetchTerm&arg='.(int) $value->term_id.'" title="'.(string) $value->string.'">'.(string)$value->string.'</a></li>';
			}
		}
		$rows.='</ul>';
		break;
	};

return $rows;
}


/*
 * 
 * Check and retrieve URI base service and data about vocabulary
 * 
*/
function wp_tematres_get_service($post_id){
	
	$tematres_uri=fetchTematresSource($post_id);

	$vocabularyMetadata=fetchVocabularyMetadata($tematres_uri);

	return $vocabularyMetadata;
}

//retieve TemaTres services provider URL
function fetchTematresSource($post_id){	
  	return get_post_meta($post_id, "tematres_uri", 1);
}


function HTMLtermDetaills($htmlTerm,$dataTerm,$vocabularyMetadata){

    $term= (string) FixEncoding($dataTerm->result->term->string);
    $term_id= (int) $dataTerm->result->term->term_id;    

    $rows= '<div class="tabbable">
            <div id="term" about="'.$dataTerm->result->term->term_id.'" typeof="skos:Concept">';

    if (isset($htmlTerm["results"]["breadcrumb"]))
      $rows.=            $htmlTerm["results"]["breadcrumb"];
    
    if (isset($htmlTerm["results"]["BT"])) {
      $rows.=$htmlTerm["results"]["BT"];
    }
    
    if (isset($htmlTerm["results"]["termdata"])) {
      $rows.='<div id="termdata"><h2>'.$htmlTerm["results"]["termdata"].'</h2></div>';
    }

    if (isset($htmlTerm["results"]["searchResources"])) {
      $rows.='<p class="t3_search_resources">'.$htmlTerm["results"]["searchResources"].'</p>';
    }

    $rows.= $htmlTerm["results"]["UF"];

    if (strlen($htmlTerm["results"]["NOTES"]) > 0) {            
          $rows.= '      <div class="relation panel">
                            <div id="notas" class="relation-body">
                                '.$htmlTerm["results"]["NOTES"].'
                            </div>
                        </div>';
    }
    
    $rows.=$htmlTerm["results"]["NT"];

    if (isset($htmlTerm["results"]["RT"])) {
      $rows.= $htmlTerm["results"]["RT"];
    }
    
    $rows.=$htmlTerm["results"]["MAP"];
    $rows.=$htmlTerm["results"]["LINKED"];


    $rows.=  '	</div><!-- #term -->';
    $rows.=  '</div> <! --#tabbable -->';
 
    return $rows;                            
}


?>
