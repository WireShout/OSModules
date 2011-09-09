<?php
	/* OSGroups, XMLRPC implementation.
	 *
	 * version 0.1 - release
	 * developed by devi S.A.S (http://devi.com.co)
	 * based on floatsam XMLRPCGroups (https://github.com/mcortez/flotsam)
	 * for help/support: https://github.com/jor3l/OSModules/issues
	*/
	
	require_once('config.php');
	require_once('require/GroupFunctions.php');
	require_once('require/XmlFunctions.php');
	require_once('require/SomeFunctions.php');
	
	$data   = (isset($HTTP_RAW_POST_DATA)) ? $HTTP_RAW_POST_DATA : '';
	if(empty($data)) die('OSGroups module.');
	$trashTags = array('<param>','</param>','<value>','</value>','<member>','</member>','<struct>','</struct>');
	$fixTags   = array('<i4>', '</i4>', '<string />');
	$fixTo     = array('<string>', '</string>', '<string></string>');
	$cleanedXML = str_replace($trashTags, '', $data);
	$arrayData = xml2array(str_replace($fixTags, $fixTo, $cleanedXML));
	
	$function  = str_replace('groups.', '', $arrayData['methodCall']['methodName']);

	$Names  = $arrayData['methodCall']['params']['name'];
	$Values = $arrayData['methodCall']['params']['string'];

	$params = array_combine($Names, $Values);
	
	$params['ReadKey']  = (is_array($params['ReadKey'])) ? implode('', $params['ReadKey']) : $params['ReadKey'];
	$params['WriteKey'] = (is_array($params['WriteKey'])) ? implode('', $params['WriteKey']) : $params['WriteKey'];

	$check  = simpleSecurityCheck($params);
	if(!isset($check['error'])) {
		$result = $function($params);
		if(isset($result['error']) && $debugLog) {
			$fh = fopen($debugFile, 'a+');
			$date = date('F j, Y, g:i a');

			fwrite($fh, "\n".'-----------ERROR LOG: ' . $date . '--------------');
			fwrite($fh, 'Original Request XML:'."\n\n" . $data);
			fwrite($fh, "\n\n".'Parsed params:'."\n\n" . json_encode($params));
			fwrite($fh, "\n\n".'Request result:'."\n\n" . json_encode($result));
			fwrite($fh, '---------------------------------------------------------------'."\n");

			fclose($fh);
		}
	} else {
		$result = $check;
	}
	
	if($exportFormat == 'json') {
		header('Content-type: text/json');
		echo json_encode($result);
	}	else if($exportFormat == 'xml') {
		header('Content-type: text/xml');
		echo array2xml($result);
	}
	
	exit;
