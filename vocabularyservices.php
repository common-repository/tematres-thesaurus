<?php
if (!defined( 'ABSPATH' )) {   die; };
/*
 *      vocabularyservices.php
 *
 *      Copyright 2018 diego ferreyra <tematres@r020.com.ar>
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

/*  Hacer una consulta y devolver un array
*       $uri = url de servicios tematres
*       & task = consulta a realizar
*       & arg = argumentos de la consulta   */
function getURLdata($url)
{
    if (extension_loaded('curl')) {
        $rCURL = curl_init();
        curl_setopt($rCURL, CURLOPT_URL, $url);
        curl_setopt($rCURL, CURLOPT_HEADER, 0);
        curl_setopt($rCURL, CURLOPT_RETURNTRANSFER, 1);
        $xml = curl_exec($rCURL) or die ("Could not open a feed called: " . $url);
        curl_close($rCURL);
    } else {
        $xml=file_get_contents($url) or die ("Could not open a feed called: " . $url);
    }
    $content = new SimpleXMLElement($xml);
    return $content;
}


/*  Funciones de presentación de datos
    Recibe un objeto con las notas y lo publica como HTML  */
function data2html4Notes($data,$param=array()){
    GLOBAL $T3_CFG,$T3_messages;
    if ($data->resume->cant_result > 0) {
     
    foreach ($data->result->term as $value) {
           //exclude private notes    
           if((string) $value->note_type!=='NP') {
               $note_label=(in_array((string) $value->note_type,array("NA","NH","NB","NC","CB","DEF"))) ? str_replace(array("NA","NH","NB","NP","NC","DEF"),array($T3_messages["LABEL_NA"],$T3_messages["LABEL_NH"],$T3_messages["LABEL_NB"],$T3_messages["LABEL_NC"],$T3_CFG["LOCAL_NOTES"]["DEF"]),(string) $value->note_type) : (string) $value->note_type;
               $rows='<div class="t3_notes" id="note_'.$value->note_id.'" rel="skos:scopeNote">';
               $rows.='<h6 class="t3_note_label">'.$note_label.':</h6>';
               $rows.='<p class="t3_body_note">'.(string) $value->note_text.'</p>';
               $rows.='</div>';
           }
        }
    }
   return (@$rows);
};



/*  Recibe un objeto con resultados de búsqueda y lo publica como HTML  */
function data2html4Search($data,$string,$param=array())
{
    GLOBAL $T3_messages;

    $rows='<div id="t3_search_terms">';

    $rows.='<h3 id="msg_search_result">'.ucfirst($T3_messages["MSG_ResultBusca"]).' <i>'.(string) $data->resume->param->arg.'</i>: '.(string) $data->resume->cant_result.'</h3>';

    if ($data->resume->cant_result > 0) {

    $rows.='<ul id="t3_search_result_list">';
        foreach ($data->result->term as $value) {
            $term_id        = (int) $value->term_id;
            $term_string    = (string) $value->string;
            $no_term_string = '';
            $no_term_string = (string) $value->no_term_string;

            $rows.='<li><span id="term_'.$term_id.'" about="#TEMATRES_SERVICES_URI#task=fetchTerm&arg='.$term_id.'" typeof="skos:Concept" >';
            if ($no_term_string != '')
                $rows.=  $no_term_string.' <strong>use</strong> ';
            $rows.='<a resource="'.$term_id.'" property="skos:prefLabel" href="#TEMATRES_SERVICES_URI#?task=fetchTerm&arg='.$term_id.'"  title="'.$term_string.'">'.$term_string.'</a></span></li>';
        }
    $rows.='</ul>';

    } else {

        //No hay resultados, buscar términos similares
        $data=getURLdata($param["vocabularyMetadata"]["tematres_uri"].'?task=fetchSimilar&arg='.urlencode((string) $data->resume->param->arg));
        if($data->resume->cant_result > 0) {
            $rows.='<h4>'.ucfirst(LABEL_TERMINO_SUGERIDO).' <a href="#TEMATRES_SERVICES_URI#?task=search&amp;arg='.(string) $data->result->string.'" title="'.(string) $data->result->string.'">'.(string) $data->result->string.'</a>?</h4>';
        }
    }

    $rows.='</div> <!-- end search div -->';
    return $rows;
}

