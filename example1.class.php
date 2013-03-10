<?php

define('COM_USER_MAIL', COM_SPACE_TYPE . '.user.mail');
define('COM_USER_PWD', COM_SPACE_TYPE . '.user.pwd');
define('COM_USER_ID', COM_SPACE_TYPE . '.user.id');

class addon_browser extends com_Singleton {

    var $com_manifest = '<bundle>
	<activity priority="121" id="addon_browser_load">
	<!-- the id is only to allowed once.. -->
	    <require>
	    <!-- require the following trigger, can be also more options -->
		<trigger>com.system.BOOT</trigger>
	    </require>
	    <!--use the function construct of the class addon_browser. If not available it will create a instance of this class-->
	    <function name="construct">
		<object class="addon_browser"/>
	    </function>
	</activity>
	<!-- you might use more triggers for this class-->
	<activity priority="121" id="addon_browser_find_user">
	<!-- the id is only to allowed once.. -->
	    <require>
		<trigger>com.users.FIND</trigger>
		<ask>com.type.user.id</ask>
	    </require>
	    <function name="find_users">
		<object class="addon_browser"/>
	    </function>
	</activity>
	</bundle>';

    function __construct() {
	//this should not be used because of the singleton technoglogy!
	return null;
    }

    //thats for singleton technology
    public static function getInstance() {
	return parent::getInstance();
    }

    function construct() {
	//use a custom function which is called if needed..
	//parent::__construct();
	//do something to construct
	com::dev("Addon browser is now loading..");
    }

    function find_users($context) {

	$mail = $context->data(COM_STRING, COM_USER_MAIL);
	$pwd = $context->data(COM_STRING, COM_USER_PWD);

	com::dev('Find user ' . $mail . ' with password ' . $pwd . '..');
	//just lookup or do anything else
	if (stristr($mail, '@test.de')) {
	    $context->answer(COM_INT, COM_USER_ID, '1234');
	    return '1234';
	}
    }

}

?>
