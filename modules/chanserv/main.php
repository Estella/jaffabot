<?php

class chanserv_main{
	function __construct($duck2fuck,$args) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		$protofunc->sendUid(0,$args[0],$args[1],$args[2],$args[2],$args[3],$args[4]);
	}
}