/*  HTML details for one term  */
function data2htmlTerm($data,$param=array()){

    GLOBAL $T3_CFG,$T3_messages;

    $date_term  = ($data->result->term->date_mod) ? $data->result->term->date_mod : $data->result->term->date_create;
    $date_term  = date_create($date_term);
    $term_id    = (int) $data->result->term->tema_id;
    $term       = (string) $data->result->term->string;
    $class_term = ($data->result->term->isMetaTerm == 1) ? ' class="t3_metaTerm" ' :'';



    $arrayRows=array("termdata"=>'',
                     "NOTES"=>'',
                     "NT"=>'',
                     "UF"=>'',
                     "BT"=>'',
                     "MAP"=>'',
                     "LINKED"=>'',
                     "breadcrumb"=>''
                    );

    /*  fetch broader terms  */
    $dataTG     = getURLdata($param["vocabularyMetadata"]["tematres_uri"].'?task=fetchUp&arg='.$term_id);
    $arrayRows["termdata"] = '<span '.$class_term.' id="term_prefLabel" property="skos:prefLabel" content="'.FixEncoding($term).'">'.FixEncoding($term).'</span>';
    
    $arrayRows["searchResources"]= $T3_messages["searchResources"].' &#171;<i><a href="'.get_bloginfo( 'url', 'display' ) . '/?t3&s=' . urlencode( $term ).'" title ="'.$T3_messages["searchResources"].' '.FixEncoding($term).'">'.FixEncoding($term).'</a>&#187;</i>.';
    /*  Notas  */
    $dataNotes = getURLdata($param["vocabularyMetadata"]["tematres_uri"].'?task=fetchNotes&arg='.$term_id);
    $arrayRows["NOTES"] = data2html4Notes($dataNotes,$param);
    
    /* NT terms */
    $dataTE = getURLdata($param["vocabularyMetadata"]["tematres_uri"].'?task=fetchDown&arg='.$term_id);
    if ($dataTE->resume->cant_result > 0) {
        $arrayRows["NT"]='<div id="narrower_terms"><h4>'.ucfirst($T3_messages["NT"]).' '.$T3_CFG["REL_SYMBOLS"]["NT"].'</h4><ul class="t3_term_list">';

        foreach ($dataTE->result->term as $NTvalues) {
            $arrayRows["NT"].='<li id="narrower'.(int) $NTvalues->term_id.'" about="#TEMATRES_SERVICES_URI#task=fetchTerm&arg='.$NTvalues->term_id.'" typeof="skos:Concept">';
            //$arrayRows["NT"].=($NTvalues->code) ? '<span property="skos:notation">'.$NTvalues->code.'</span>' :'';
            $arrayRows["NT"].=' <a class="t3_term_link" rel="skos:narrower" href="#TEMATRES_SERVICES_URI#task=fetchTerm&arg='.(int) $NTvalues->term_id.'" title="'.$NTvalues->string.'">'.$NTvalues->string.'</a>';
            $arrayRows["NT"].='</li>';
        }
    $arrayRows["NT"].='</ul>';
    }
    

    //Fetch data about associated terms (BT,RT,UF)
    $dataDirectTerms = getURLdata($param["vocabularyMetadata"]["tematres_uri"].'?task=fetchDirectTerms&arg='.$term_id);
    $array2HTMLdirectTerms = data2html4directTerms($dataDirectTerms);
    if ($array2HTMLdirectTerms["UFcant"] > 0) {
        $arrayRows["UF"]='<div id="alt_terms"><h4>'.ucfirst($T3_messages["UF"]).' '.$T3_CFG["REL_SYMBOLS"]["UF"].'</h4><ul class="t3_term_list">';
        $arrayRows["UF"].=$array2HTMLdirectTerms["UF"];
        $arrayRows["UF"].='</ul></div>';
    }
    if ($array2HTMLdirectTerms["RTcant"] > 0) {
        $arrayRows["RT"]='<div id="related_terms"><h4>'.ucfirst($T3_messages["RT"]).' '.$T3_CFG["REL_SYMBOLS"]["RT"].'</h4><ul class="t3_term_list">';
        $arrayRows["RT"].=$array2HTMLdirectTerms["RT"];
        $arrayRows["RT"].='</ul></div>';

    }

    /* SOLO PARA TÉRMINOS con POLIJERQUIA. si no, está el breadcrumb */
    if ($array2HTMLdirectTerms["BTcant"] > 1) {
        $arrayRows["BT"]='<div id="broader_terms"><h4>'.ucfirst($T3_messages["BT"]).' '.$T3_CFG["REL_SYMBOLS"]["BT"].'</h4><ul class="t3_term_list">';
        $arrayRows["BT"].=$array2HTMLdirectTerms["BT"];
        $arrayRows["BT"].='</ul></div>';
    }
    /* Buscar términos mapeados  */
    $dataMapped=getURLdata($param["vocabularyMetadata"]["tematres_uri"].'?task=fetchTargetTerms&arg='.$term_id);
    if ($dataMapped->resume->cant_result > 0) {
        $arrayRows["MAP"]='<div id="map_terms"><h4>'.ucfirst($T3_messages["TargetTerm"]).'</h4><ul class="t3_term_list">';
        $arrayRows["MAP"].=data2html4MappedTerms($dataMapped);
        $arrayRows["MAP"].='</ul></div>';

    }
    /*  Buscar términos linkeados // fetchURI  */
    $dataMappedURI=getURLdata($param["vocabularyMetadata"]["tematres_uri"].'?task=fetchURI&arg='.$term_id);
    if ($dataMappedURI->resume->cant_result >"0")  {
        $arrayRows["LINKED"]='<div id="linked_terms"><h4>'.ucfirst($T3_messages["TargetTerm"]).'</h4><ul class="t3_term_list">';
        $arrayRows["LINKED"].=data2html4MappedURITerms($dataMappedURI);
        $arrayRows["LINKED"].='</ul></div>';
    }


    $arrayRows["breadcrumb"]=data2html4Breadcrumb($dataTG,$term_id);


    return array("task"=>"fetchTerm","results"=>$arrayRows);
}



