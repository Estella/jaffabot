<?php

global $privcalls;
$privcalls = array();
global $debug;
$debug = false; if ($debug) {
                   fwrite(STDOUT,"! !WARNING! ! You are running the IRCv3 Client protocol module in DEBUG mode. This means all input and output to the bot is logged to STDOUT.\r\n");
                   fwrite(STDOUT,"! !WARNING! ! THIS INCLUDES BOT PASSWORDS! If you do not want to see the passwords people register with your bot with, please C-c\r\n");
                   fwrite(STDOUT,"! !WARNING! ! and edit ircv3c.php to change \$debug = true; to \$debug = false;.\r\n");
}


function regPrivmsgCallback($object,$func,$client,$cmd){
	global $privcalls;
	// For client PRIVMSG callbacks all dests are the same
	$privcalls[0][$cmd][] = array($object,$func);
}

class protocol{
	function ignore(){
		return;
	}
	function protocol_start($args){
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		if ($opMode == "serv") die("IRCv3C is a CLIENT protocol module, dumbass. Use a server protocol module for server protocols. LOL.");
		regCallback($this,"protocol_privmsg","PRIVMSG");
		regCallback($this,"ignore","372");
		regCallback($this,"ignore","376");
		regCallback($this,"ignore","353");
		regCallback($this,"ignore","366");
		regCallback($this,"irc_join","JOIN");
		regCallback($this,"irc_part","PART");
		regCallback($this,"irc_ping","PING");
		regCallback($this,"irc_WeReConnected","001");
		regCallback($this,"ignore","005");
		regCallback($this,"irc3_cap","CAP");
		$this->sw("CAP REQ :multi-prefix extended-join account-notify away-notify");
		$this->sw("CAP END");
		$this->sw(sprintf("NICK %s",$args[0]));
		$this->sw(sprintf("USER %s * * :%s",($args[2])?$args[2]:"jaffabot",$args[1]));
	}
	function irc_WeReConnected($args){
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		$ohohone = explode(" ",$args["payload"]);
		$nick = explode("!",$ohohone[count($ohohone)-1])[0];
		$performs = parseConf("perform","no");
		foreach ($performs as $performline) {
			$perform = implode("%",array_slice($performline,1));
			$perform = implode($nick,explode("&*&",$perform));
			$this->sw($perform);
		}
		$joins = parseConf("join","no");
		foreach ($joins as $joinline) {
			$this->sw(sprintf("JOIN %s %s",$joinline[1],$joinline[2]));
		}
	}

	function sw($mesg) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		fwrite($socket,$mesg."\r\n");
		if ($debug) fwrite(STDOUT,"Output ".$mesg."\n");
	}

	function irc3_cap($get) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		$payload = explode(" ", $get["payload"]); $state = $get[($get["hassource"])?3:2];
		if ($state == "NAK") { foreach ($payload as $token) {
				$this->caps[$token] = false;
			}
		} else if ($state == "ACK") { foreach ($payload as $token) {
				$this->caps[$token] = true;
			}
		}
	}

	function sendUid($sender){
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		$this->send_notice(NULL,$sender,"\x02Your command could not be completed.\x02 Cannot use N message in client mode; ignoring");
		return false;
	}

	function parse($get){
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		if ($debug) fwrite(STDOUT,"Input  ".$get."\n");
		if ($get[0] == ":") $hasSrc = true;
		else $hasSrc = false;
		$msg = ($hasSrc) ? array_slice(explode(":",$get,3),1) : explode(":",$get,2);
		$comd = $msg[0];
		$payload = $msg[1];
		$cmd = explode(" ", $comd);
		$cmd["sendernick"] = ($hasSrc) ? explode("!",$cmd[0])[0] : false; // Useful! :P That is, if the sender is not a server. :P
		$cmd["hassource"] = ($hasSrc) ? true : false;
		$cmd["cmd"] = ($hasSrc) ? $cmd[1] : $cmd[0];
		if ($payload != "") $cmd["payload"] = $payload;
		return $cmd;
	}

	function protocol_privmsg($get){
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		// This parses PRIVMSGs. This should really be in the core... ugh
		$cmd = explode(" ",$get["payload"],2);
		foreach ($privcalls[0][$cmd[0]] as $callback) {
			call_user_func($callback,$get["sendernick"],$get[($cmd["hassource"])?2:1],$cmd[1]);
		}
	}

	function irc_ping($get){
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		$this->sw(sprintf("PONG :%s",$get["payload"]));
	}

	function send_msg($type,$from,$to,$message) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		// $from is ignored by client protocols
		if ($type[0] == "N") $this->sw(sprintf("NOTICE %s :%s",$to,$message));
		if ($type[0] == "n") $this->sw(sprintf("NOTICE %s :%s",$to,$message));
		if ($type[0] == "P") $this->sw(sprintf("PRIVMSG %s :%s",$to,$message));
		if ($type[0] == "p") $this->sw(sprintf("PRIVMSG %s :%s",$to,$message));
	}

	function irc_join($args) {
		$this->ignore();
	}
	function irc_part($args) {
		$this->ignore();
	}

	function send_notice($from,$to,$message) { global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug; $this->send_msg("Notice",$from,$to,$message); }
	function send_privmsg($from,$to,$message) { global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug; $this->send_msg("PRIVMSG",$from,$to,$message); }
}
