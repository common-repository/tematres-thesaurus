<?php
/*
 *      vocabularyservices.php
 *      
 *      Copyright 2009 diego ferreyra <tematres@r020.com.ar>
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




/*
Funciones generales de parseo XML / General function for XML parser
*/

function xml2arraySimple($str) {
	$xml = simplexml_load_string($str);
	return simplexml2array($xml);
}


function simplexml2array($xml) {

	if (get_class($xml) == 'SimpleXMLElement') {
		$attributes = $xml->attributes();
		foreach($attributes as $k=>$v) {
			if ($v) $a[$k] = (string) $v;
		}
		$x = $xml;
		$xml = get_object_vars($xml);
	}
	if (is_array($xml)) {
		if (count($xml) == 0) return (string) $x; // for CDATA
		foreach($xml as $key=>$value) {
			$r[$key] = simplexml2array($value);
		}
		if (isset($a)) $r['@'] = $a;// Attributes
		return $r;
	}
	return (string) $xml;
}




/*
Funciones de consulta de datos
*/


/*
Hacer una consulta y devolver un array
* $uri = url de servicios tematres
* +    & task = consulta a realizar
* +    & arg = argumentos de la consulta
*/
function xmlVocabulary2array($tematres_uri,$task,$arg){
	
	$url=$tematres_uri.'?task='.$task.'&arg='.$arg;
	
	$xml=file_get_contents($url) or die ("Could not open a feed called: " . $url);
	
	return xml2arraySimple($xml);
	}




/*
Recibe un array y lo publica como HTML
*/
function arrayVocabulary2html($array,$div_title,$tag_type){

	if($array["resume"]["cant_result"]>"0")	{

	$rows.='<h3>'.$div_title.'</h3><'.$tag_type.'>';
	
	foreach ($array["result"] as $key => $value){
				while (list( $k, $v ) = each( $value )){
					$i=++$i;
					//Controlar que no sea un resultado unico
					if(is_array($v)){
						$rows.='<li>';
						$rows.='<a href="#TEMATRES_URL_BASE#task=fetchTerm&amp;arg='.$v[term_id].'#t3">'.FixEncoding($v[string]).'</a>';
						$rows.='</li>';
			
						} else {

							//controlar que sea la ultima
							if(count($value)==$i){
								$rows.='<li>';
								$rows.='<a href="#TEMATRES_URL_BASE#task=fetchTerm&amp;arg='.$value[term_id].'#t3">'.FixEncoding($value[string]).'</a>';
								$rows.='</li>';
								}
						}
					}

		}		
	$rows.='</'.$tag_type.'>';
	}

return $rows;
	}


/*
Recibe notas array y lo publica como HTML
*/
function arrayVocabulary2htmlNotes($array){

GLOBAL $message;

	if($array["resume"]["cant_result"]>"0")	{

	$rows.='<div class="notes">';

	foreach ($array["result"] as $key => $value){
				while (list( $k, $v ) = each( $value )){
					$i=++$i;
					//Controlar que no sea un resultado unico
					if(is_array($v)){
						$rows.='<h4 class="notes">'.ucfirst($message[$v[note_type]]).'</h4>';
						$rows.='<p>'.FixEncoding($v[note_text]).'</p>';
			
						} else {

							//controlar que sea la ultima
							if(count($value)==$i){
								
								$rows.='<h4 class="notes">'.ucfirst($message[$value[note_type]]).'</h4>';
								$rows.='<p>'.FixEncoding($value[note_text]).'</p>';
							}
						}
					}

		}		
	$rows.='</div>';
	}

return $rows;
	}