function data2html4MappedTerms($data,$param=array()){
    
    if ($data->resume->cant_result >"0") {
        foreach ($data->result->term as $value) {
            $rows='<li><span about="#TEMATRES_SERVICES_URI#task=fetchTerm&arg='.(int) $value->term_id.'" typeof="skos:Concept">';
            $rows.=(string) $value->target_vocabulary_label.': <span resource="#TEMATRES_SERVICES_URI#task=fetchTerm&arg='.(int) $value->term_id.'" property="skos:prefLabel" href="#TEMATRES_SERVICES_URI#task=fetchTerm&arg='.(int) $value->term_id.'" title="'.(string) $value->string.'">'.(string) $value->string.'</span>';
            $rows.='</span>';
            $rows.='</li>';
        }
    }
    return (@$rows);
}

/*  HTML details for direct terms  */
function data2html4directTerms($data,$param=array()){

    $i = 0;
    $iRT = 0;
    $iBT = 0;
    $iUF = 0;
    $RT_rows = '';
    $BT_rows = '';
    $UF_rows = '';

    if ($data->resume->cant_result > "0") {
        foreach ($data->result->term as $value) {
            $i=++$i;
            $term_id=(int) $value->term_id;
            $term_string=(string) $value->string;
            switch ((int) $value->relation_type_id) {
                case '2':
                    $iRT=++$iRT;
                    $RT_rows.='<li class="related_terms" id="related_terms'.$term_id.'" about="#TEMATRES_SERVICES_URI#task=fetchTerm&arg='.$term_id.'" typeof="skos:Concept">';
                    //$RT_rows.=($value->code) ? '<span property="skos:notation">'.$value->code.'</span>' :'';
                    $RT_rows.=' <a class="t3_term_link" rel="skos:related" href="#TEMATRES_SERVICES_URI#task=fetchTerm&arg='.$term_id.'" title="'.$term_string.'">'.$term_string.'</a></li>';
                    break;
                case '3':
                    $iBT=++$iBT;
                    if (isset($v["isMetaTerm"]))
                        $class_dd=($v["isMetaTerm"]==1) ? ' class="t3_metaTerm" ' :'';
                    $BT_rows.=' <li class="broader_terms" id="broader_term_'.$term_id.'"  about="#TEMATRES_SERVICES_URI#task=fetchTerm&arg='.$term_id.'" typeof="skos:Concept">';
                    $BT_rows.=' <a class="t3_term_link" rel="skos:broader" href="#TEMATRES_SERVICES_URI#task=fetchTerm&arg='.$term_id.'" title="'.$term_string.'">'.$term_string.'</a></li>';
                    break;
                case '4':
                    if ($value->relation_code !=='H') {
                        $iUF=++$iUF;
                        $UF_rows.=' <li class="alt_terms" id="alt_term_'.$term_id.'" typeof="skos:altLabel" property="skos:altLabel" content="'.$term_string.'" xml:lang="'.(string) $value->lang.'"><i>'.$term_string.'</i></li>';
                    }
                    break;
            }
        }
    }
    return array(   "RT"=>$RT_rows,
                    "BT"=>$BT_rows,
                    "UF"=>$UF_rows,
                    "RTcant"=>$iRT,
                    "BTcant"=>$iBT,
                    "UFcant"=>$iUF);
}

