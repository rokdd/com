<?php

//we need the simpledom library
require_once ('SimpleDOM.php');

//com does send a request
function com($trigger) {
    //create  a object of the con if it does not exists
    if (!is_object($trigger))
	$trigger = new con($trigger);

//insert temporary our request
    com::$manifests->manifests->insertXML('<request>' . $trigger->xml() . '</request>');
    $activitys = com::$manifests->SortedXpath('//activity[not(require/*[not(text()=//request/*/text())])]', '@priority', SORT_ASC);

    //com::dev(htmlspecialchars(com::$manifests->manifests->asXML()));
    com::$manifests->manifests->request->removeSelf();

    if ($activitys == false || count($activitys) == 0)
	return false;
    $arr_param = Array($trigger);
    $ret = Array();
    foreach ($activitys as $int_activity => $activity) {
	$arr_param_specific = $arr_param;
	com::dev('Start activity #' . $activity->attributes()->id);
	if (isset($activity->function))
	    if (isset($activity->function->object)) {
		$ret[(String) $activity->attributes()->id . '_' . $int_activity] = call_user_func_array(Array(call_user_func(array((string) $activity->function->object->attributes()->class, 'getInstance')), (string) $activity->function->attributes()->name), $arr_param_specific);
	    }
	if (isset($activity->function->attributes()->name) && function_exists($activity->function->attributes()->name)) {
	    //check for given params
	    foreach ($activity->function->xpath('//parameter') as $obj_parameter)
		$arr_param_specific[] = (string) $obj_parameter;
	    $ret[(String) $activity->attributes()->id . '_' . $int_activity] = call_user_func_array((string) $activity->function->attributes()->name, $arr_param_specific);
	}
    }
    return $ret;
}

/* HELPER FUNCTIONS */

//just create a class for communication hub
class com {

    //property manifest: cache all manifest which we will get or find
    static $manifests = null;
    //might be for addon structure to use a default name for registering the hook
    private static $manifest_file_default_name = 'com.manifest.xml';
    //property name of the object to search for
    private static $manifest_object_default_name = 'com_manifest';
    //arrays to store the instances and triggers
    private static $instances = array();
    private static $triggers = array();

    //just weak but needful to determine the called class
    private static function get_called_class() {
	$t = debug_backtrace();
	for ($x = count($t) - 1; $x >= 0; $x--) {
	    if (isset($t[$x]["class"]) && $t[$x]["function"] == 'getInstance' && $t[$x]["type"] == '::')
		return $t[$x]["class"];
	}
    }

    public static function coi($str_ident) {
	$ret = Array();
	if (isset(self::$triggers[$str_ident]))
	    foreach (self::$triggers[$str_ident] as $ident_callback => $callback) {
		$arr_param = func_get_args();
		$arr_param[0] = $ident_callback;
		if (is_array($callback['p']))
		    $arr_param = array_merge($arr_param, $callback['p']);
		$ret[$ident_callback] = call_user_func_array($callback['cb'], $arr_param);
	    }
	return $ret;
    }

    public static function setCoi($str_ident, $callback, $value = Array()) {
	if (!isset(self::$triggers[$str_ident]))
	    self::$triggers[$str_ident] = Array(Array('cb' => $callback, 'p' => $value));
	elseif ($value != null)
	    self::$triggers[$str_ident][] = Array('cb' => $callback, 'p' => $value);
	else
	    unset(self::$triggers[$str_ident][$callback]);
    }

    //just for Singleton
    public static function getInstance() {
	$class = self::get_called_class();

	if (strlen($class) == 0 || !class_exists($class))
	    DEV ? self::dev(print_r(debug_backtrace(), true)) : null;

	if (!isset(self::$instances[$class]))
	    self::$instances[$class] = new $class;

	return self::$instances[$class];
    }

    //first run to etablish the list
    function init_manifests() {
	if (!is_object(self::$manifests)) {
	    self::$manifests = new SimpleDOM('<com><manifests/></com>');
	}
    }

