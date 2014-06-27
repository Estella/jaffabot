If your config file doesn't have a correctly formed m: (client)
or M: (server) line, this program will fuck itself over.

Only the first modproto% module will be included.

<?php
function __autoload($c) {
	$c = strtr($c,"_","/");
	require_once("./modules/".$c.".php");
}

include_once("./core.php");

error_reporting(0);
global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket;

$mods["%args%"] = $argv;

function isPrivate($dest) {
	return (($dest[0] == "#") or ($dest[0] == "+"))?false:true;
}

function parseConf($linename,$rehash) {
global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket;
	// Returns an array containing at the first dimension
	// all lines with the $linename in the config
	// and at the second dimension the items on that line.
	// For .rehash requires to be done for every module
	if ($linename == "") return false;
	if (strcasecmp($rehash,"yes"))
		$file = file_get_contents("./".$mods["%args%"][1]);
	$filelines = explode("\n",$file);
	$lines = array();
	foreach ($filelines as $line) {
		$linearr = explode("%",$line);
		if ($linearr[0] == $linename)
			$lines[] = $linearr;
	}
	return $lines;
}

function Rehash() {
global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket;
	$file = file_get_contents("./".$mods["%args%"][1]);
}

function regCallback($object, $func, $protocolWord){
global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket;
	$callbacks[$protocolWord][] = array($object, $func);
}

function regEvent($object, $func, $protocolWord){
global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket;
	$mods["%event%"][$protocolWord][] = array($object, $func);
}

function regLEvent($func, $protocolWord){
global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket;
	$mods["%event%"][$protocolWord][] = $func;
}

function callCallbacks($get){
global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket;
	// Format for a parsed line is:
	/*
	 * $get[0] = Command (or any chosen special word, just has to be standard :P)
	 * $get[1] = Source (or local server if no source)
	 * $get[2] and so on = Rest of arguments
	 */
	if ($callbacks[$get["cmd"]])
		foreach ($callbacks[$get["cmd"]] as $callback) call_user_func($callback,array_slice($get,1));
	if ($callbacks[$get["cmd"]]) return;
	if ($callbacks[$get[0]])
		foreach ($callbacks[$get[0]] as $callback) call_user_func($callback,array_slice($get,1));
}

function callEvents($get){
global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket;
	// Format for a parsed line is:
	/*
	 * $get[0] = Command (or any chosen special word, just has to be standard :P)
	 * $get[1] = Source (or local server if no source)
	 * $get[2] and so on = Rest of arguments
	 */
	if ($mods["%event%"][$get["cmd"]])
		foreach ($mods["%event%"][$get["cmd"]] as $callback) call_user_func($callback,$get);
}

Rehash();
$mods["%select%"] = new SockSelect();
$modeline = parseConf("mode","no");
$opMode = "client";
$protomod = parseConf("modproto","no");
$connect = parseConf("server","no");
$modules = parseConf("loadmod","no");
if ($modeline[0][1] == "S") $opMode = "serv";
if ($opMode == "serv") {
	$Mline = parseConf("M","no");
} else {
	$Mline = parseConf("m","no");
}

require_once($protomod[0][1]);
sleep(1);
$prot = str_replace(".php","",$protomod[0][1]);
// Protocol modules are now floating, i.e. a module can load a protocol module
// that is different to the main one (altho how $socket will work i don't know)

$socket = $mods["%select%"]->connect($connect[0][1],array(
	"ssl" => array(
		"verify_peer" => false,
		"verify_peer_name" => false
	)
),"callSock");

$protofunc = new $prot($socket);

$protofunc->protocol_start(array_slice($Mline[0],1)); // Protocols should follow the standard M:line setup

function callSock($fd,$get) {
	global $protofunc;
        $got = $protofunc->parse(trim($get));
        callCallbacks($got); 
}

foreach ($modules as $mod) {
global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket;
	// Modules take their options as an array.
	if ($mod[1] == "_") break;
	if ($mod[1] == "") break;
	if ($mod[1] == "/") break;
	$mods[$mod[1]] = new $mod[1]($mod[1],array_slice($mod,2));
}

/*
 * We've decided that protocol modules will have to register a callback
 * for the main socket; however, the core will do that for them.
 * So they don't need to. -- j4jackj
 */

//$callbacks["%input%"][(int)$socket] = callCallbacks($got);

while (true) {
global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket;
	/* if (feof($socket)) die("Error  Socket fucked a duck");
	$get = fgets($socket,514);
	$got = $protofunc->parse(trim($get));
	callCallbacks($got); */
	$mods["%select%"]->loop();
}