function data2html4Breadcrumb($data,$tema_id="0",$param=array()){

    GLOBAL $T3_messages;

    $tema_id = (int) $tema_id;
    
    if ($data->resume->cant_result > 0){
    
        $rows='<div id="term_breadcrumb">';                    
        $rows.='<span typeof="v:Breadcrumb">';
        $rows.='<a rel="v:url" property="v:title" href="'.get_permalink().'" title="'.ucfirst($T3_messages["MENU_Inicio"]).'">'.ucfirst($T3_messages["MENU_Inicio"]).'</a>';
        $rows.='</span>  ';

        $i=0;

        foreach ($data->result->term as $value){
            $i=++$i;
            if((int) $value->term_id!==$tema_id){
                $rows.='› <span typeof="v:Breadcrumb">';
                $rows.='<a rel="v:url" property="v:title" href="#TEMATRES_SERVICES_URI#task=fetchTerm&arg='.(int) $value->term_id.'" title="'.(string) $value->string.'">'.(string) $value->string.'</a>';
                $rows.='</span>  ';
            }            else            {
                
                $rows.='› <span typeof="v:Breadcrumb">';
                $rows.=(string) $value->string;
                $rows.='</span>  ';
            }
        }

        $rows.='</div>';        

    }else{//there are only one result
        
        $rows='<div id="term_breadcrumb">';                    
        $rows.='<span typeof="v:Breadcrumb">';
        $rows.='<a rel="v:url" property="v:title" href="'.get_permalink().'" title="'.ucfirst($T3_messages["MENU_Inicio"]).'">'.ucfirst($T3_messages["MENU_Inicio"]).'</a>';
        $rows.='</span>  ';

        $rows.='› <span typeof="v:Breadcrumb">';
        $rows.=(string) $data->term->string;
        $rows.='</span>  ';
        $rows.='</div>';
    }

return (@$rows);
}

function data2html4MappedURITerms($data,$param=array()){

    if($data->resume->cant_result > 0) {
        foreach ($data->result->term as $value) {
            $rows='<li><span about="'.$value->term_id.'" typeof="skos:Concept">';
            $rows.=(string) $value->link_type.': <a resource="'.(string) $value->link.'" property="skos:'.(string) $value->link_type.'" href="'.(string) $value->link.'" title="'.(string) $value->link_type.' '.(string) $value->link.'">'.(string) $value->link.'</a>';
            $rows.='</span>';
            $rows.='</li>';
        }
    }
 return (@$rows);
};


function data2html4TopTerms($data,$param=array())
{

    if($data->resume->cant_result > 0) {
        
        $rows='<div><ul class="topterms">';

        foreach ($data->result->term as $value) {
            $term_id=(int) $value->term_id;
            $term_string=(string) $value->string;
            $class_li=($value->isMetaTerm==1) ? ' class="t3_metaTerm" ' :'';
            $rows.='<li '.$class_li.' about="#TEMATRES_SERVICES_URI#task=fetchTerm&arg='.$term_id.'" typeof="skos:Concept">';
            $rows.=($value->code) ? '<span property="skos:notation">'.$value->code.'</span> ' :'';
            $rows.='<a resource="#TEMATRES_SERVICES_URI#task=fetchTerm&arg='.$term_id.'" property="skos:hasTopConcept" href="#TEMATRES_SERVICES_URI#task=fetchTerm&arg='.$term_id.'" title="'.$term_string.'">'.$term_string.'</a>';
            $rows.='</li>';
            }
        $rows.='</ul>';
        $rows.='</div>';
    }
return (@$rows);
}