/*
Recibe un array y lo publica como HTML
*/
function arrayVocabulary2htmlSearch($tematres_uri,$array,$tag_type="ul"){

	GLOBAL $message	;

	$rows.='<h3>'.ucfirst($message["searchExpresion"]).' : <i>'.$array["resume"]["param"]["arg"].'</i>'.' ('.$array["resume"]["cant_result"].')</h3>';
	
	if($array["resume"]["cant_result"]>"0")	{
		
	$rows.='<'.$tag_type.'>';
	
	foreach ($array["result"] as $key => $value){
				while (list( $k, $v ) = each( $value )){
					$i=++$i;
					//Controlar que no sea un resultado unico
					if(is_array($v)){
						$rows.='<li>';
						$rows.= ($v[no_term_string]) ? '<em title="'.$message['UF'].'  '.$message['USE'].' '.FixEncoding($v[string]).'">'.FixEncoding($v[no_term_string]).'</em> '.$message['USE'].' ' : '';
						$rows.='<a href="#TEMATRES_URL_BASE#task=fetchTerm&amp;arg='.$v[term_id].'#t3" title="'.FixEncoding($v[string]).'">'.FixEncoding($v[string]).'</a>';
						$rows.='</li>';
			
						} else {

							//controlar que sea la ultima
							if(count($value)==$i){
								$rows.='<li>';
								$rows.= ($value[no_term_string]) ? '<em title="'.$message['UF'].' '.$message['USE'].' '.FixEncoding($value[string]).'">'.FixEncoding($value[no_term_string]).'</em> '.$message['USE'].' ' : '';
								$rows.='<a href="#TEMATRES_URL_BASE#task=fetchTerm&amp;arg='.$value[term_id].'#t3" title="'.FixEncoding($value[string]).'">'.FixEncoding($value[string]).'</a>';
								$rows.='</li>';
								}
						}
					}

		}		
	$rows.='</'.$tag_type.'>';
	}
	else 
	{
	
	$arrayTerm=xmlVocabulary2array($tematres_uri,"fetchSimilar",urlencode($array["resume"]["param"]["arg"]));

	if (count($arrayTerm))
		{
		$rows.='<h4>'.ucfirst($message['suggestedSearchTerm']).' <a href="#TEMATRES_URL_BASE#task=search&amp;arg='.FixEncoding($arrayTerm[result][string]).'#t3" title="'.FixEncoding($arrayTerm[result][string]).'">'.FixEncoding($arrayTerm[result][string]).'</a>?</h4>';			
		}

	}

return $rows;
	}


/*
HTML details for one term
*/
function arrayTerm2html($tematres_uri,$array){

GLOBAL $message;

$date_term = ($array[result][term][date_mod]) ? $array[result][term][date_mod] : $array[result][term][date_create];

$date_term = date_create($date_term);

$arrayRows["termdata"]=$array[result][term];


$arrayRows["htmltermdata"].='<div class="termdata"><h2 class="title"><a title="Permanent Link to '.FixEncoding($array[result][term][string]).'" href="#TEMATRES_URL_BASE#task=fetchTerm&arg='.$array[result][term][tema_id].'#t3" rel="bookmark">'.FixEncoding($array[result][term][string]).'</a></h2>';

//$arrayRows["htmltermdata"].='<p class="PostInfo">'.ucfirst($message["term_date"]).' '.date_format($date_term,"d-m-Y").'</p>';

$arrayRows["htmltermdata"].='</div>';

/*
Notas // notes
*/
$arrayNotes=xmlVocabulary2array($tematres_uri,"fetchNotes&arg",$array[result][term][tema_id]);
$rows.=arrayVocabulary2htmlNotes($arrayNotes);
$arrayRows["N"].=arrayVocabulary2htmlNotes($arrayNotes);

/*
fetch narrow terms
*/
$arrayTE=xmlVocabulary2array($tematres_uri,"fetchDown",$array[result][term][tema_id]);	
if (count($arrayTE)) 
{
	$arrayRows["NT"]=arrayVocabulary2htmlTerms($arrayTE,$message["NT"]);
}


/*
fetch broader terms
*/
$arrayTG=xmlVocabulary2array($tematres_uri,"fetchUp",$array[result][term][tema_id]);

//$rows.=arrayVocabulary2html($arrayTG,"Broader terms","ol");

if (count($arrayTG)) 
{
	$arrayRows["BT"]=arrayVocabulary2htmlBTerms($arrayTG,$message["BT"],$array[result][term][tema_id]);	
}	

/*
fetch related terms
*/
$arrayTR=xmlVocabulary2array($tematres_uri,"fetchRelated",$array[result][term][tema_id]);
//$rows.=arrayVocabulary2html($arrayTR,"Related terms","ul");

if (count($arrayTR)) 
{
	$arrayRows["RT"]=arrayVocabulary2htmlTerms($arrayTR,$message["RT"]);
}

/*
Buscar términos equivalentes // fetch equivalent terms
*/
$arrayUF=xmlVocabulary2array($tematres_uri,"fetchAlt",$array[result][term][tema_id]);
if (count($arrayUF)) 
{
	$arrayRows["UF"]=arrayVocabulary2htmlTerms($arrayUF,$message["UF"],"0");
}



return array($rows,$arrayRows);
}




