<?php

class spamspamspam {
	function __construct($myname,$arr){
		regPrivmsgCallback($this,"cmd_version",0,"!spam");
	}
	function cmd_version($who,$dest,$args) {
		global $protofunc;
		if ($dest[0] == "#" || $dest[0] == "&") $to = $dest;
		else $to = $who;
		$args = explode("\n",$args);
		$args = implode('\n',$args);
		$args = explode("\r",$args);
		$args = implode('\r',$args);
		$protofunc->send_privmsg(0,$to,"{$who}, I am a Jaffabot <https://github.com/asterIRC/jaffabot>");
	}
}
