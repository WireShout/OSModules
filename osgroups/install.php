<?php
	
	/*
	 *	PHP groups database installer. v0.1
	 *	developed by devi S.A.S (http://devi.com.co)
	 */
	 
	require_once('config.php');
	
	$installData = array('osagent' => 'CREATE TABLE `osagent` (`AgentID` varchar(128) NOT NULL default \'\', `ActiveGroupID` varchar(128) NOT NULL default \'\', PRIMARY KEY  (`AgentID`)) TYPE=MyISAM;',
						 'osgroup' => 'CREATE TABLE `osgroup` ( `GroupID` varchar(128) NOT NULL default \'\', `Name` varchar(255) NOT NULL default \'\', `Charter` text NOT NULL, `InsigniaID` varchar(128) NOT NULL default \'\', `FounderID` varchar(128) NOT NULL default \'\', `MembershipFee` int(11) NOT NULL default \'0\', `OpenEnrollment` varchar(255) NOT NULL default \'\', `ShowInList` tinyint(1) NOT NULL default \'0\', `AllowPublish` tinyint(1) NOT NULL default \'0\', `MaturePublish` tinyint(1) NOT NULL default \'0\', `OwnerRoleID` varchar(128) NOT NULL default \'\', PRIMARY KEY (`GroupID`), UNIQUE KEY `Name` (`Name`), FULLTEXT KEY `Name_2` (`Name`) ) TYPE=MyISAM;',
						 'osgroupinvite' => 'CREATE TABLE `osgroupinvite` ( `InviteID` varchar(128) NOT NULL default \'\', `GroupID` varchar(128) NOT NULL default \'\', `RoleID` varchar(128) NOT NULL default \'\', `AgentID` varchar(128) NOT NULL default \'\', `TMStamp` timestamp(14) NOT NULL, PRIMARY KEY (`InviteID`), UNIQUE KEY `GroupID` (`GroupID`,`RoleID`,`AgentID`) ) TYPE=MyISAM;',
						 'osgroupmembership' => 'CREATE TABLE `osgroupmembership` ( `GroupID` varchar(128) NOT NULL default \'\', `AgentID` varchar(128) NOT NULL default \'\', `SelectedRoleID` varchar(128) NOT NULL default \'\', `Contribution` int(11) NOT NULL default \'0\', `ListInProfile` int(11) NOT NULL default \'1\', `AcceptNotices` int(11) NOT NULL default \'1\', PRIMARY KEY (`GroupID`,`AgentID`) ) TYPE=MyISAM;',
						 'osgroupnotice' => 'CREATE TABLE `osgroupnotice` ( `GroupID` varchar(128) NOT NULL default \'\', `NoticeID` varchar(128) NOT NULL default \'\', `Timestamp` int(10) unsigned NOT NULL default \'0\', `FromName` varchar(255) NOT NULL default \'\', `Subject` varchar(255) NOT NULL default \'\', `Message` text NOT NULL, `BinaryBucket` text NOT NULL, PRIMARY KEY (`GroupID`,`NoticeID`), KEY `Timestamp` (`Timestamp`) ) TYPE=MyISAM;',
						 'osgrouprolemembership' => 'CREATE TABLE `osgrouprolemembership` ( `GroupID` varchar(128) NOT NULL default \'\', `RoleID` varchar(128) NOT NULL default \'\', `AgentID` varchar(128) NOT NULL default \'\', PRIMARY KEY (`GroupID`,`RoleID`,`AgentID`) ) TYPE=MyISAM;',
						 'osrole' => 'CREATE TABLE `osrole` ( `GroupID` varchar(128) NOT NULL default \'\', `RoleID` varchar(128) NOT NULL default \'\', `Name` varchar(255) NOT NULL default \'\', `Description` varchar(255) NOT NULL default \'\', `Title` varchar(255) NOT NULL default \'\', `Powers` bigint(20) unsigned NOT NULL default \'0\', PRIMARY KEY (`GroupID`,`RoleID`) ) TYPE=MyISAM;');
						 
    $database = mysql_connect($databaseHost,$databaseUserName,$databasePassword);
    if (!$database) die('Could not connect: ' . mysql_error());
	
    mysql_select_db($databaseName, $database); /* Select DB */
	
	echo '<h3>Connected to database, installing...</h3><br/>----------------------------------------<br/><br/><blockquote>'; flush();
	
	foreach($installData as $table->$query) {
		if(!table_exists($table, $database)) {
			$exec = mysql_query($query) or echo('> Error for table \'' . $table . '\': ' . mysql_error());
			if($exec) '> Table \'' . $table . '\' created!'.
			echo '<br/>';
		} else echo '> Table \'' . $table . '\' already exists, skipped!.<br/>'.
		flush();
	}
	
	echo '</blockquote><br/>----------------------------------------<br/><h4>Done!</h4>';
	
        mysql_close($database);
	
	function table_exists ($table, $db) { 
		$tables = mysql_list_tables ($db); 
		while (list ($temp) = mysql_fetch_array ($tables)) {
			if ($temp == $table) {
				return TRUE;
			}
		}
		return FALSE;
	}