/*
 * Display related, narrower, alt terms (some display rules)
*/
function arrayVocabulary2htmlTerms($array,$div_title,$show_link="1"){
	

	if($array["resume"]["cant_result"]>"0")	{

  	$rows.='<div class="list_terms">
   		<h3>'.ucfirst($div_title).'</h3>
    		<ul>';
		
	foreach ($array["result"] as $key => $value){
				while (list( $k, $v ) = each( $value )){
					$i=++$i;
					//Controlar que no sea un resultado unico
					if(is_array($v)){
							$rows.='<li>';
								if ($show_link=='1') 
								{
									$rows.='<a href="#TEMATRES_URL_BASE#task=fetchTerm&amp;arg='.$v[term_id].'#t3" title="'.FixEncoding($v[string]).'">'.FixEncoding($v[string]).'</a>';
								}
								else 
								{
									$rows.=FixEncoding($v[string]);	
								}								
							$rows.='</li>';
						} else {

							//controlar que sea la ultima
							if(count($value)==$i){
									$rows.='<li>';
										if ($show_link=='1') 
										{
											$rows.='<a href="#TEMATRES_URL_BASE#task=fetchTerm&amp;arg='.$value[term_id].'#t3" title="'.FixEncoding($value[string]).'">'.FixEncoding($value[string]).'</a>';
										}
										else 
										{
											$rows.=FixEncoding($value[string]);
										}
																			
									$rows.='</li>';
								}
						}
					}

		}		
	$rows.='</ul>';
	$rows.='</div>';
	}

return $rows;
}


function arrayVocabulary2htmlBTerms($array,$div_title,$tema_id="0"){
	

	if($array["resume"]["cant_result"]>"0")	{

  	$rows.='<div class="list_terms">
   		<h3>'.ucfirst($div_title).'</h3>
    		<ol>';
		
	foreach ($array["result"] as $key => $value){
				while (list( $k, $v ) = each( $value )){
					$i=++$i;
					//Controlar que no sea un resultado unico
					if(is_array($v)){
						if($v[term_id]!==$tema_id)
						{

							$rows.='<li>';
							$rows.='<a href="#TEMATRES_URL_BASE#task=fetchTerm&amp;arg='.$v[term_id].'#t3" title="'.FixEncoding($v[string]).'">'.FixEncoding($v[string]).'</a>';
							$rows.='</li>';
						}
			
						} else {

							//controlar que sea la ultima
							if(count($value)==$i){
								//Que sea el mismo tema_id 
								if($value[term_id]==$tema_id) 
									{
										$showBT='0';
									}
									else
									{
										$rows.='<li>';
										$rows.='<a href="#TEMATRES_URL_BASE#task=fetchTerm&amp;arg='.$value[term_id].'#t3" title="'.FixEncoding($value[string]).'">'.FixEncoding($value[string]).'</a>';
										$rows.='</li>';
									}	
							}
						}
					}

		}		
	$rows.='</ol>';
	$rows.='</div>';
	}

if($showBT!=='0') return $rows;
}



