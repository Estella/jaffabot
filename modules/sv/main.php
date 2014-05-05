<?php

class sv_main {

	function __construct($duck2fuck,$args) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		$this->admin = $args[0];
		$protofunc->sendUid(0,$args[1],$args[2],$args[3],$args[3],$args[4],$args[5]);
	}

}
