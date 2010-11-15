Offline Message module
''''''''''''''''''''''

Created by DEVI (dev José Vera - Jor3l)
devi S.A.S - Bogotá, Colombia
jor3l@foravatars.com

---------------------------------------
ABOUT:
	This package enables Offline messages in OpenSim, unlike others, this module
	creates the database needed to work, you do not have to import .sql files or
	mess with mysql databases.
	
INSTALL:
	- Unrar this package
	- Place the Offline.php and database.php in a directory you want.
	- Edit the database.php with your MySQL details:
		- Change the DATABASE, USERNAME and PASSWORD
		* localhost is optional, change if you're running OpenSim in other machine.
	- Open your OpenSim.ini file and find 'OfflineMessageModule' and remove the ;
		- Also remove the ; for:	- OfflineMessageURL
									- MuteListModule
									- MuteListURL
	- Change the OfflineMessageURL to the path of your install
		- e.g: http://myweb.com/offline_messages/Offline.php
		- e.g: http://localhost/offline_messages/Offline.php
	- Ignore the MuteList*
	- Start your OpenSim.exe and enjoy!
	
NOTES:
	If you have Wiredux offline module running, you can set this module to use the same database,
	modify the 'Offline.php' file and change the [ $OMTN = 'OfflineMessages'; ] to [ $OMTN = 'wi_offline_msgs'; ]
