<?php

//include the com class
require_once('com.class.php');

//disable or enable debugging
define('DEV', true);

if (DEV)
    error_reporting(E_ALL);

//setup the name of the space and a first branch type
define('COM_SPACE', 'com');
define('COM_SPACE_TYPE', COM_SPACE . '.type');

//register the types of data types
foreach (Array('STRING', 'OBJECT', 'INT', 'OBJECT_SET', 'STRING_SET') as $str_type)
    define('COM_' . strtoupper($str_type), COM_SPACE_TYPE . '.' . strtoupper($str_type));

//register some events
define('COM_SYSTEM_BOOT', COM_SPACE . '.system.BOOT');
define('COM_FIND_USERS', COM_SPACE . '.users.FIND');

//EXAMPLE 1
com::dev('---EXAMPLE 1---');

//before you should run the includes of addons etc.
require_once('example1.class.php');

//index the manifests
com::find_manifests(Array(true));


//start the event that we are booting
com(COM_SYSTEM_BOOT);

//EXAMPLE 2
com::dev('---EXAMPLE 2---');

$con = new con(COM_FIND_USERS);
$con->data(COM_STRING, 'com.type.user.mail', 'testman@test.de');
$con->data(COM_STRING, 'com.type.user.pwd', 'password');
$con->ask(COM_INT, 'com.type.user.id');

com::dev('Results returned: ' . print_r(com($con), true));
com::dev('Results in query object:' . print_r($con->answer, true));
?>