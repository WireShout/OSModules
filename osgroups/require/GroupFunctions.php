<?php 
	/* groups.FUNCTION */
    
    $groupDBCon = mysql_connect($databaseHost,$databaseUserName,$databasePassword);
	
    if (!$groupDBCon)    {
        die('Could not connect: ' . mysql_error());
    }
	
    mysql_select_db($databaseName, $groupDBCon);

    // This is filled in by secure()
    $requestingAgent = $uuidZero;
    
    function test() {
        return array('name' => 'Joe','age' => 27);
    }

    function createGroup($params)
    {	
        global $groupEnforceGroupPerms, $requestingAgent, $uuidZero, $groupDBCon;

        $groupID        = $params["GroupID"];
        $name           = $params["Name"];
        $charter        = $params["Charter"];
        $insigniaID     = $params["InsigniaID"];
        $founderID      = $params["FounderID"];
        $membershipFee  = $params["MembershipFee"];
        $openEnrollment = $params["OpenEnrollment"];
        $showInList     = $params["ShowInList"];
        $allowPublish   = $params["AllowPublish"];
        $maturePublish  = $params["MaturePublish"];
        $ownerRoleID    = $params["OwnerRoleID"];
        $everyonePowers = $params["EveryonePowers"];
        $ownersPowers   = 490382185988094; //$params["OwnersPowers"];
	
	 if(empty($groupID) || empty($name) || empty($founderID) || empty($insigniaID))
		return array('error' => 'Some necessary params were empty.');
        
        $escapedParams         = array_map("mysql_real_escape_string", $params);
        $escapedGroupID        = $escapedParams["GroupID"];
        $escapedName           = $escapedParams["Name"];
        $escapedCharter        = $escapedParams["Charter"];
        $escapedInsigniaID     = $escapedParams["InsigniaID"];
        $escapedFounderID      = $escapedParams["FounderID"];
        $escapedMembershipFee  = $escapedParams["MembershipFee"];
        $escapedOpenEnrollment = $escapedParams["OpenEnrollment"];
        $escapedShowInList     = $escapedParams["ShowInList"];
        $escapedAllowPublish   = $escapedParams["AllowPublish"];
        $escapedMaturePublish  = $escapedParams["MaturePublish"];
        $escapedOwnerRoleID    = $escapedParams["OwnerRoleID"];

        // Create group
        $sql = "INSERT INTO osgroup
                (GroupID, Name, Charter, InsigniaID, FounderID, MembershipFee, OpenEnrollment, ShowInList, AllowPublish, MaturePublish, OwnerRoleID)
                VALUES
                ('$escapedGroupID', '$escapedName', '$escapedCharter', '$escapedInsigniaID', '$escapedFounderID', '$escapedMembershipFee', '$escapedOpenEnrollment', '$escapedShowInList', '$escapedAllowPublish', '$escapedMaturePublish', '$escapedOwnerRoleID')";
        
        if (!mysql_query($sql, $groupDBCon))
        {
            return array('error' => "Could not successfully run query ($sql) from DB: " . mysql_error(), 'params' => var_export($params, TRUE));
        }
        
        // Create Everyone Role
        // NOTE: FIXME: This is a temp fix until the libomv enum for group powers is fixed in OpenSim
		
        $result = _addRoleToGroup(array('GroupID' => $groupID, 'RoleID' => $uuidZero, 'Name' => 'Everyone', 'Description' => 'Everyone in the group is in the everyone role.', 'Title' => "Member of $name", 'Powers' => $everyonePowers));
        if( isset($result['error']) )
        {
            return $result;
        }

        // Create Owner Role
        $result = _addRoleToGroup(array('GroupID' => $groupID, 'RoleID' => $ownerRoleID, 'Name' => 'Owners', 'Description' => "Owners of $name", 'Title' => "Owner of $name", 'Powers' => $ownersPowers));
        if( isset($result['error']) )
        {
            return $result;
        }

        // Add founder to group, will automatically place them in the Everyone Role, also places them in specified Owner Role
        $result = _addAgentToGroup(array('AgentID' => $founderID, 'GroupID' => $groupID, 'RoleID' => $ownerRoleID));
        if( isset($result['error']) )
        {
            return $result;
        }
        
        // Select the owner's role for the founder
        $result = _setAgentGroupSelectedRole(array('AgentID' => $founderID, 'RoleID' => $ownerRoleID, 'GroupID' => $groupID));
        if( isset($result['error']) )
        {
            return $result;
        }
        
        // Set the new group as the founder's active group
        $result = _setAgentActiveGroup(array('AgentID' => $founderID, 'GroupID' => $groupID));
        if( isset($result['error']) )
        {
            return $result;
        }
        
        return getGroup(array("GroupID"=>$groupID));
    }
    
	  // Private method, does not include security, to only be called from places that have already verified security
    function _addRoleToGroup($params)
    {
        $everyonePowers = 8796495740928; // This should now be fixed, when libomv was updated...		
	
        global $groupEnforceGroupPerms, $requestingAgent, $uuidZero, $groupDBCon, $groupPowers;
        $groupID = mysql_real_escape_string( $params['GroupID'] );
        $roleID  = mysql_real_escape_string( $params['RoleID'] );
        $name    = mysql_real_escape_string( $params['Name'] );
        $desc    = mysql_real_escape_string( $params['Description'] );
        $title   = mysql_real_escape_string( $params['Title'] );
        $powers  = mysql_real_escape_string( $params['Powers'] );

        if( !isset($powers) || ($powers == 0) || ($powers == '') )
        {
            $powers = $everyonePowers;
        }
        
        $sql = " INSERT INTO osrole (GroupID, RoleID, Name, Description, Title, Powers) VALUES "
              ." ('$groupID', '$roleID', '$name', '$desc', '$title', '$powers')";

        if (!mysql_query($sql, $groupDBCon))
        {
            return array('error' => "Could not successfully run query ($sql) from DB: " . mysql_error()
                       , 'method' => 'addRoleToGroup'
                       , 'params' => var_export($params, TRUE));
        }
        
        return array("success" => "true");
    }
	
    function addRoleToGroup($params)
    {
        global $groupEnforceGroupPerms, $requestingAgent, $uuidZero, $groupDBCon, $groupPowers;
        $groupID = $params['GroupID'];
          
        // Verify the requesting agent has permission
        if( is_array($error = checkGroupPermission($groupID, $groupPowers['CreateRole'])) )
        {
            return $error;
        }

        return _addRoleToGroup($params);
    }
    
    function updateGroupRole($params)
    {
        global $groupEnforceGroupPerms, $requestingAgent, $uuidZero, $groupDBCon, $groupPowers;
        $groupID = mysql_real_escape_string( $params['GroupID'] );
        $roleID  = mysql_real_escape_string( $params['RoleID'] );
        $name    = mysql_real_escape_string( $params['Name'] );
        $desc    = mysql_real_escape_string( $params['Description'] );
        $title   = mysql_real_escape_string( $params['Title'] );
        $powers  = mysql_real_escape_string( $params['Powers'] );
        
        // Verify the requesting agent has permission
        if( is_array($error = checkGroupPermission($groupID, $groupPowers['RoleProperties'])) )
        {
            return $error;
        }
		
        $sql = " UPDATE osrole SET RoleID = '$roleID' ";
        if( isset($params['Name']) )
        {
            $sql .= ", Name = '$name'";
        }
        if( isset($params['Description']) )
        {
            $sql .= ", Description = '$desc'";
        }
        if( isset($params['Title']) )
        {
            $sql .= ", Title = '$title'";
        }
        if( isset($params['Powers']) )
        {
            $sql .= ", Powers = '$powers'";
        }
        
        $sql .= " WHERE GroupID = '$groupID' AND RoleID = '$roleID'";

        if (!mysql_query($sql, $groupDBCon))
        {
            return array('error' => "Could not successfully run query ($sql) from DB: " . mysql_error(), 'params' => var_export($params, TRUE));
        }
        
        return array("success" => "true");
    }
    
    function removeRoleFromGroup($params)
    {
        global $groupEnforceGroupPerms, $requestingAgent, $uuidZero, $groupDBCon, $groupPowers;
        $groupID = mysql_real_escape_string( $params['GroupID'] );
        $roleID  = mysql_real_escape_string( $params['RoleID'] );
        
        if( is_array($error = checkGroupPermission($groupID, $groupPowers['RoleProperties'])) )
        {
            return $error;
        }
		
        /// 1. Remove all members from Role
        /// 2. Set selected Role to uuidZero for anyone that had the role selected
        /// 3. Delete roll
        
        $sql = "DELETE FROM osgrouprolemembership WHERE GroupID = '$groupID' AND RoleID = '$roleID'";
        if (!mysql_query($sql, $groupDBCon))
        {
            return array('error' => "Could not successfully run query ($sql) from DB: " . mysql_error(), 'params' => var_export($params, TRUE));
        }
        
        $sql = "UPDATE osgroupmembership SET SelectedRoleID = '$uuidZero' WHERE GroupID = '$groupID' AND SelectedRoleID = '$roleID'";
        if (!mysql_query($sql, $groupDBCon))
        {
            return array('error' => "Could not successfully run query ($sql) from DB: " . mysql_error(), 'params' => var_export($params, TRUE));
        }
        
        $sql = "DELETE FROM osrole WHERE GroupID = '$groupID' AND RoleID = '$roleID'";
        if (!mysql_query($sql, $groupDBCon))
        {
            return array('error' => "Could not successfully run query ($sql) from DB: " . mysql_error(), 'params' => var_export($params, TRUE));
        }
        
        return array("success" => "true");
    }
    
    function getGroup($params) {
        return _getGroup($params);
    }
		
    function _getGroup($params)
    {
        global $groupEnforceGroupPerms, $requestingAgent, $uuidZero, $groupDBCon;
        $sql = " SELECT osgroup.GroupID, osgroup.Name, Charter, InsigniaID, FounderID, MembershipFee, OpenEnrollment, ShowInList, AllowPublish, MaturePublish, OwnerRoleID"
              ." , count(osrole.RoleID) as GroupRolesCount, count(osgroupmembership.AgentID) as GroupMembershipCount "
              ." FROM osgroup "
              ." LEFT JOIN osrole ON (osgroup.GroupID = osrole.GroupID)"
              ." LEFT JOIN osgroupmembership ON (osgroup.GroupID = osgroupmembership.GroupID)"
              ." WHERE ";

        if( isset($params['GroupID']) )
        {
            $sql .= "osgroup.GroupID = '" . mysql_real_escape_string($params['GroupID']). "'";
        } 
        else if( isset($params['Name']) ) 
        {
            $sql .= "osgroup.Name = '" . mysql_real_escape_string($params['Name']) . "'";
        } 
        else 
        {
            return array("error" => "Must specify GroupID or Name");
        }
        
        $sql .= " GROUP BY osgroup.GroupID, osgroup.name, charter, insigniaID, founderID, membershipFee, openEnrollment, showInList, allowPublish, maturePublish, ownerRoleID";
        
        $result = mysql_query($sql, $groupDBCon);
        
        if (!$result) 
        {
            return array('error' => "Could not successfully run query ($sql) from DB: " . mysql_error(), 'params' => var_export($params, TRUE));
        }

        if (mysql_num_rows($result) == 0) 
        {
            return array('succeed' => 'false', 'error' => 'Group Not Found', 'params' => var_export($params, TRUE), 'sql' => $sql);
        }
        
        return mysql_fetch_assoc($result);
    }        
    
    function updateGroup($params)
    {
        global $groupEnforceGroupPerms, $requestingAgent, $uuidZero, $groupDBCon, $groupPowers;
        $groupID = mysql_real_escape_string( $params["GroupID"] );
        $charter = mysql_real_escape_string( $params["Charter"] );
        $insigniaID = mysql_real_escape_string( $params["InsigniaID"] );
        $membershipFee = mysql_real_escape_string( $params["MembershipFee"] );
        $openEnrollment = mysql_real_escape_string( $params["OpenEnrollment"] );
        $showInList = mysql_real_escape_string( $params["ShowInList"] );
        $allowPublish = mysql_real_escape_string( $params["AllowPublish"] );
        $maturePublish = mysql_real_escape_string( $params["MaturePublish"] );
        
        if( is_array($error = checkGroupPermission($groupID, $groupPowers['ChangeOptions'])) )
        {
            return $error;
        }
		
        // Create group
        $sql = "UPDATE osgroup
                SET
                    Charter = '$charter'
                    , InsigniaID = '$insigniaID'
                    , MembershipFee = '$membershipFee'
                    , OpenEnrollment= '$openEnrollment'
                    , ShowInList    = '$showInList'
                    , AllowPublish  = '$allowPublish'
                    , MaturePublish = '$maturePublish'
                WHERE
                    GroupID = '$groupID'";
        
        if (!mysql_query($sql, $groupDBCon))
        {
            return array('error' => "Could not successfully run query ($sql) from DB: " . mysql_error(), 'params' => var_export($params, TRUE));
        }

        return array('success' => 'true');
    }

    function findGroups($params)
    {
        global $groupEnforceGroupPerms, $requestingAgent, $uuidZero, $groupDBCon;
        $search = mysql_real_escape_string( $params['Search'] );
        
        $sql = " SELECT osgroup.GroupID, osgroup.Name, count(osgroupmembership.AgentID) as Members "
              ." FROM osgroup LEFT JOIN osgroupmembership ON (osgroup.GroupID = osgroupmembership.GroupID) "
              ." WHERE "
			  ." (    MATCH (osgroup.name) AGAINST ('$search' IN BOOLEAN MODE)"
              ."   OR osgroup.name LIKE '%$search%'"
              ."   OR osgroup.name REGEXP '$search'"
			  ." ) AND ShowInList = 1" 
              ." GROUP BY osgroup.GroupID, osgroup.Name";
        
        $result = mysql_query($sql, $groupDBCon);
        
        if (!$result) 
        {
            return array('error' => "Could not successfully run query ($sql) from DB: " . mysql_error(), 'params' => var_export($params, TRUE));
        }

        if( mysql_num_rows($result) == 0 )
        {
            return array('succeed' => 'false', 'error' => 'No groups found.', 'params' => var_export($params, TRUE), 'sql' => $sql);
        }
        
        $results = array();

        while ($row = mysql_fetch_assoc($result)) 
        {
            $groupID = $row['GroupID'];
            $results[$groupID] = $row;
        }
        
        return array('results' => $results, 'success' => TRUE);
    }
    
    function _setAgentActiveGroup($params)
    {
        global $groupEnforceGroupPerms, $requestingAgent, $uuidZero, $groupDBCon;
        $agentID = mysql_real_escape_string( $params['AgentID'] );
        $groupID = mysql_real_escape_string( $params['GroupID'] );
		
        $sql = " UPDATE osagent "
              ." SET ActiveGroupID = '$groupID'"
              ." WHERE AgentID = '$agentID'";
    
        if (!mysql_query($sql, $groupDBCon))
        {
            return array('error' => "Could not successfully run query ($sql) from DB: " . mysql_error(), 'params' => var_export($params, TRUE));
        }
        
        if( mysql_affected_rows() == 0 )
        {
            $sql = " INSERT INTO osagent (ActiveGroupID, AgentID) VALUES "
                  ." ('$groupID', '$agentID')";
        
            if (!mysql_query($sql, $groupDBCon))
            {
                return array('error' => "Could not successfully run query ($sql) from DB: " . mysql_error(), 'params' => var_export($params, TRUE));
            }
        }
    
        return array("success" => "true");
    }

    function setAgentActiveGroup($params)
    {
        global $groupEnforceGroupPerms, $requestingAgent, $uuidZero, $groupDBCon;
        $agentID = $params['AgentID'];
        $groupID = $params['GroupID'];
        
        if( isset($requestingAgent) && ($requestingAgent != $uuidZero) && ($requestingAgent != $agentID) )
        {
            return array('error' => "Agent can only change their own Selected Group Role", 'params' => var_export($params, TRUE));
        }
        
        return _setAgentActiveGroup($params);
    }
    
    function addAgentToGroup($params)
    {
        global $groupEnforceGroupPerms, $requestingAgent, $uuidZero, $groupDBCon, $groupPowers;
        $groupID = $params["GroupID"];
        $agentID = $params["AgentID"];        
		
        if( is_array($error = checkGroupPermission($groupID, $groupPowers['AssignMember'])) )
        {
            // If they don't have direct permission, check to see if the group is marked for open enrollment
            $groupInfo = _getGroup( array ('GroupID' => $groupID) );
          
            if( isset($groupInfo['error']))
            {
                return $groupInfo;
            }

            if($groupInfo['OpenEnrollment'] != 1)
            {
                $escapedAgentID = mysql_real_escape_string($agentID);
                $escapedGroupID = mysql_real_escape_string($groupID);

                // Group is not open enrollment, check if the specified agentid has an invite
                $sql = " SELECT GroupID, RoleID, AgentID FROM osgroupinvite"
                      ." WHERE osgroupinvite.AgentID = '$escapedAgentID' AND osgroupinvite.GroupID = '$escapedGroupID'";
                        
                $results = mysql_query($sql, $groupDBCon);
                if (!$results) 
                {
                    return array('error' => "Could not successfully run query ($sql) from DB: " . mysql_error(), 'params' => var_export($params, TRUE));
                }
                  
                if( mysql_num_rows($results) == 1 )
                {
                    // if there is an invite, make sure we're adding the user to the role specified in the invite
                    $inviteInfo = mysql_fetch_assoc($results);
                    $params['RoleID'] = $inviteInfo['RoleID'];
                } 
                else 
                {
                    // Not openenrollment, not invited, return permission denied error
                    return $error;
                }
            } 
        }

        return _addAgentToGroup($params);
    }
    
	  // Private method, does not include security, to only be called from places that have already verified security
    function _addAgentToGroup($params)
    {
        global $groupEnforceGroupPerms, $requestingAgent, $uuidZero, $groupDBCon;
        $agentID = $params["AgentID"];
        $groupID = $params["GroupID"];
        
        $roleID  = $uuidZero;
        if( isset($params["RoleID"]) )
        {
            $roleID = $params["RoleID"];
        }

        $escapedAgentID = mysql_real_escape_string($agentID);
        $escapedGroupID = mysql_real_escape_string($groupID);
        $escapedRoleID = mysql_real_escape_string($roleID);
    
        // Check if agent already a member
        $sql = " SELECT count(AgentID) as isMember FROM osgroupmembership WHERE AgentID = '$escapedAgentID' AND GroupID = '$escapedGroupID'";
        $result = mysql_query($sql, $groupDBCon);
        if (!$result)
        {
            return array('error' => "Could not successfully run query ($sql) from DB: " . mysql_error(), 'params' => var_export($params, TRUE));
        }

        // If not a member, add membership, select role (defaults to uuidZero, or everyone role)
        if( mysql_result($result, 0) == 0 )
        {
            $sql = " INSERT INTO osgroupmembership (GroupID, AgentID, Contribution, ListInProfile, AcceptNotices, SelectedRoleID) VALUES "
                  ."('$escapedGroupID','$escapedAgentID', 0, 1, 1,'$escapedRoleID')";
        
            if (!mysql_query($sql, $groupDBCon))
            {
                return array('error' => "Could not successfully run query ($sql) from DB: " . mysql_error(), 'params' => var_export($params, TRUE));
            }
        }
        
        // Make sure they're in the Everyone role
        $result = _addAgentToGroupRole(array("GroupID" => $groupID, "RoleID" => $uuidZero, "AgentID" => $agentID));
        if( isset($result['error']) )
        {
            return $result;
        }
        
        // Make sure they're in specified role, if they were invited
        if( $roleID != $uuidZero )
        {
            $result = _addAgentToGroupRole(array("GroupID" => $groupID, "RoleID" => $roleID, "AgentID" => $agentID));
            if( isset($result['error']) )
            {
                return $result;
            }
        }

		    //Set the role they were invited to as their selected role
        _setAgentGroupSelectedRole(array('AgentID' => $agentID, 'RoleID' => $roleID, 'GroupID' => $groupID));
		
        // Set the group as their active group.
        // _setAgentActiveGroup(array("GroupID" => $groupID, "AgentID" => $agentID));
		
        return array("success" => "true");
    }
    
    function removeAgentFromGroup($params)
    {
      	 global $groupEnforceGroupPerms, $requestingAgent, $uuidZero, $groupDBCon, $groupPowers;
        $agentID = $params["AgentID"];
        $groupID = $params["GroupID"];

        // An agent is always allowed to remove themselves from a group -- so only check if the requesting agent is different then the agent being removed.
        if( $agentID != $requestingAgent )
        {
            if( is_array($error = checkGroupPermission($groupID, $groupPowers['RemoveMember'])) )
            {
                return $error;
            }
        }

        $escapedAgentID = mysql_real_escape_string($agentID);
        $escapedGroupID = mysql_real_escape_string($groupID);
		
        // 1. If group is agent's active group, change active group to uuidZero
        // 2. Remove Agent from group (osgroupmembership)
        // 3. Remove Agent from all of the groups roles (osgrouprolemembership)
        
        $sql = " UPDATE osagent "
              ." SET ActiveGroupID = '$uuidZero'"
              ." WHERE AgentID = '$escapedAgentID' AND ActiveGroupID = '$escapedGroupID'";

        if (!mysql_query($sql, $groupDBCon))
        {
            return array('error' => "Could not successfully run query ($sql) from DB: " . mysql_error(), 'params' => var_export($params, TRUE));
        }
        
        $sql = " DELETE FROM osgroupmembership "
              ." WHERE AgentID = '$agentID' AND GroupID = '$groupID'";
        if (!mysql_query($sql, $groupDBCon))
        {
            return array('error' => "Could not successfully run query ($sql) from DB: " . mysql_error(), 'params' => var_export($params, TRUE));
        }
        
        $sql = " DELETE FROM osgrouprolemembership "
              ." WHERE AgentID = '$escapedAgentID' AND GroupID = '$escapedGroupID'";
        if (!mysql_query($sql, $groupDBCon))
        {
            return array('error' => "Could not successfully run query ($sql) from DB: " . mysql_error(), 'params' => var_export($params, TRUE));
        }
        
        return array("success" => "true");
    }
    
    function _addAgentToGroupRole($params)
    {
        global $groupEnforceGroupPerms, $requestingAgent, $uuidZero, $groupDBCon;
        $agentID = mysql_real_escape_string($params["AgentID"]);
        $groupID = mysql_real_escape_string($params["GroupID"]);
        $roleID = mysql_real_escape_string($params["RoleID"]);
    
        // Check if agent already a member
        $sql = " SELECT count(AgentID) as isMember FROM osgrouprolemembership WHERE AgentID = '$agentID' AND RoleID = '$roleID' AND GroupID = '$groupID'";
        $result = mysql_query($sql, $groupDBCon);
        if (!$result)
        {
            return array('error' => "Could not successfully run query ($sql) from DB: " . mysql_error(), 'params' => var_export($params, TRUE));
        }
    
        if( mysql_result($result, 0) == 0 )
        {
            $sql = " INSERT INTO osgrouprolemembership (GroupID, RoleID, AgentID) VALUES "
                  ."('$groupID', '$roleID', '$agentID')";
        
            if (!mysql_query($sql, $groupDBCon))
            {
                return array('error' => "Could not successfully run query ($sql) from DB: " . mysql_error(), 'params' => var_export($params, TRUE));
            }
        }
    
        return array("success" => "true");
    }
    
    function addAgentToGroupRole($params)
    {		
        global $groupEnforceGroupPerms, $requestingAgent, $uuidZero, $groupDBCon, $groupPowers;
        $agentID = $params["AgentID"];
        $groupID = $params["GroupID"];
        $roleID = $params["RoleID"];

        $escapedAgentID = mysql_real_escape_string($agentID);
        $escapedGroupID = mysql_real_escape_string($groupID);
        $escapedRoleID = mysql_real_escape_string($roleID);
    
        // Check if being assigned to Owners role, assignments to an owners role can only be requested by owners.
        $sql = " SELECT OwnerRoleID, osgrouprolemembership.AgentID "
              ." FROM osgroup LEFT JOIN osgrouprolemembership ON (osgroup.GroupID = osgrouprolemembership.GroupID AND osgroup.OwnerRoleID = osgrouprolemembership.RoleID) "
            ." WHERE osgrouprolemembership.AgentID = '" . mysql_real_escape_string($requestingAgent) . "' AND osgroup.GroupID = '$escapedGroupID'";
			  
        $results = mysql_query($sql, $groupDBCon);
        if (!$results) 
        {
            return array('error' => "Could not successfully run query ($sql) from DB: " . mysql_error(), 'params' => var_export($params, TRUE));
        }
		
        if( mysql_num_rows($results) == 0 )
        {
			return array('error' => "Group ($groupID) not found or Agent ($agentID) is not in the owner's role", 'params' => var_export($params, TRUE));
		}

        $ownerRoleInfo = mysql_fetch_assoc($results);
        if( ($ownerRoleInfo['OwnerRoleID'] == $roleID) && ($ownerRoleInfo['AgentID'] != $requestingAgent) )
        {
            return array('error' => "Requesting agent $requestingAgent is not a member of the Owners Role and cannot add members to the owners role.", 'params' => var_export($params, TRUE));
        }
	
        if( is_array($error = checkGroupPermission($groupID, $groupPowers['AssignMember'])) )
        {
            return $error;
        }
	
		return _addAgentToGroupRole($params);
    }
    
    function removeAgentFromGroupRole($params)
    {
        global $groupEnforceGroupPerms, $requestingAgent, $uuidZero, $groupDBCon, $groupPowers;
        $agentID = mysql_real_escape_string($params["AgentID"]);
        $groupID = mysql_real_escape_string($params["GroupID"]);
        $roleID  = mysql_real_escape_string($params["RoleID"]);

        if( is_array($error = checkGroupPermission($groupID, $groupPowers['AssignMember'])) )
        {
            return $error;
        }
		
        // If agent has this role selected, change their selection to everyone (uuidZero) role
        $sql = " UPDATE osgroupmembership SET SelectedRoleID = '$uuidZero' WHERE AgentID = '$agentID' AND GroupID = '$groupID' AND SelectedRoleID = '$roleID'";
        $result = mysql_query($sql, $groupDBCon);
        if (!$result)
        {
            return array('error' => "Could not successfully run query ($sql) from DB: " . mysql_error(), 'params' => var_export($params, TRUE));
        }

        $sql = " DELETE FROM osgrouprolemembership WHERE AgentID = '$agentID' AND GroupID = '$groupID' AND RoleID = '$roleID'";
    
        if (!mysql_query($sql, $groupDBCon))
        {
            return array('error' => "Could not successfully run query ($sql) from DB: " . mysql_error(), 'params' => var_export($params, TRUE));
        }
        
        return array("success" => "true");
    }
    
    function _setAgentGroupSelectedRole($params)
    {
        global $groupEnforceGroupPerms, $requestingAgent, $uuidZero, $groupDBCon;
        $agentID = mysql_real_escape_string($params["AgentID"]);
        $groupID = mysql_real_escape_string($params["GroupID"]);
        $roleID = mysql_real_escape_string($params["RoleID"]);
    
        $sql = " UPDATE osgroupmembership SET SelectedRoleID = '$roleID' WHERE AgentID = '$agentID' AND GroupID = '$groupID'";
        $result = mysql_query($sql, $groupDBCon);
        if (!$result)
        {
            return array('error' => "Could not successfully run query ($sql) from DB: " . mysql_error(), 'params' => var_export($params, TRUE));
        }
    
        return array('success' => 'true');
    }

    function setAgentGroupSelectedRole($params)
    {
        global $groupEnforceGroupPerms, $requestingAgent, $uuidZero, $groupDBCon;
        $agentID = $params["AgentID"];
        $groupID = $params["GroupID"];
        $roleID = $params["RoleID"];
    
        if( isset($requestingAgent) && ($requestingAgent != $uuidZero) && ($requestingAgent != $agentID) )
        {
            return array('error' => "Agent can only change their own Selected Group Role", 'params' => var_export($params, TRUE));
        }
	
        return _setAgentGroupSelectedRole($params);
    }
	
    function getAgentGroupMembership($params)
    {
        global $groupEnforceGroupPerms, $requestingAgent, $uuidZero, $groupDBCon;
        $groupID = mysql_real_escape_string($params['GroupID']);
        $agentID = mysql_real_escape_string($params['AgentID']);
        
        $sql = " SELECT osgroup.GroupID, osgroup.Name as GroupName, osgroup.Charter, osgroup.InsigniaID, osgroup.FounderID, osgroup.MembershipFee, osgroup.OpenEnrollment, osgroup.ShowInList, osgroup.AllowPublish, osgroup.MaturePublish"
              ." , osgroupmembership.Contribution, osgroupmembership.ListInProfile, osgroupmembership.AcceptNotices"
              ." , osgroupmembership.SelectedRoleID, osrole.Title"
              ." , osagent.ActiveGroupID "
              ." FROM osgroup JOIN osgroupmembership ON (osgroup.GroupID = osgroupmembership.GroupID)"
              ."              JOIN osrole ON (osgroupmembership.SelectedRoleID = osrole.RoleID AND osgroupmembership.GroupID = osrole.GroupID)"
              ."              JOIN osagent ON (osagent.AgentID = osgroupmembership.AgentID)"
              ." WHERE osgroup.GroupID = '$groupID' AND osgroupmembership.AgentID = '$agentID'";
        
        $groupmembershipResult = mysql_query($sql, $groupDBCon);
        if (!$groupmembershipResult) 
        {
            return array('error' => "Could not successfully run query ($sql) from DB: " . mysql_error(), 'params' => var_export($params, TRUE));
        }
        
        if( mysql_num_rows($groupmembershipResult) == 0 )
        {
            return array('succeed' => 'false', 'error' => 'None Found', 'params' => var_export($params, TRUE), 'sql' => $sql);
        }
        
        $groupMembershipInfo = mysql_fetch_assoc($groupmembershipResult);
        
        $sql = " SELECT BIT_OR(osrole.Powers) AS GroupPowers"
              ." FROM osgrouprolemembership JOIN osrole ON (osgrouprolemembership.GroupID = osrole.GroupID AND osgrouprolemembership.RoleID = osrole.RoleID)"
              ." WHERE osgrouprolemembership.GroupID = '$groupID' AND osgrouprolemembership.AgentID = '$agentID'";
        $groupPowersResult = mysql_query($sql, $groupDBCon);
        if (!$groupPowersResult) 
        {
            return array('error' => "Could not successfully run query ($sql) from DB: " . mysql_error(), 'params' => var_export($params, TRUE));
        }
        $groupPowersInfo = mysql_fetch_assoc($groupPowersResult);
        
        return array_merge($groupMembershipInfo, $groupPowersInfo);
    }

    function getAgentGroupMemberships($params)
    {
        global $groupEnforceGroupPerms, $requestingAgent, $uuidZero, $groupDBCon;
        $agentID = mysql_real_escape_string($params['AgentID']);
        
        $sql = " SELECT osgroup.GroupID, osgroup.Name as GroupName, osgroup.Charter, osgroup.InsigniaID, osgroup.FounderID, osgroup.MembershipFee, osgroup.OpenEnrollment, osgroup.ShowInList, osgroup.AllowPublish, osgroup.MaturePublish"
              ." , osgroupmembership.Contribution, osgroupmembership.ListInProfile, osgroupmembership.AcceptNotices"
              ." , osgroupmembership.SelectedRoleID, osrole.Title"
              ." , IFNULL(osagent.ActiveGroupID, '$uuidZero') AS ActiveGroupID"
              ." FROM osgroup JOIN osgroupmembership ON (osgroup.GroupID = osgroupmembership.GroupID)"
              ."              JOIN osrole ON (osgroupmembership.SelectedRoleID = osrole.RoleID AND osgroupmembership.GroupID = osrole.GroupID)"
              ."         LEFT JOIN osagent ON (osagent.AgentID = osgroupmembership.AgentID)"
              ." WHERE osgroupmembership.AgentID = '$agentID'";
        
        $groupmembershipResults = mysql_query($sql, $groupDBCon);
        if (!$groupmembershipResults) 
        {
            return array('error' => "Could not successfully run query ($sql) from DB: " . mysql_error(), 'params' => var_export($params, TRUE));
        }

        if( mysql_num_rows($groupmembershipResults) == 0 )
        {
            return array('succeed' => 'false', 'error' => 'No Memberships', 'params' => var_export($params, TRUE), 'sql' => $sql);
        }
        
        $groupResults = array();
        while($groupMembershipInfo = mysql_fetch_assoc($groupmembershipResults))
        {
            $groupID = $groupMembershipInfo['GroupID'];
            $sql = " SELECT BIT_OR(osrole.Powers) AS GroupPowers"
                  ." FROM osgrouprolemembership JOIN osrole ON (osgrouprolemembership.GroupID = osrole.GroupID AND osgrouprolemembership.RoleID = osrole.RoleID)"
                  ." WHERE osgrouprolemembership.GroupID = '$groupID' AND osgrouprolemembership.AgentID = '$agentID'";
            $groupPowersResult = mysql_query($sql, $groupDBCon);
            if (!$groupPowersResult) 
            {
                return array('error' => "Could not successfully run query ($sql) from DB: " . mysql_error(), 'params' => var_export($params, TRUE));
            }
            $groupPowersInfo = mysql_fetch_assoc($groupPowersResult);
            $groupResults[$groupID] = array_merge($groupMembershipInfo, $groupPowersInfo);
        }
        
        return $groupResults;
    }
    
    // Parameters should not already be mysql_real_escape_string() escaped
    function canAgentViewRoleMembers( $agentID, $groupID, $roleID )
    {
        global $membersVisibleTo, $groupDBCon;
		
        if( $membersVisibleTo == 'All' ) 
          return true;

        $agentID = mysql_real_escape_string($agentID);
        $groupID = mysql_real_escape_string($groupID);
        $roleID  = mysql_real_escape_string($roleID); 
		
        $sql  = " SELECT CASE WHEN min(OwnerRoleMembership.AgentID) IS NOT NULL THEN 1 ELSE 0 END AS IsOwner ";
        $sql .= " FROM osgroup JOIN osgroupmembership ON (osgroup.GroupID = osgroupmembership.GroupID AND osgroupmembership.AgentID = '$agentID')";
        $sql .= "         LEFT JOIN osgrouprolemembership AS OwnerRoleMembership ON (OwnerRoleMembership.GroupID = osgroup.GroupID ";
        $sql .= "                   AND OwnerRoleMembership.RoleID  = osgroup.OwnerRoleID ";
        $sql .= "                   AND OwnerRoleMembership.AgentID = '$agentID')";
        $sql .= " WHERE osgroup.GroupID = '$groupID' GROUP BY osgroup.GroupID";	
		
        $viewMemberResults = mysql_query($sql, $groupDBCon);
        if (!$viewMemberResults)
        {
            return array('error' => "Could not successfully run query ($sql) from DB: " . mysql_error());
        }
        
        if (mysql_num_rows($viewMemberResults) == 0) 
        {
            return false;
        }
		
        $viewMemberInfo = mysql_fetch_assoc($viewMemberResults);
		
        switch( $membersVisibleTo )
        {
            case 'Group':
                // if we get to here, there is at least one row, so they are a member of the group
                return true;
            case 'Owners':
            default:
                return $viewMemberInfo['IsOwner'];			
        }
    }
	
    function getGroupMembers($params)
    {
        global $groupEnforceGroupPerms, $requestingAgent, $uuidZero, $groupDBCon, $groupPowers;
        $groupID = $params['GroupID'];
        $escapedGroupID = mysql_real_escape_string($groupID);
        
        $sql = " SELECT osgroupmembership.AgentID"
              ." , osgroupmembership.Contribution, osgroupmembership.ListInProfile, osgroupmembership.AcceptNotices"
              ." , osgroupmembership.SelectedRoleID, osrole.Title"
              ." , CASE WHEN OwnerRoleMembership.AgentID IS NOT NULL THEN 1 ELSE 0 END AS IsOwner"
              ." FROM osgroup JOIN osgroupmembership ON (osgroup.GroupID = osgroupmembership.GroupID)"
              ."              JOIN osrole ON (osgroupmembership.SelectedRoleID = osrole.RoleID AND osgroupmembership.GroupID = osrole.GroupID)"
              ."              JOIN osrole AS OwnerRole ON (osgroup.OwnerRoleID  = OwnerRole.RoleID AND osgroup.GroupID  = OwnerRole.GroupID)"
              ."         LEFT JOIN osgrouprolemembership AS OwnerRoleMembership ON (osgroup.OwnerRoleID       = OwnerRoleMembership.RoleID 
                                                                               AND (osgroup.GroupID           = OwnerRoleMembership.GroupID)
                                                                               AND (osgroupmembership.AgentID = OwnerRoleMembership.AgentID))"
              ." WHERE osgroup.GroupID = '$escapedGroupID'";
        
        $groupmemberResults = mysql_query($sql, $groupDBCon);
        if (!$groupmemberResults) 
        {
            return array('error' => "Could not successfully run query ($sql) from DB: " . mysql_error(), 'params' => var_export($params, TRUE));
        }
        
        if (mysql_num_rows($groupmemberResults) == 0) 
        {
            return array('succeed' => 'false', 'error' => 'No Group Members found', 'params' => var_export($params, TRUE), 'sql' => $sql);
        }
        
        $roleMembersVisibleBit = $groupPowers['RoleMembersVisible'];
        $canViewAllGroupRoleMembers = canAgentViewRoleMembers($requestingAgent, $groupID, '');
		
        $memberResults = array();
        while ($memberInfo = mysql_fetch_assoc($groupmemberResults))
        {
            $agentID = $memberInfo['AgentID'];
            $sql = " SELECT BIT_OR(osrole.Powers) AS AgentPowers, ( BIT_OR(osrole.Powers) & $roleMembersVisibleBit) as MemberVisible"
                  ." FROM osgrouprolemembership JOIN osrole ON (osgrouprolemembership.GroupID = osrole.GroupID AND osgrouprolemembership.RoleID = osrole.RoleID)"
                  ." WHERE osgrouprolemembership.GroupID = '$escapedGroupID' AND osgrouprolemembership.AgentID = '$agentID'";
            $memberPowersResult = mysql_query($sql, $groupDBCon);
            if (!$memberPowersResult) 
            {
                return array('error' => "Could not successfully run query ($sql) from DB: " . mysql_error(), 'params' => var_export($params, TRUE));
            }

            $memberPowersCount = mysql_num_rows($memberPowersResult);
            # error_log("Found $memberPowersCount rows for agent $agentID for requesting agent $requestingAgent");

            if ($memberPowersCount == 0) 
            {
                if ($canViewAllGroupRoleMembers || $agentID == $requestingAgent)
                {
                    $memberResults[$agentID] = array_merge($memberInfo, array('AgentPowers' => 0));
                } 
                else 
                {
                    // if can't view all group role members and there is no Member Visible bit, then don't return this member's info
                    unset($memberResults[$agentID]);
                }
            } 
            else 
            {
                $memberPowersInfo = mysql_fetch_assoc($memberPowersResult);
                if ($memberPowersInfo['MemberVisible'] || $canViewAllGroupRoleMembers || $agentID == $requestingAgent)
                {
                    $memberResults[$agentID] = array_merge($memberInfo, $memberPowersInfo);
                } 
                else 
                {
                    // if can't view all group role members and there is no Member Visible bit, then don't return this member's info
                    unset($memberResults[$agentID]);
                }
            }
        }

        # error_log("Returning " . count($memberResults) . " visible members for group $groupID for agent $agentID");
        
        if (count($memberResults) == 0) 
        {
            return array('succeed' => 'false', 'error' => 'No Visible Group Members found', 'params' => var_export($params, TRUE), 'sql' => $sql);
        }
		
        return $memberResults;
    }
    
    function getAgentActiveMembership($params)
    {
        global $groupEnforceGroupPerms, $requestingAgent, $uuidZero, $groupDBCon;
        $agentID = mysql_real_escape_string($params['AgentID']);
        
        $sql = " SELECT osgroup.GroupID, osgroup.Name as GroupName, osgroup.Charter, osgroup.InsigniaID, osgroup.FounderID, osgroup.MembershipFee, osgroup.OpenEnrollment, osgroup.ShowInList, osgroup.AllowPublish, osgroup.MaturePublish"
              ." , osgroupmembership.Contribution, osgroupmembership.ListInProfile, osgroupmembership.AcceptNotices"
              ." , osgroupmembership.SelectedRoleID, osrole.Title"
              ." , osagent.ActiveGroupID "
              ." FROM osagent JOIN osgroup ON (osgroup.GroupID = osagent.ActiveGroupID)"
              ."              JOIN osgroupmembership ON (osgroup.GroupID = osgroupmembership.GroupID AND osagent.AgentID = osgroupmembership.AgentID)"
              ."              JOIN osrole ON (osgroupmembership.SelectedRoleID = osrole.RoleID AND osgroupmembership.GroupID = osrole.GroupID)"
              ." WHERE osagent.AgentID = '$agentID'";
        
        $groupmembershipResult = mysql_query($sql, $groupDBCon);
        if (!$groupmembershipResult) 
        {
            return array('error' => "Could not successfully run query ($sql) from DB: " . mysql_error(), 'params' => var_export($params, TRUE));
        }
        if (mysql_num_rows($groupmembershipResult) == 0) 
        {
            return array('succeed' => 'false', 'error' => 'No Active Group Specified', 'params' => var_export($params, TRUE), 'sql' => $sql);
        }
        $groupMembershipInfo = mysql_fetch_assoc($groupmembershipResult);
        
        $groupID = $groupMembershipInfo['GroupID'];
        $sql = " SELECT BIT_OR(osrole.Powers) AS GroupPowers"
              ." FROM osgrouprolemembership JOIN osrole ON (osgrouprolemembership.GroupID = osrole.GroupID AND osgrouprolemembership.RoleID = osrole.RoleID)"
              ." WHERE osgrouprolemembership.GroupID = '$groupID' AND osgrouprolemembership.AgentID = '$agentID'";
        $groupPowersResult = mysql_query($sql, $groupDBCon);
        if (!$groupPowersResult) 
        {
            return array('error' => "Could not successfully run query ($sql) from DB: " . mysql_error(), 'params' => var_export($params, TRUE));
        }
        $groupPowersInfo = mysql_fetch_assoc($groupPowersResult);
        
        return array_merge($groupMembershipInfo, $groupPowersInfo);
    }
    
    function getAgentRoles($params)
    {
        global $groupEnforceGroupPerms, $requestingAgent, $uuidZero, $groupDBCon;
        $agentID = mysql_real_escape_string($params['AgentID']);
        
        $sql = " SELECT "
              ." osrole.RoleID, osrole.GroupID, osrole.Title, osrole.Name, osrole.Description, osrole.Powers"
              ." , CASE WHEN osgroupmembership.SelectedRoleID = osrole.RoleID THEN 1 ELSE 0 END AS Selected"
              ." FROM osgroupmembership JOIN osgrouprolemembership  ON (osgroupmembership.GroupID = osgrouprolemembership.GroupID AND osgroupmembership.AgentID = osgrouprolemembership.AgentID)"
              ."                        JOIN osrole ON ( osgrouprolemembership.RoleID = osrole.RoleID AND osgrouprolemembership.GroupID = osrole.GroupID)"
              ."                   LEFT JOIN osagent ON (osagent.AgentID = osgroupmembership.AgentID)"
              ." WHERE osgroupmembership.AgentID = '$agentID'";
              
        if( isset($params['GroupID']) )
        {
            $groupID = $params['GroupID'];
            $sql .= " AND osgroupmembership.GroupID = '$groupID'";
        }

        $roleResults = mysql_query($sql, $groupDBCon);
        if (!$roleResults) 
        {
            return array('error' => "Could not successfully run query ($sql) from DB: " . mysql_error(), 'params' => var_export($params, TRUE));
        }

        if( mysql_num_rows($roleResults) == 0 )
        {
            return array('succeed' => 'false', 'error' => 'None found', 'params' => var_export($params, TRUE), 'sql' => $sql);
        }
        
        $roles = array();
        while($role = mysql_fetch_assoc($roleResults))
        {
            $ID = $role['GroupID'].$role['RoleID'];
            $roles[$ID] = $role;
        }
        
        return $roles;
    }
    
    function getGroupRoles($params)
    {
        global $groupEnforceGroupPerms, $requestingAgent, $uuidZero, $groupDBCon;
        $groupID = mysql_real_escape_string($params['GroupID']);
        
        $sql = " SELECT "
              ." osrole.RoleID, osrole.Name, osrole.Title, osrole.Description, osrole.Powers, count(osgrouprolemembership.AgentID) as Members"
              ." FROM osrole LEFT JOIN osgrouprolemembership ON (osrole.GroupID = osgrouprolemembership.GroupID AND osrole.RoleID = osgrouprolemembership.RoleID)"
              ." WHERE osrole.GroupID = '$groupID'"
              ." GROUP BY osrole.RoleID, osrole.Name, osrole.Title, osrole.Description, osrole.Powers";
              
        $roleResults = mysql_query($sql, $groupDBCon);
        if (!$roleResults) 
        {
            return array('error' => "Could not successfully run query ($sql) from DB: " . mysql_error(), 'params' => var_export($params, TRUE));
        }
        
        if( mysql_num_rows($roleResults) == 0 )
        {
            return array('succeed' => 'false', 'error' => 'No roles found for group', 'params' => var_export($params, TRUE), 'sql' => $sql);
        }
        
        $roles = array();
        while($role = mysql_fetch_assoc($roleResults))
        {
            $RoleID = $role['RoleID'];
            $roles[$RoleID] = $role;
        }
        
        return $roles;
    }

    function getGroupRoleMembers($params)
    {
        global $groupEnforceGroupPerms, $requestingAgent, $uuidZero, $groupDBCon, $groupPowers;
        $groupID = $params['GroupID'];        
		
        $roleMembersVisibleBit = $groupPowers['RoleMembersVisible'];
        $canViewAllGroupRoleMembers = canAgentViewRoleMembers($requestingAgent, $groupID, '');
		
        $escapedGroupID = mysql_real_escape_string($groupID);

        $sql = " SELECT "
              ." osrole.RoleID, osgrouprolemembership.AgentID"
	      		  ." , (osrole.Powers & $roleMembersVisibleBit) as MemberVisible"
              ." FROM osrole JOIN osgrouprolemembership ON (osrole.GroupID = osgrouprolemembership.GroupID AND osrole.RoleID = osgrouprolemembership.RoleID)"
              ." WHERE osrole.GroupID = '$escapedGroupID'";
              
        $memberResults = mysql_query($sql, $groupDBCon);
        if (!$memberResults) 
        {
            return array('error' => "Could not successfully run query ($sql) from DB: " . mysql_error(), 'params' => var_export($params, TRUE));
        }
        
        if( mysql_num_rows($memberResults) == 0 )
        {
            return array('succeed' => 'false', 'error' => 'No role memberships found for group', 'params' => var_export($params, TRUE), 'sql' => $sql);
        }		

        $members = array();
        while($member = mysql_fetch_assoc($memberResults))
        {
            if( $canViewAllGroupRoleMembers || $member['MemberVisible'] || ($member['AgentID'] == $requestingAgent) )
            {
                $Key = $member['AgentID'] . $member['RoleID'];
                $members[$Key ] = $member;
            }
        }
		
        if( count($members) == 0 )
        {
            return array('succeed' => 'false', 'error' => 'No role memberships visible for group', 'params' => var_export($params, TRUE), 'sql' => $sql);
        }
        
        return $members;
    }
    
    function setAgentGroupInfo($params)
    {
        global $groupEnforceGroupPerms, $requestingAgent, $uuidZero, $groupDBCon;
		
        if (isset($params['AgentID'])) {
            $agentID = mysql_real_escape_string($params['AgentID']);
        } else {
            $agentID = "";
        }
        if (isset($params['GroupID'])) {
            $groupID = mysql_real_escape_string($params['GroupID']);
        } else {
            $groupID = "";
        }
        if (isset($params['SelectedRoleID'])) {
            $roleID  = mysql_real_escape_string($params['SelectedRoleID']);
        } else {
            $roleID = "";
        }
        if (isset($params['AcceptNotices'])) {
            $acceptNotices = mysql_real_escape_string($params['AcceptNotices']);
        } else {
            $acceptNotices = 1;
        }
        if (isset($params['ListInProfile'])) {
            $listInProfile  = mysql_real_escape_string($params['ListInProfile']);
        } else {
            $listInProfile = 0;
        }

        if( isset($requestingAgent) && ($requestingAgent != $uuidZero) && ($requestingAgent != $agentID) )
        {
            return array('error' => "Agent can only change their own group info", 'params' => var_export($params, TRUE));
        }
    
        $sql = " UPDATE "
              ."     osgroupmembership"
              ." SET "
              ."    AgentID = '$agentID'";

        if( isset($params['SelectedRoleID']) )
        {
            $sql .="    , SelectedRoleID = '$roleID'";
        }
        if( isset($params['AcceptNotices']) )
        {
            $sql .="    , AcceptNotices = $acceptNotices";
        }
        if( isset($params['ListInProfile']) )
        {
            $sql .="    , ListInProfile = $listInProfile";
        }
        
        $sql .=" WHERE osgroupmembership.GroupID = '$groupID' AND osgroupmembership.AgentID = '$agentID'";
              
        $memberResults = mysql_query($sql, $groupDBCon);
        if (!$memberResults) 
        {
            return array('error' => "Could not successfully run query ($sql) from DB: " . mysql_error(), 'params' => var_export($params, TRUE));
        }
        
        return array('success'=> 'true');
    }
    
    function getGroupNotices($params)
    {
        global $groupEnforceGroupPerms, $requestingAgent, $uuidZero, $groupDBCon;
        $groupID = mysql_real_escape_string($params['GroupID']);
        
        $sql = " SELECT "
              ." GroupID, NoticeID, Timestamp, FromName, Subject, Message, BinaryBucket"
              ." FROM osgroupnotice"
              ." WHERE osgroupnotice.GroupID = '$groupID'";
              
        $results = mysql_query($sql, $groupDBCon);
        if (!$results) 
        {
            return array('error' => "Could not successfully run query ($sql) from DB: " . mysql_error(), 'params' => var_export($params, TRUE));
        }
        
        if( mysql_num_rows($results) == 0 )
        {
            return array('succeed' => 'false', 'error' => 'No Notices', 'params' => var_export($params, TRUE), 'sql' => $sql);
        }
        
        $notices = array();
        while($notice = mysql_fetch_assoc($results))
        {
            $NoticeID = $notice['NoticeID'];
            $notices[$NoticeID] = $notice;
        }
        
        return $notices;
    }

    function getGroupNotice($params)
    {
        global $groupEnforceGroupPerms, $requestingAgent, $uuidZero, $groupDBCon;
        $noticeID = mysql_real_escape_string($params['NoticeID']);
        
        $sql = " SELECT "
              ." GroupID, NoticeID, Timestamp, FromName, Subject, Message, BinaryBucket"
              ." FROM osgroupnotice"
              ." WHERE osgroupnotice.NoticeID = '$noticeID'";
              
        $results = mysql_query($sql, $groupDBCon);
        if (!$results) 
        {
            return array('error' => "Could not successfully run query ($sql) from DB: " . mysql_error(), 'params' => var_export($params, TRUE));
        }
        
        if( mysql_num_rows($results) == 0 )
        {
            return array('succeed' => 'false', 'error' => 'Group Notice Not Found', 'params' => var_export($params, TRUE), 'sql' => $sql);
        }
        
        return mysql_fetch_assoc($results);
    }
    
    function addGroupNotice($params)
    {
        global $groupEnforceGroupPerms, $requestingAgent, $uuidZero, $groupDBCon, $groupPowers;
        $groupID        = mysql_real_escape_string($params['GroupID']);
        $noticeID       = mysql_real_escape_string($params['NoticeID']);
        $fromName       = mysql_real_escape_string($params['FromName']);
        $subject        = mysql_real_escape_string($params['Subject']);
        $binaryBucket   = mysql_real_escape_string($params['BinaryBucket']);
        $message        = mysql_real_escape_string($params['Message']);
        $timeStamp      = mysql_real_escape_string($params['TimeStamp']);

        if( is_array($error = checkGroupPermission($groupID, $groupPowers['SendNotices'])) )
        {
            return $error;
        }
        
        $sql = " INSERT INTO osgroupnotice"
              ." (GroupID, NoticeID, Timestamp, FromName, Subject, Message, BinaryBucket)"
              ." VALUES "
              ." ('$groupID', '$noticeID', $timeStamp, '$fromName', '$subject', '$message', '$binaryBucket')";
              
        $results = mysql_query($sql, $groupDBCon);
        if (!$results) 
        {
            return array('error' => "Could not successfully run query ($sql) from DB: " . mysql_error(), 'params' => var_export($params, TRUE));
        }
        
        return array('success' => 'true');
    }
    
    function addAgentToGroupInvite($params)
    {
        global $groupEnforceGroupPerms, $requestingAgent, $uuidZero, $groupDBCon, $groupPowers;

        if( is_array($error = checkGroupPermission($params['GroupID'], $groupPowers['Invite'])) )
        {
            return $error;
        }

        $inviteID   = mysql_real_escape_string($params['InviteID']);
        $groupID    = mysql_real_escape_string($params['GroupID']);
        $roleID     = mysql_real_escape_string($params['RoleID']);
        $agentID    = mysql_real_escape_string($params['AgentID']);
		
        // Remove any existing invites for this agent to this group
        $sql = " DELETE FROM osgroupinvite"
              ." WHERE osgroupinvite.AgentID = '$agentID' AND osgroupinvite.GroupID = '$groupID'";
              
        $results = mysql_query($sql, $groupDBCon);
        if (!$results) 
        {
            return array('error' => "Could not successfully run query ($sql) from DB: " . mysql_error(), 'params' => var_export($params, TRUE));
        }
        
        // Add new invite for this agent to this group for the specifide role
        $sql = " INSERT INTO osgroupinvite"
              ." (InviteID, GroupID, RoleID, AgentID) VALUES ('$inviteID', '$groupID', '$roleID', '$agentID')";
              
        $results = mysql_query($sql, $groupDBCon);
        if (!$results) 
        {
            return array('error' => "Could not successfully run query ($sql) from DB: " . mysql_error(), 'params' => var_export($params, TRUE));
        }
        
        return array('success' => 'true');
    }

    function getAgentToGroupInvite($params)
    {
        global $groupEnforceGroupPerms, $requestingAgent, $uuidZero, $groupDBCon;
        $inviteID = mysql_real_escape_string($params['InviteID']);

        $sql = " SELECT GroupID, RoleID, AgentID FROM osgroupinvite"
              ." WHERE osgroupinvite.InviteID = '$inviteID'";
              
        $results = mysql_query($sql, $groupDBCon);
        if (!$results) 
        {
            return array('error' => "Could not successfully run query ($sql) from DB: " . mysql_error(), 'params' => var_export($params, TRUE));
        }
        
        if( mysql_num_rows($results) == 1 )
        {
            $inviteInfo = mysql_fetch_assoc($results);
            $groupID  = $inviteInfo['GroupID'];
            $roleID   = $inviteInfo['RoleID'];
            $agentID  = $inviteInfo['AgentID'];
        
            return array('success' => 'true', 'GroupID'=>$groupID, 'RoleID'=>$roleID, 'AgentID'=>$agentID);
        } 
        else 
        {
            return array('succeed' => 'false', 'error' => 'Invitation not found', 'params' => var_export($params, TRUE), 'sql' => $sql);
        }
    }
    
    function removeAgentToGroupInvite($params)
    {
        global $groupEnforceGroupPerms, $requestingAgent, $uuidZero, $groupDBCon;
        $inviteID = mysql_real_escape_string($params['InviteID']);
        
        $sql = " DELETE FROM osgroupinvite"
              ." WHERE osgroupinvite.InviteID = '$inviteID'";
              
        $results = mysql_query($sql, $groupDBCon);
        if (!$results) 
        {
            return array('error' => "Could not successfully run query ($sql) from DB: " . mysql_error(), 'params' => var_export($params, TRUE));
        }
        
        return array('success' => 'true');
    }
    

    function checkGroupPermission($GroupID, $Permission)
    {
        global $groupEnforceGroupPerms, $requestingAgent, $uuidZero, $groupDBCon, $groupPowers;
		
        if( !isset($Permission) || ($Permission == 0) )
        {
            return array('error' => 'No Permission value specified for checkGroupPermission'
                       , 'Permission' => $Permission);
        }
		
        // If it isn't set to true, then always return true, otherwise verify they have perms
        if( !isset($groupEnforceGroupPerms) || ($groupEnforceGroupPerms != TRUE) )
        {
            return true;
        }
        
        if( !isset($requestingAgent) || ($requestingAgent == $uuidZero) )
        {
            return array('error' => 'Requesting agent was either not specified or not validated.'
                       , 'requestingAgent' => $requestingAgent);
        }
        
        $params = array('AgentID' => $requestingAgent, 'GroupID' => $GroupID);
        $reqAgentMembership = getAgentGroupMembership($params);

        if( isset($reqAgentMembership['error'] ) )
        {
            return array('error' => 'Could not get agent membership for group'
                       , 'params' => var_export($params, TRUE)
             , 'nestederror' => $reqAgentMembership['error']);
        }

        // Worlds ugliest bitwise operation, EVER
        $PermMask   = $reqAgentMembership['GroupPowers'];
        $PermValue  = $Permission;
        
        global $groupDBCon;
        $sql = " SELECT $PermMask & $PermValue AS Allowed";
        $results = mysql_query($sql, $groupDBCon);
        if (!$results) 
        {
            echo print_r( array('error' => "Could not successfully run query ($sql) from DB: " . mysql_error()));
        }
        $PermMasked = mysql_result($results, 0);
        
        if( $PermMasked != $Permission )
        {
            $permNames = array_flip($groupPowers);

            return array('error' => 'Agent does not have group power to ' . $Permission .'('.$permNames[$Permission].')'
             , 'PermMasked' => $PermMasked
                       , 'params' => var_export($params, TRUE)
             , 'permBitMaskSql' => $sql
             , 'Permission' => $Permission);
        }
        
        /*
        return array('error' => 'Reached end'
               , 'reqAgentMembership' => var_export($reqAgentMembership, TRUE)
               , 'GroupID' => $GroupID
               , 'Permission' => $Permission
               , 'PermMasked' => $PermMasked
               );
        */
        return TRUE;
    }