function arrayVocabulary2htmlCustom($array,$tag_type,$tema_id="0",$show_link="1"){
	
	GLOBAL $message;

	if($array["resume"]["cant_result"]>"0")	{

  	$rows.='<div class="list_terms">
   		<h3>'.$message["hieraquicalView"].'</h3>
    		<'.$tag_type.'>';
		
	foreach ($array["result"] as $key => $value){
				while (list( $k, $v ) = each( $value )){
					$i=++$i;
					//Controlar que no sea un resultado unico
					if(is_array($v)){
						if($v[term_id]!==$tema_id)
							{
							$rows.='<li>';
								if ($show_link=='1') 
								{
									$rows.='<a href="#TEMATRES_URL_BASE#task=fetchTerm&amp;arg='.$v[term_id].'#t3" title="'.FixEncoding($v[string]).'">'.FixEncoding($v[string]).'</a>';
								}
								else 
								{
									$rows.=FixEncoding($v[string]);	
								}								
							$rows.='</li>';
							}
			
						} else {

							//controlar que sea la ultima
							if(count($value)==$i){
								//Que sea el mismo tema_id 
								if($value[term_id]!==$tema_id)
									{								
									$rows.='<li>';
										if ($show_link=='1') 
										{
											$rows.='<a href="#TEMATRES_URL_BASE#task=fetchTerm&amp;arg='.$value[term_id].'#t3" title="'.FixEncoding($value[string]).'">'.FixEncoding($value[string]).'</a>';
										}
										else 
										{
											$rows.=FixEncoding($value[string]);
										}
																			
									$rows.='</li>';
									}
								}
						}
					}

		}		
	$rows.='</'.$tag_type.'>';
	$rows.='</div>';
	}

return $rows;
}


// string 2 URL legible
// based on source from http://code.google.com/p/pan-fr/
function string2url ( $string )
{
		$string = strtr($string,
		"ÀÁÂÃÄÅàáâãäåÇçÒÓÔÕÖØòóôõöøÈÉÊËèéêëÌÍÎÏìíîïÙÚÛÜùúûü¾ÝÿýÑñ",
		"AAAAAAaaaaaaCcOOOOOOooooooEEEEeeeeIIIIiiiiUUUUuuuuYYyyNn");

		$string = str_replace('Æ','AE',$string);
		$string = str_replace('æ','ae',$string);
		$string = str_replace('¼','OE',$string);
		$string = str_replace('½','oe',$string);

		$string = preg_replace('/[^a-z0-9_\s\'\:\/\[\]-]/','',strtolower($string));
		$string = preg_replace('/[^a-z0-9_\s\:\/\[\]-]/','',strtolower($string));
		
		$string = preg_replace('/[\s\'\:\/\[\]-]+/',' ',trim($string));
		
		return $string;
}

//form http://www.compuglobalhipermega.net/php/php-url-semantica/	
function cambiaAcentos($str)
{
	if(is_utf($str))
	{
		$str = utf8_decode($str);
	}

	$str = htmlentities($str);
	$str = preg_replace('/&([a-zA-Z])(uml|acute|grave|circ|tilde);/','$1',$str);
return html_entity_decode($str);
}

//form http://www.compuglobalhipermega.net/php/php-url-semantica/	
function is_utf ($t)
{
	if ( @preg_match ('/.+/u', $t) )
	return 1;
}

//from http://www.marcusmonteiro.com/geral/php-retirar-acentos-de-strings
function delAcentos ($frase) {
	$pasta = strtolower( ereg_replace("[^a-zA-Z0-9-]", "", strtr(utf8_decode(trim($frase)), utf8_decode("áàãâéêíóôõúüñçÁÀÃÂÉÊÍÓÔÕÚÜÑÇ "),"aaaaeeiooouuncAAAAEEIOOOUUNC-")) );
	return utf8_encode($pasta);
}	




//form http://www.compuglobalhipermega.net/php/php-url-semantica/	
function strtolowerExtended($str)
{     

$low = array(chr(193) => chr(225), //á
   chr(201) => chr(233), //é
	 chr(205) => chr(237), //í­
	 chr(211) => chr(243), //ó
	 chr(218) => chr(250), //ú
	 chr(220) => chr(252), //ü
	 chr(209) => chr(241)  //ñ
	 );

return strtolower(strtr($str,$low));
}

/*
From http://ar2.php.net/utf8_encode
*/
function FixEncoding($x){
  if(mb_detect_encoding($x)=='UTF-8'){
    return $x;
  }else{
    return utf8_encode($x);
  }
} 
?>
