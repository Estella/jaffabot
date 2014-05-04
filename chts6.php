<?php

global $privcalls;
$privcalls = array();
global $debug;
$debug = true; if ($debug) {
                   fwrite(STDOUT,"! !WARNING! ! You are running the IRCv3 Client protocol module in DEBUG mode. This means all input and output to the bot is logged to STDOUT.\r\n");
                   fwrite(STDOUT,"! !WARNING! ! THIS INCLUDES BOT PASSWORDS! If you do not want to see the passwords people register with your bot with, please C-c\r\n");
                   fwrite(STDOUT,"! !WARNING! ! and edit ircv3c.php to change \$debug = true; to \$debug = false;.\r\n");
}


function regPrivmsgCallback($object,$func,$client,$cmd){
	global $privcalls;
	// For client PRIVMSG callbacks all dests are the same
	$privcalls[$client][$cmd][] = array($object,$func);
}

class protocol{
	function ignore(){
		return;
	}
	function protocol_start($args){
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		$confItems["SID"] = $args[4];
		if ($opMode != "serv") die("Ehm... aren't you supposed to be using ircv3c.php?");
		regCallback($this,"protocol_privmsg","PRIVMSG");
		regCallback($this,"irc_join","JOIN");
		regCallback($this,"irc_pass","PASS");
		regCallback($this,"irc_sjoin","SJOIN");
		regCallback($this,"irc_part","PART");
		regCallback($this,"irc_nick","NICK");
		regCallback($this,"irc_logon","EUID");
		regCallback($this,"irc_server","SID");
		regCallback($this,"irc_ping","PING");
		regCallback($this,"irc_ping","PING");
		regCallback($this,"irc_capab","CAPAB");
		regCallback($this,"irc_svinfo","SVINFO");
		$this->sw(sprintf("PASS %s TS 6 :%s",$args[1],$args[4]));
		$this->sw(sprintf("SERVER %s 1 :%s",$args[0],$args[2]));
	}

	function enc($id) {
    $fromBaseInput="0123456789";
    $toBaseInput="ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    if ($fromBaseInput==$toBaseInput) return $numberInput;
    $fromBase = str_split($fromBaseInput,1);
    $toBase = str_split($toBaseInput,1);
    $number = str_split($numberInput,1);
    $fromLen=strlen($fromBaseInput);
    $toLen=strlen($toBaseInput);
    $numberLen=strlen($numberInput);
    $retval='';
    if ($toBaseInput == '0123456789')
    {
        $retval=0;
        for ($i = 1;$i <= $numberLen; $i++)
            $retval = bcadd($retval, bcmul(array_search($number[$i-1], $fromBase),bcpow($fromLen,$numberLen-$i)));
        return $retval;
    }
    if ($fromBaseInput != '0123456789')
        $base10=$this->convBase($numberInput, $fromBaseInput, '0123456789');
    else
        $base10 = $numberInput;
    if ($base10<strlen($toBaseInput))
        return $toBase[$base10];
    while($base10 != '0')
    {
        $retval = $toBase[bcmod($base10,$toLen)].$retval;
        $base10 = bcdiv($base10,$toLen,0);
    }
    return $retval;

	}

	function dec($id) {
    $fromBaseInput="ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $toBaseInput="0123456789";
    if ($fromBaseInput==$toBaseInput) return $numberInput;
    $fromBase = str_split($fromBaseInput,1);
    $toBase = str_split($toBaseInput,1);
    $number = str_split($numberInput,1);
    $fromLen=strlen($fromBaseInput);
    $toLen=strlen($toBaseInput);
    $numberLen=strlen($numberInput);
    $retval='';
    if ($toBaseInput == '0123456789')
    {
        $retval=0;
        for ($i = 1;$i <= $numberLen; $i++)
            $retval = bcadd($retval, bcmul(array_search($number[$i-1], $fromBase),bcpow($fromLen,$numberLen-$i)));
        return $retval;
    }
    if ($fromBaseInput != '0123456789')
        $base10=$this->convBase($numberInput, $fromBaseInput, '0123456789');
    else
        $base10 = $numberInput;
    if ($base10<strlen($toBaseInput))
        return $toBase[$base10];
    while($base10 != '0')
    {
        $retval = $toBase[bcmod($base10,$toLen)].$retval;
        $base10 = bcdiv($base10,$toLen,0);
    }
    return $retval;

	}

	function sw($mesg) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		fwrite($socket,$mesg."\r\n");
		if ($debug) fwrite(STDOUT,"Output ".$mesg."\n");
	}

	function irc_capab($get) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		$payload = array_slice($get,1);
		foreach ($payload as $token) {
				$this->caps[$token] = true;
			}
		}
	}

	function sendUid($sender,$nick,$ident,$host,$dhost,$uid,$realname="* Unknown *"){
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		$this->sw(sprintf(":%s EUID %s 1 %s +ioS %s %s 0 %s%s%s %s * :%s",$confItems["SID"], $nick, time(), $ident, $dhost, $confItems["SID"], str_repeat("A", (6-strlen($this->enc($uid)))), $this->enc($uid), $host, $realname));
		return true; //user introduced
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
		$cmd["sendernick"] = ($hasSrc) ? $this->sidnicks[$cmd[0]] : $confItems["UplinkSID"]; // Useful! :P That is, if the sender is not a server. :P
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
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
	}
	function irc_part($args) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
	}

	function send_notice($from,$to,$message) { global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug; $this->send_msg("Notice",$from,$to,$message); }
	function send_privmsg($from,$to,$message) { global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug; $this->send_msg("PRIVMSG",$from,$to,$message); }
}