    //just for debugging
    public static function dev($txt) {
	if (!DEV)
	    return false;

	print('<pre>' . date('d.m.Y', time()) . "\t" . date('h:i:s', time()) . "\t" . $txt . '' . "</pre>");
    }

//function remove and add and update manifests
    function manifest($manifests, $value = null) {
	self::init_manifests();
	if (is_array($manifests))
	    foreach ($manifests as $manifest)
		self::manifest($manifest);
	else {
	    //theoretically we should check what we want to do with the manifest and setup the format and whether is valid
	    //make a list of existing ids
	    $str_id = '|';
	    /* XPATH 2.0 make this easier, but for compatibility not used */
	    $existing_obj_id = self::$manifests->manifests->xpath('//activity/@id');
	    if (is_array($existing_obj_id))
		foreach ($existing_obj_id as $obj_id)
		    $str_id.=(string) $obj_id . '|';

	    $xml_manifests = simpledom_load_string($manifests);
	    if ($xml_manifests === false && !is_object($xml_manifests)) {
		//that should be never true
		print_r($manifests);
		exit;
	    }
	    foreach ($xml_manifests->xpath('//activity[string(@id)!="" and not(contains("' . $str_id . '",@id))]') as $activity)
		self::$manifests->manifests->appendChild($activity);
	}
    }

    function debug_manifests() {
	echo htmlspecialchars(self::$manifests->asXML());
    }

    //search for manifests by the given settings
    function find_manifests($source = null, $options = null) {
	//if it is a custom function so execute
	if (is_callable($source))
	    call_user_func_array($source, Array());
	elseif (is_array($source))
	    foreach ($source as $obj_source)
		com::find_manifests($obj_source);
	elseif (is_string($source) && file_exists($source)) {
	    //recursive depth is default 1 or setup options with value if you want more
	    $options = ((!isset($options) || (!is_numeric($options))) ? (1) : ($options));
	    $dir = opendir($source);
	    //open the dir and check for files with the default name of static property OR go one folder deeper if allowed by options
	    while ($filename = readdir($dir))
		if ($filename == self::$manifest_file_default_name)
		    self::manifest(rtrim($source, '/') . '/' . $filename);
		elseif (is_dir($filename) && $options > 0)
		    self::find_manifests(rtrim($source, '/') . '/' . $filename, ($options - 1));
	}
	elseif ($source == true) {
	    //crawl for objects with manifest propertys
	    $arr_classes = get_declared_classes();
	    foreach ($arr_classes as $str_class) {
		$arr_propertys = get_class_vars($str_class);
		if (isset($arr_propertys['com_manifest']))
		    self::manifest($arr_propertys['com_manifest']);
	    }
	}
	else
	    return false;
    }

}

//and a container class for sharing the data
class con {

    var $trigger = "";
    var $data = Array(COM_STRING => Array(), COM_OBJECT => Array(), COM_INT => Array(), COM_OBJECT_SET => Array());
    var $ask = Array(COM_STRING => Array(), COM_OBJECT => Array(), COM_INT => Array(), COM_OBJECT_SET => Array());
    var $answer = Array(COM_STRING => Array(), COM_OBJECT => Array(), COM_INT => Array(), COM_OBJECT_SET => Array());
    var $sense = Array('ask', 'data', 'answer');

    function __construct($trigger = "") {
	$this->trigger = $trigger;
    }

    function __call($str_name, $arr_arguments) {
	if ($str_name == 'trigger' && isset($arr_arguments[0]) && is_string($arr_arguments[0]))
	    $this->trigger = $arr_arguments[0];
	if (in_array($str_name, $this->sense) && isset($arr_arguments[2])) {
	    $this->{$str_name}[$arr_arguments[0]][$arr_arguments[1]] = $arr_arguments[2];
	} elseif (in_array($str_name, $this->sense) && !isset($arr_arguments[1])) {
	    return $this->{$str_name}[$arr_arguments[0]];
	} elseif (in_array($str_name, $this->sense) && !isset($arr_arguments[2])) {
	    if (isset($this->{$str_name}[$arr_arguments[0]][$arr_arguments[1]]))
		return $this->{$str_name}[$arr_arguments[0]][$arr_arguments[1]];
	    else {
		$this->{$str_name}[$arr_arguments[0]][$arr_arguments[1]] = null;
		return $this->{$str_name}[$arr_arguments[0]][$arr_arguments[1]];
	    }
	}

	return $this->{$str_name};
    }

    function xml() {
	$str = '<trigger>' . $this->trigger . '</trigger>';
	foreach ($this->ask as $str_type => $arr_questions)
	    foreach ($arr_questions as $str_data_type => $value)
		$str.='<ask>' . $str_data_type . '</ask>';

	return $str;
    }

}

//singleton class
class com_Singleton extends com {

    public static function getInstance() {

	return parent::getInstance();
    }

    protected function __clone() {

    }

}

?>