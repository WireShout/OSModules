<?php
	require_once('config.php');
	require_once('require/GroupFunctions.php');
	require_once('require/XmlFunctions.php');
	
	$data   = (isset($HTTP_RAW_POST_DATA)) ? $HTTP_RAW_POST_DATA : '';
	$trashTags = array('<param>','</param>','<value>','</value>','<member>','</member>','<struct>','</struct>');
	$arrayData = xml2array(str_replace($trashTags, '', $data));
	
	$function  = str_replace('groups.', '', $arrayData['methodCall']['methodName']);

	$Names  = $arrayData['methodCall']['params']['name'];
	$Values = $arrayData['methodCall']['params']['string'];
	$params = array_combine($Names, $Values);
	
	$result = $function($params);
	
	if($exportFormat == 'json') {
		header('Content-type: text/json');
		echo json_encode($result);
	}	else if($exportFormat == 'xml') {
		header('Content-type: text/xml');
		echo array2xml($result);
	}
	
	exit;
