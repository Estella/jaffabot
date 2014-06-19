<?php

class andromeda {
	function __construct($myname,$arr){
		regPrivmsgCallback($this,"cmd_version",0,"!version");
		regPrivmsgCallback($this,"ctcp_version",0,"\x01version\x01");
		regPrivmsgCallback($this,"ctcp_version",0,"\x01version");
	}
	function cmd_version($who,$dest,$args) {
		global $protofunc;
		if ($dest[0] == "#" || $dest[0] == "&") $to = $dest;
		else $to = $who;
		echo $dest.PHP_EOL;
		echo $to.PHP_EOL;
		$args = explode("\n",$args);
		$args = implode('\n',$args);
		$args = explode("\r",$args);
		$args = implode('\r',$args);
		$protofunc->send_privmsg(0,$to,sprintf("I am an example command module. I was called with the arguments %s",$args));
		$protofunc->send_privmsg(0,$to,"I am a Jaffabot IRC bot. My design was inspired by Andromeda's phpbot but is much bulkier.");
		$protofunc->send_privmsg(0,$to,"Modules are expected to implement their own flood control; this bot is designed to work as a servicesbot too.");
	}
	function ctcp_version($who,$dest,$args) {
		global $protofunc;
		$protofunc->send_notice(0,$who,"\x01VERSION andromeda.php for Jaffabot 0.99.7 Vancouver by Jack D. Johnson\x01");
	}
}
