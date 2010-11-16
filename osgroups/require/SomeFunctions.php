<?php

	function simpleSecurityCheck($params) {
		/* These globals are never used or is just me.. Anyway, keep them for futher compatibility.
			global $groupWriteKey, $groupReadKey, $verifiedReadKey, $verifiedWriteKey, $groupRequireAgentAuthForWrite, $requestingAgent; 
			lobal $overrideAgentUserService;
		*/
		
		global $securityReadKey, $securityWriteKey;
		$result = false;
		
		/* Key check */
		
		if( (empty($params['ReadKey']) && !empty($securityReadKey) ) || 
			(empty($params['WriteKey']) && !empty($securityWriteKey) )) return array('error' => "Empty Read/Write Key specified", 'params' => var_export($params, TRUE));
		if( ($params['ReadKey'] != $securityReadKey) || 
			($params['WriteKey'] != $securityReadKey) ) return array('error' => "Invalid Read/Write Key specified", 'params' => var_export($params, TRUE));
		
		return true;
	}