<?php

global $privcalls;
$privcalls = array();
global $debug;
$debug = false; if ($debug) {
                   fwrite(STDOUT,"! !WARNING! ! You are running the TS6 server protocol module in DEBUG mode. This means all input and output to the bot is logged to STDOUT.\r\n");
                   fwrite(STDOUT,"! !WARNING! ! THIS INCLUDES BOT PASSWORDS! If you do not want to see the passwords people register with your bot with, please C-c\r\n");
                   fwrite(STDOUT,"! !WARNING! ! and edit chts6.php to change \$debug = true; to \$debug = false;.\r\n");
}


function regPrivmsgCallback($object,$func,$client,$cmd){
	global $privcalls;
	// For client PRIVMSG callbacks all dests are the same
	$privcalls[$client][$cmd][] = array($object,$func);
}

class p10 {
	function __construct($sock) {
		$this->socket = $sock;
	}
	function ignore(){
		return;
	}
	function protocol_start($args){
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		if ($opMode != "serv") die("Ehm... aren't you supposed to be using ircv3c.php?");
		$confItems["SID"] = $args[4];
		regCallback($this,"protocol_privmsg","P");
		regCallback($this,"irc_join","J");
		regCallback($this,"irc_mode","M");
		regCallback($this,"irc_mode","OM");
		regCallback($this,"irc_njoin","B");
		regCallback($this,"irc_part","L");
		regCallback($this,"sts_rj","K");
		regCallback($this,"irc_nick","N");
		regCallback($this,"irc_signout","Q");
		regCallback($this,"irc_server","S");
		regCallback($this,"irc_server","SERVER");
		regCallback($this,"irc_ping","G");
		regCallback($this,"irc_acct","AC");
		$this->sw(sprintf("PASS %s",$args[1]));
		$this->sw(sprintf("SERVER %s 1 %s %s J10 %s]]] +sh6 :%s",$args[0],time(),time(),$args[4],$args[2]));
	}