/*  fetch vocabulary metadata  */
function fetchVocabularyMetadata($url)
{
    $data=getURLdata($url.'?task=fetchVocabularyData');
    if (is_object($data)) {
        $array["title"]=        (string) $data->result->title;
        $array["author"]=       (string) $data->result->author;
        $array["lang"]=         (string) $data->result->lang;
        $array["scope"]=        (string) $data->result->scope;
        $array["keywords"]=     (string) $data->result->keywords;
        $array["lastMod"]=      (string) $data->result->lastMod;
        $array["uri"]=          (string) $data->result->uri;
        $array["contributor"]=  (string) $data->result->contributor;
        $array["publisher"]=    (string)$data->result->publisher;
        $array["rights"]=       (string) $data->result->rights;
        $array["createDate"]=   (string) $data->result->createDate;
        $array["cant_terms"]=   (int) $data->result->cant_terms;
        $array["adminEmail"]=   (string) $data->result->adminEmail;
        $array["tematres_uri"]=   (string) $url;
        $array["status"] = (string) $data->resume->status;
        $array["version"] = (string) $data->resume->version;
    } else {
        $array=array();
    }
    return $array;
}

/*  Funciones generales  */
// string 2 URL legible
// based on source from http://code.google.com/p/pan-fr/
function string2url ($string)
{
    $string = strtr($string,
    "�������������������������������������������������������",
    "AAAAAAaaaaaaCcOOOOOOooooooEEEEeeeeIIIIiiiiUUUUuuuuYYyyNn");
    $string = str_replace('�','AE',$string);
    $string = str_replace('�','ae',$string);
    $string = str_replace('�','OE',$string);
    $string = str_replace('�','oe',$string);
    $string = preg_replace('/[^a-z0-9_\s\'\:\/\[\]-]/','',strtolower($string));
    $string = preg_replace('/[\s\'\:\/\[\]-]+/',' ',trim($string));
    $res = str_replace(' ','-',$string);
    return $res;
}

//form http://www.compuglobalhipermega.net/php/php-url-semantica/
function is_utf ($t)
{
    if (@preg_match ('/.+/u', $t))
        return 1;
}

/* Banco de vocabularios 2013 */
// XML Entity Mandatory Escape Characters or CDATA
function xmlentities ($string, $pcdata=FALSE)
{
    if($pcdata == TRUE) {
        return  '<![CDATA[ '.str_replace ( array ('[[',']]' ), array ('',''), $string ).' ]]>';
    } else {
        return str_replace ( array ( '&', '"', "'", '<', '>','[[',']]' ), array ( '&amp;' , '&quot;', '&apos;' , '&lt;' , '&gt;','',''), $string );
    }
}

function fixEncoding($input, $output_encoding="UTF-8")
{
    return $input;
    // For some reason this is missing in the php4 in NMT
    $encoding = mb_detect_encoding($input);
    switch($encoding) {
        case 'ASCII':
        case $output_encoding:
            return $input;
        case '':
            return mb_convert_encoding($input, $output_encoding);
        default:
            return mb_convert_encoding($input, $output_encoding, $encoding);
    }
}


/*  Convierte una cadena a latin1
    http://gmt-4.blogspot.com/2008/04/conversion-de-unicode-y-latin1-en-php-5.html  */
function latin1($txt)
{
    $encoding = mb_detect_encoding($txt, 'ASCII,UTF-8,ISO-8859-1');
    if ($encoding == "UTF-8") {
        $txt = utf8_decode($txt);
    }
    return $txt;
}

/*  Convierte una cadena a utf8
    http://gmt-4.blogspot.com/2008/04/conversion-de-unicode-y-latin1-en-php-5.html  */
function utf8($txt)
{
    $encoding = mb_detect_encoding($txt, 'ASCII,UTF-8,ISO-8859-1');
    if ($encoding == "ISO-8859-1") {
        $txt = utf8_encode($txt);
    }
    return $txt;
}
/*  Arma un array con una fecha  */
function do_date($time)
{
    $array=array(
        'min'  => date("i",strtotime($time)),
        'hora' => date("G",strtotime($time)),
        'dia'  => date("d",strtotime($time)),
        'mes'  => date("m",strtotime($time)),
        'ano'  => date("Y",strtotime($time))
    );
    return $array;
}

?>