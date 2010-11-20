<?php
	/* SECURITY KEYS */
	
	$securityReadKey    = ''; //'1234';
	$securityWriteKey   = ''; //'1234';
	
	/* DATA BASE INFO */
	
	$databaseName     = 'DATABASENAME';
	$databaseUserName = 'USERNAME';
	$databasePassword = 'PASSWORD';
	$databaseHost     = 'localhost'; /* Optional */
	
	/* DATA EXPORT */
	
	$exportFormat     = 'xml'; // xml/json
	
	/* DEBUG */
	
	$debugLog         = true;
	$debugFile        = 'xmlrpc.log'; /* Create and/or give write permissions */
	
	/* FloatSam Config */
	
	$groupPowers = array(
        'None' => '0',
        /// <summary>Can send invitations to groups default role</summary>
        'Invite' => '2',
        /// <summary>Can eject members from group</summary>
        'Eject' => '4',
        /// <summary>Can toggle 'Open Enrollment' and change 'Signup fee'</summary>
        'ChangeOptions' => '8',
        /// <summary>Can create new roles</summary>
        'CreateRole' => '16',
        /// <summary>Can delete existing roles</summary>
        'DeleteRole' => '32',
        /// <summary>Can change Role names, titles and descriptions</summary>
        'RoleProperties' => '64',
        /// <summary>Can assign other members to assigners role</summary>
        'AssignMemberLimited' => '128',
        /// <summary>Can assign other members to any role</summary>
        'AssignMember' => '256',
        /// <summary>Can remove members from roles</summary>
        'RemoveMember' => '512',
        /// <summary>Can assign and remove abilities in roles</summary>
        'ChangeActions' => '1024',
        /// <summary>Can change group Charter, Insignia, 'Publish on the web' and which
        /// members are publicly visible in group member listings</summary>
        'ChangeIdentity' => '2048',
        /// <summary>Can buy land or deed land to group</summary>
        'LandDeed' => '4096',
        /// <summary>Can abandon group owned land to Governor Linden on mainland, or Estate owner for
        /// private estates</summary>
        'LandRelease' => '8192',
        /// <summary>Can set land for-sale information on group owned parcels</summary>
        'LandSetSale' => '16384',
        /// <summary>Can subdivide and join parcels</summary>
        'LandDivideJoin' => '32768',
        /// <summary>Can join group chat sessions</summary>
        'JoinChat' => '65536',
        /// <summary>Can toggle "Show in Find Places" and set search category</summary>
        'FindPlaces' => '131072',
        /// <summary>Can change parcel name, description, and 'Publish on web' settings</summary>
        'LandChangeIdentity' => '262144',
        /// <summary>Can set the landing point and teleport routing on group land</summary>
        'SetLandingPoint' => '524288',
        /// <summary>Can change music and media settings</summary>
        'ChangeMedia' => '1048576',
        /// <summary>Can toggle 'Edit Terrain' option in Land settings</summary>
        'LandEdit' => '2097152',
        /// <summary>Can toggle various About Land > Options settings</summary>
        'LandOptions' => '4194304',
        /// <summary>Can always terraform land, even if parcel settings have it turned off</summary>
        'AllowEditLand' => '8388608',
        /// <summary>Can always fly while over group owned land</summary>
        'AllowFly' => '16777216',
        /// <summary>Can always rez objects on group owned land</summary>
        'AllowRez' => '33554432',
        /// <summary>Can always create landmarks for group owned parcels</summary>
        'AllowLandmark' => '67108864',
        /// <summary>Can use voice chat in Group Chat sessions</summary>
        'AllowVoiceChat' => '134217728',
        /// <summary>Can set home location on any group owned parcel</summary>
        'AllowSetHome' => '268435456',
        /// <summary>Can modify public access settings for group owned parcels</summary>
        'LandManageAllowed' => '536870912',
        /// <summary>Can manager parcel ban lists on group owned land</summary>
        'LandManageBanned' => '1073741824',
        /// <summary>Can manage pass list sales information</summary>
        'LandManagePasses' => '2147483648',
        /// <summary>Can eject and freeze other avatars on group owned land</summary>
        'LandEjectAndFreeze' => '4294967296',
        /// <summary>Can return objects set to group</summary>
        'ReturnGroupSet' => '8589934592',
        /// <summary>Can return non-group owned/set objects</summary>
        'ReturnNonGroup' => '17179869184',
        /// <summary>Can landscape using Linden plants</summary>
        'LandGardening' => '34359738368',
        /// <summary>Can deed objects to group</summary>
        'DeedObject' => '68719476736',
        /// <summary>Can moderate group chat sessions</summary>
        'ModerateChat' => '137438953472',
        /// <summary>Can move group owned objects</summary>
        'ObjectManipulate' => '274877906944',
        /// <summary>Can set group owned objects for-sale</summary>
        'ObjectSetForSale' => '549755813888',
        /// <summary>Pay group liabilities and receive group dividends</summary>
        'Accountable' => '1099511627776',
        /// <summary>Can send group notices</summary>
        'SendNotices'    => '4398046511104',
        /// <summary>Can receive group notices</summary>
        'ReceiveNotices' => '8796093022208',
        /// <summary>Can create group proposals</summary>
        'StartProposal' => '17592186044416',
        /// <summary>Can vote on group proposals</summary>
        'VoteOnProposal' => '35184372088832',
        /// <summary>Can return group owned objects</summary>
        'ReturnGroupOwned' => '281474976710656',
        /// <summary>Members are visible to non-owners</summary>
		    'RoleMembersVisible' => '140737488355328'
		);
	
    $uuidZero = "00000000-0000-0000-0000-000000000000";