	function sw($mesg) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		$mods["%select%"]->write($this->socket,$mesg."\r\n");
		if ($debug) fwrite(STDOUT,"Output ".$mesg."\n");
	}

	function mode($uid,$mesg) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		$pad = str_repeat("A",3-strlen($this->enc($uid)));
		$this->sw($confItems["SID"].$pad.$this->enc($uid)." M ".$mesg." ".$this->CTS[explode(" ",$mesg)[0]]."\r\n");
		$this->irc_mode($this->parse("MODE ".$mesg));
	}

	function kill($client,$mesg) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		fwrite($this->socket,$confItems["SID"].$this->cliNick[$client]." D ".$mesg."\r\n");
	}

	function irc_capab($get) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		$payload = array_slice($get,1);
		foreach ($payload as $token) {
			$this->caps[$token] = true;
		}
	}

	function sendUid($sender,$nick,$ident,$host,$dhost,$uid,$realname="* Unknown *"){
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		$pad = str_repeat("A",3-strlen($this->enc($uid)));
		$this->cliID[$confItems["SID"].$pad.$this->enc($uid)] = $nick;
		$this->cliNick[$nick] = $confItems["SID"].$pad.$this->enc($uid);
		$this->sw(sprintf("%s N %s 1 %s %s %s +oikd AAAAAA %s%s%s :%s",$confItems["SID"], $nick, time(), $ident, $host, $confItems["SID"],$pad,$this->enc($uid), $realname));
		return true; //user introduced
	}

	function isop($nick,$channel) {
		return (strpos($this->opmodes[$cmd[$nick]][$channel],"o") === false)?false:true;
	}

	function irc_acct($args) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		switch($args[2]) {
		case "R":
		// This should ONLY ever happen because of a NetSplit that rejoins.
		// Anyone ever heard of "staying logged in thru a netsplit"?
		// It's probably kosher; no one should be running two sets of services on a network *sigh*. Update the arrays and let everyone know
		$this->accnt[$args[1]] = $args[3];
		callEvents(array("cmd"=>"ACCOUNT","nick"=>$args[1],"action"=>"I","acct"=>$args[3]));
		break;
		case "C":
		// This should ONLY ever happen because of a NetSplit that rejoins.
		// Anyone ever heard of "staying logged in thru a netsplit"?
		// It's probably kosher; no one should be running two sets of services on a network *sigh*. Update the arrays and let everyone know
		callEvents(array("cmd"=>"ACCOUNT","serv"=>$args["sendernick"],"rqid"=>$args[3],"action"=>"R","acct"=>$args[4],"pass"=>$args["payload"]));
		break;
		}
	}

	function sts_authallow($to,$rqid) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		$this->sw(sprintf("%s AC %s A %s",$confItems["SID"],$to,$rqid));
	}

	function sts_authdeny($to,$rqid) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		$this->sw(sprintf("%s AC %s D %s",$confItems["SID"],$to,$rqid));
	}

	function irc_nick($args) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		var_dump($args);

		if (count($args) > 5) { /* Got the right number of arguments for a user introduction? */
			if (strpos($args[6],"r") !== false) {
				$num = $args[9];
				if (strpos($args[6],"h") !== false) {
					$num = $args[10];
				}
				var_dump($this->accnt);
				$this->accnt[$num] = $args[7];
			} else if (strpos($args[6],"+") !== false) {
				$num = $args[9];
			} else {
				$num = $args[8];
			}
			var_dump($num);
			$this->nick[$num] = $args[1];
			$this->ident[$num] = $args[4];
			$this->rhost[$num] = $args[5];
			$this->gecos[$num] = $args["payload"];
			callEvents(array("cmd"=>"SIGNON","ident"=>$args[4],"realhost"=>$args[5],"nick"=>$args[1]));
		} else { /* This guy is changing his nickname; update the arrays. */
			$this->nick[$num] = $args[1];
			// Tell everyone that this guy is now parking on a new nickname.
			// This is used by NSV and NickServ to name a few official modules.
			callEvents(array("cmd"=>"NICK","from"=>$args["sendernick"],"to"=>$args[1]));
		}
	}

	function irc_signout($args) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		unset($this->nick[$args["sendernick"]]);
		unset($this->ident[$args["sendernick"]]);
		unset($this->rhost[$args["sendernick"]]);
		unset($this->gecos[$args["sendernick"]]);
		unset($this->vhost[$args["sendernick"]]);
		unset($this->accnt[$args["sendernick"]]);
	}

	function sts_login($nick,$accountname,$vhost) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		$this->sw(sprintf("%s AC %s R %s",$confItems["SID"],$nick,$accountname));
		if ($vhost != "") $this->sw(sprintf("%s SH %s %s",$confItems["SID"], $nick,$vhost));
		$this->accnt[$nick] = $accountname;
		return true; //user logged in and vhosted
	}

	function sts_join($client,$channel) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		$uid = $client;
		$pad = str_repeat("A",3-strlen($this->enc($uid)));
		$this->sw(sprintf("%s B %s %s %s:o",$confItems["SID"],$channel,$this->CTS[$channel],$confItems["SID"].$pad.$this->enc($uid)));
	}

	function sts_tsjoin($client,$channel,$cts) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		$uid = $client;
		$pad = str_repeat("A",3-strlen($this->enc($uid)));
		$this->sw(sprintf("%s B %s %s %s:o",$confItems["SID"],$channel,$cts,$confItems["SID"].$pad.$this->enc($uid)));
	}

	function sts_rj($args) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		$client = $args[2];
		$channel = $args[1];
		$uid = $client;
		$this->sw(sprintf("%s L %s",$uid,$channel));
		$this->sw(sprintf("%s B %s %s %s:o",$confItems["SID"],$channel,$this->CTS[$channel],$uid));
	}

	function sts_part($client,$channel, $reason) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		$this->sw(sprintf("%s%s L %s :%s",$confItems["SID"],$this->enc($this->cliNick[$client]),$channel,$reason));
	}

	function parse($get){
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		if ($debug) fwrite(STDOUT,"Input  ".$get."\n");
		$hasSrc = false;
		if ($get[0] == ":") $hasSrc = true;
		else $hasP10Src = true;
		$msg = explode(" :",$get,2);
		$comd = $msg[0];
		$payload = $msg[1];
		$cmd = explode(" ", $comd);
		$cmd["sendernick"] = $cmd[0]; // Useful! :P That is, if the sender is not a server. :P
		$cmd["cmd"] = $cmd[1];
		if ($payload != "") $cmd["payload"] = $payload;
		return $cmd;
	}

	function irc_mode($get) {
		$modes = array_slice($get,3);
		var_dump($this->opmodes);
		for ($i=0;$i<strlen($get[2]);$i++) {
			if ($get[2][$i] == "") break;
			if ($get[2][$i] == "-") {
				$status = "down";
			} else if ($get[2][$i] == "+") {
				$status = "up";
			} else if (($status == "up") and !($get[2][$i] == "+") and !($get[2][$i] == "-")) {
				if (
				    ($get[2][$i] == "o") or
				    ($get[2][$i] == "h") or
				    ($get[2][$i] == "v"))
				{
					$this->opped[$get[1]][$modes[$i]] .= $get[2][$i];
					$this->opmodes[$modes[$i]][$get[1]] .= $get[2][$i];
				}
			} else if (($status == "down") and !($get[2][$i] == "+") and !($get[2][$i] == "-")) {
				if (
				    ($get[2][$i] == "o") or
				    ($get[2][$i] == "h") or
				    ($get[2][$i] == "v"))
				{
					$this->opped[$get[1]][$modes[$i]][strpos($this->opped[$get[1]][$modes[$i]],$get[2][$i])] = "";
					$this->opmodes[$modes[$i]][$get[1]][strpos($this->opped[$get[1]][$modes[$i]],$get[2][$i])] = "";
				}
			}
		}
	}

	function protocol_privmsg($get){
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		// This parses PRIVMSGs. This should really be in the core... ugh
		$cmd = explode(" ",$get["payload"],2);
		foreach ($privcalls[$this->cliID[$get[1]]][$cmd[0]] as $callback) {
			call_user_func($callback,$get["sendernick"],$get[($cmd["hassource"])?2:1],$cmd[1]);
		}
		foreach ($privcalls[$this->dec(substr($get[1],-3))][$cmd[0]] as $callback) {
			call_user_func($callback,$get["sendernick"],$get[($cmd["hassource"])?2:1],$cmd[1]);
		}
	}

	function irc_ping($get){
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		$this->sw(sprintf("%s Z %s %s",$confItems["SID"],$get[2],$get[3]));
	}

	function send_msg($type,$uid,$to,$message) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		$pad = str_repeat("A",3-strlen($this->enc($uid)));
		if ($type[0] == "p") $this->sw(sprintf("%s P %s :%s", $confItems["SID"].$pad.$this->enc($uid),$to,$message));
		if ($type[0] == "P") $this->sw(sprintf("%s P %s :%s", $confItems["SID"].$pad.$this->enc($uid),$to,$message));
		if ($type[0] == "n") $this->sw(sprintf("%s O %s :%s", $confItems["SID"].$pad.$this->enc($uid),$to,$message));
		if ($type[0] == "N") $this->sw(sprintf("%s O %s :%s", $confItems["SID"].$pad.$this->enc($uid),$to,$message));
	}

	function irc_join($args) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		$args[2] = strtolower($args[1]);
		$chans = explode(",",$args[1]);
		foreach ($chans as $chandef) {
			$chandef = explode("\x07",$chandef);
			$chan = $chandef[0];
			$ops = $chandef[1];
			$this->inChans[$args["sendernick"]][$chan] = true;
			$this->opmodes[$args["sendernick"]][$chan] = $ops;
			callEvents(array("cmd"=>"JOIN","from"=>$args["sendernick"],"chan"=>$chan));
		}
	}

	function irc_njoin($args) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		$sjnicks = explode(",",$args[(count($args)-3)]);
		$get = $args;
		$this->CTS[$get[1]] = $get[2];
		var_dump($sjnicks);
		$get[1] = strtolower($get[1]);
		$args[1] = strtolower($get[1]);
		foreach ($sjnicks as $nick) {
			$nicklax = explode(":",$nick);
			$nick = $nicklax[0];
			$modestring = $nicklax[1];
			echo "NMODE ".$nick."+".$modestring."\n";
			$this->inChans[$nick][$get[1]] = true;
			if ($modestring) $this->opmodes[$nick][strtolower($get[1])] = $modestring;
			if ($modestring) $this->opped[strtolower($get[1])][$nick] = $modestring;
		}
	}

	function irc_part($args) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		$chans = explode(",",$args[2]);
		foreach ($chans as $chandef) {
			unset($this->inChans[$cmd["sendernick"]][$chan]);
			unset($this->opmodes[$cmd["sendernick"]][$chan]);
			unset($this->opped[$chan][$cmd["sendernick"]]);
		}
	}

	function send_notice($from,$to,$message) { global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug; $this->send_msg("Notice",$from,$to,$message); }
	function send_privmsg($from,$to,$message) { global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug; $this->send_msg("PRIVMSG",$from,$to,$message); }
	function enc($numberInput) {
    $fromBaseInput="0123456789";
    $toBaseInput="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789[]";
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
    $plen = strlen($retval);
    $pad = str_repeat("A",(3-$plen));
    echo $retval;
    return $pad.$retval;
	}
	function dec($numberInput) {
    $fromBaseInput="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789[]";
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
}
