<?php	
	$OMTN = 'OfflineMessages';
	
	/* Process offile_message requests */
	$method = (isset($_SERVER['PATH_INFO'])) ? $_SERVER['PATH_INFO'] : 'none';
	$data   = (isset($HTTP_RAW_POST_DATA)) ? substr($HTTP_RAW_POST_DATA,3) : '';
	
	if ($method == '/SaveMessage/') {
		require_once('database.php');
		$db = mysql_connect(HOST,USER,PASS);
			  mysql_select_db(DB);
			  
		if(mysql_num_rows(mysql_query("SHOW TABLES LIKE '$OMTN'")) <= 0) mysql_query('CREATE TABLE IF NOT EXISTS `'.$OMTN.'` (`uuid` varchar(36) NOT NULL,`message` text NOT NULL, KEY `uuid` (`uuid`))');
		
		$toAgent = getBetween($data, '<toAgentID>', '</toAgentID>');
		
		mysql_query("INSERT INTO `$OMTN` (uuid, message) VALUES ('$toAgent', '$data')") or die('<?xml version="1.0" encoding="utf-8"?><boolean>false</boolean>');
		echo '<?xml version="1.0" encoding="utf-8"?><boolean>true</boolean>'; // Offline message stored.
		
		mysql_close($db);
	} else if ($method == '/RetrieveMessages/') {
		//Guid
		require_once('database.php');
		$db = mysql_connect(HOST,USER,PASS);
			  mysql_select_db(DB);
		
		if(mysql_num_rows(mysql_query("SHOW TABLES LIKE '$OMTN'")) <= 0) mysql_query('CREATE TABLE IF NOT EXISTS `'.$OMTN.'` (`uuid` varchar(36) NOT NULL,`message` text NOT NULL, KEY `uuid` (`uuid`))');

		$userID = getBetween($data, '<Guid>', '</Guid>');
		
		$query = @mysql_query("SELECT message FROM `$OMTN` WHERE uuid='$userID'");
		if(mysql_num_rows($query) > 0) {
			$array_messages = array();
			while($row = mysql_fetch_array($query)) {
				$array_messages[] = $row['message'];
			}
		} mysql_close($db);
		
		echo '<?xml version=\"1.0\" encoding=\"utf-8\"?><ArrayOfGridInstantMessage xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\">';
        if(isset($array_messages)) echo implode('',$array_messages);
		echo '</ArrayOfGridInstantMessage>';
	}
	
	function getBetween($content,$start,$end) {
		$a1 = strrpos($content,$start);
		$content = substr($content,$a1 + strlen($start));
		while($a2 = strrpos($content,$end))
		{
			$content = substr($content,0,$a2);
		}
		return $content;
	}
 