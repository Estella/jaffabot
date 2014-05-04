<?php

class andromeda {
	function __construct($myname,$arr){
		regPrivmsgCallback($this,"cmd_version","OS","!version");
	}
	function cmd_version($who,$dest,$args) {
		global $protofunc;
		if ($dest[0] == "#" || $dest[0] == "&") $to = $dest;
		else $to = $who;
		$args = explode("\n",$args);
		$args = implode('\n',$args);
		$args = explode("\r",$args);
		$args = implode('\r',$args);
		$protofunc->send_privmsg("OS",$to,sprintf("I am an example command module. I was called with the arguments %s",$args));
		$protofunc->send_privmsg("OS",$to,"I am a Jaffabot IRC bot. My design was inspired by Andromeda's phpbot but is much bulkier.");
		$protofunc->send_privmsg("OS",$to,"Modules are expected to implement their own flood control; this bot is designed to work as a servicesbot too.");
	}
}
