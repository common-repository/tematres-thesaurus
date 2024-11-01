<?php
/*
 *      proxy.php
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
$WP_Path = dirname( dirname( dirname( dirname( __FILE__ ) ) ) );

require_once( $WP_Path . '/wp-load.php' );

$searchq=(@$_GET['query']);
if (!$searchq) return;
//for JSONP
$callback=(@$_GET["callback"]) ? $_GET["callback"] : '';

$URL_BASE=$_GET["tematres_uri"];

if(strlen($searchq)>= 3) {
	echo getData4Autocompleter($URL_BASE,sanitize_text_field($searchq),$callback);
	exit;
}