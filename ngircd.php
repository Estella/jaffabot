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

class protocol {
	function ignore(){
		return;
	}
	function protocol_start($args){
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		if ($opMode != "serv") die("Ehm... aren't you supposed to be using ircv3c.php?");
		$confItems["SID"] = $args[0];
		regCallback($this,"protocol_privmsg","PRIVMSG");
		regCallback($this,"protocol_privmsg","SQUERY");
		regCallback($this,"irc_join","JOIN");
		regCallback($this,"irc_mode","MODE");
		regCallback($this,"irc_njoin","NJOIN");
		regCallback($this,"irc_part","PART");
		regCallback($this,"irc_nick","NICK");
		regCallback($this,"irc_signout","QUIT");
		regCallback($this,"irc_server","SERVER");
		regCallback($this,"irc_ping","PING");
		regCallback($this,"irc_capab","PASS");
		regCallback($this,"irc_metadata","METADATA");
		$this->sw(sprintf("PASS %s 0210-IRC+ jaffabot|0.1:CLMSXo",$args[1]));
		$this->sw(sprintf("SERVER %s 1 :%s",$args[0],$args[2]));
	}

	function sw($mesg) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		fwrite($socket,$mesg."\r\n");
		if ($debug) fwrite(STDOUT,"Output ".$mesg."\n");
	}

	function mode($client,$mesg) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		fwrite($socket,":".$client." MODE ".$mesg."\r\n");
		$this->irc_mode($this->parse("MODE ".$mesg));
	}

	function kill($client,$mesg) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		fwrite($socket,":".$client." KILL ".$mesg."\r\n");
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
		$this->cliID[$uid] = $nick;
		$this->cliNick[$nick] = $uid;
		$this->sw(sprintf(":%s NICK %s 1 %s %s 1 +ioqB :%s",$confItems["SID"], $nick, $ident, $host, $realname));
		$this->sw(sprintf(":%s METADATA %s cloakhost :%s",$confItems["SID"], $nick, $dhost));
		if ($debug) $this->sw(sprintf(":%s WALLOPS :Fake user %s introduced. A module in this program probably caused this.",$confItems["SID"], $nick));
		return true; //user introduced
	}

	function isop($nick,$channel) {
		return (strpos($this->opmodes[$cmd[$nick]][$channel],"o") === false)?false:true;
	}

	function issop($nick,$channel) {
		return ((strpos($this->opmodes[$cmd[$nick]][$channel],"a") === false) and (strpos($this->opmodes[$cmd[$nick]][$channel],"q") === false))?false:true;
	}

	function irc_metadata($args) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;

		switch ($args[2]) {
			case "accountname":
				if ($args["payload"] == "") {
					callEvents(array("cmd"=>"ACCOUNT","nick"=>$args[1],"action"=>"O"));
					break;
				}
				// This should ONLY ever happen because of a NetSplit that rejoins.
				// Anyone ever heard of "staying logged in thru a netsplit"?
				// It's probably kosher; no one should be running two sets of services on a network *sigh*. Update the arrays and let everyone know
				$this->accnt[$args[1]] = $args["payload"];
				callEvents(array("cmd"=>"ACCOUNT","nick"=>$args[1],"action"=>"I","acct"=>$args["payload"]));
				break;
			case "cloakhost":
				// This happens for anyone connecting or changing mode to +x
				$this->vhost[$args[1]] = $args["payload"];
				callEvents(array("cmd"=>"FAKEHOST","nick"=>$args[1],"hostname"=>$args["payload"]));
				break;
		}
	}

	function irc_nick($args) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;

		if (count($args) > 5) { /* Got the right number of arguments for a user introduction? */
			$this->nick[$args[1]] = $args[1];
			$this->ident[$args[1]] = $args[4];
			$this->rhost[$args[1]] = $args[5];
			$this->gecos[$args[1]] = $args["payload"];
			callEvents(array("cmd"=>"SIGNON","ident"=>$args[4],"realhost"=>$args[5],"nick"=>$args[1]));
		} else { /* This guy is changing his nickname; update the arrays. */
			$this->nick[$args[1]] = $args[1];
			$args[1] = $args["payload"];
			$this->ident[$args[1]] = $this->ident[$args["sendernick"]];
			unset($this->ident[$args["sendernick"]]);

			$this->rhost[$args[1]] = $this->rhost[$args["sendernick"]];
			unset($this->rhost[$args["sendernick"]]);

			$this->gecos[$args[1]] = $this->gecos[$args["sendernick"]];
			unset($this->gecos[$args["sendernick"]]);

			$this->vhost[$args[1]] = $this->vhost[$args["sendernick"]];
			unset($this->vhost[$args["sendernick"]]);

			$this->accnt[$args[1]] = $this->accnt[$args["sendernick"]];
			unset($this->accnt[$args["sendernick"]]);

			// Tell everyone that this guy is now parking on a new nickname.
			// This is used by NSV and NickServ to name a few official modules.
			callEvents(array("cmd"=>"NICK","from"=>$args["sendernick"],"to"=>$args[1]));
		}
	}

	function irc_signout($args) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		unset($this->ident[$args["sendernick"]]);
		unset($this->rhost[$args["sendernick"]]);
		unset($this->gecos[$args["sendernick"]]);
		unset($this->vhost[$args["sendernick"]]);
		unset($this->accnt[$args["sendernick"]]);
	}

	function sts_login($nick,$accountname,$vhost) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		$this->sw(sprintf(":%s METADATA %s accountname :%s",$confItems["SID"], $nick,$accountname));
		if ($vhost != "") $this->sw(sprintf(":%s METADATA %s cloakhost :%s",$confItems["SID"], $nick,$vhost));
		$this->accnt[$nick] = $accountname;
		return true; //user logged in and vhosted
	}

	function sts_join($client,$channel) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		$this->sw(sprintf(":%s JOIN %s\x07ao",$client,$channel));
	}

	function sts_part($client,$channel, $reason) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		$this->sw(sprintf(":%s PART %s :%s",$client,$channel,$reason));
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
				if (($get[2][$i] == "q") or
				    ($get[2][$i] == "a") or
				    ($get[2][$i] == "o") or
				    ($get[2][$i] == "h") or
				    ($get[2][$i] == "v"))
				{
					$this->opped[$get[1]][$modes[$i]] .= $get[2][$i];
					$this->opmodes[$modes[$i]][$get[1]] .= $get[2][$i];
				}
			} else if (($status == "down") and !($get[2][$i] == "+") and !($get[2][$i] == "-")) {
				if (($get[2][$i] == "q") or
				    ($get[2][$i] == "a") or
				    ($get[2][$i] == "o") or
				    ($get[2][$i] == "h") or
				    ($get[2][$i] == "v"))
				{
	//				$this->opped[$get[1]][$modes[$i]][strpos($this->opped[$get[1]][$modes[$i]],$get[2][$i])] = "";
	//				$this->opmodes[$modes[$i]][$get[1]][strpos($this->opped[$get[1]][$modes[$i]],$get[2][$i])] = "";
				}
			}
		}
	}

	function protocol_privmsg($get){
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		// This parses PRIVMSGs. This should really be in the core... ugh
		$cmd = explode(" ",$get["payload"],2);
		foreach ($privcalls[$get[1]][$cmd[0]] as $callback) {
			call_user_func($callback,$get["sendernick"],$get[($cmd["hassource"])?2:1],$cmd[1]);
		}
	}

	function irc_ping($get){
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		$this->sw(sprintf(":%s PONG :%s",$confItems["SID"],$get["payload"]));
	}

	function send_msg($type,$from,$to,$message) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		if ($type[0] == "p") $this->sw(sprintf(":%s PRIVMSG %s :%s", $this->enc($from),$to,$message));
		if ($type[0] == "P") $this->sw(sprintf(":%s PRIVMSG %s :%s", $this->enc($from),$to,$message));
		if ($type[0] == "n") $this->sw(sprintf(":%s NOTICE %s :%s", $this->enc($from),$to,$message));
		if ($type[0] == "N") $this->sw(sprintf(":%s NOTICE %s :%s", $this->enc($from),$to,$message));
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
		$sjnicks = explode(",",$args["payload"]);
		$get = $args;
		$get[1] = strtolower($get[1]);
		$args[1] = strtolower($get[1]);
		foreach ($sjnicks as $nick) {
			for ($i=0;$i<strlen($nick);$i++) {
				echo "NJOIN ".$nick."=".$i."\n";
				if ((($nick[$i] == "~") or ($nick[$i] == "&")
					or ($nick[$i] == "@") or ($nick[$i] == "%") or ($nick[$i] == "+"))) continue;
				$nickpos = $i; break;
			}
			$modestring = strtr(substr($nick,0,$nickpos),"~&@%+","qaohv");
			echo "NMODE ".$nick."+".$modestring."\n";
			$this->inChans[substr($nick,$nickpos)][$get[1]] = true;
			if ($modestring) $this->opmodes[substr($nick,$nickpos)][strtolower($get[1])] = $modestring;
			if ($modestring) $this->opped[strtolower($get[1])][substr($nick,$nickpos)] = $modestring;
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
	function enc($uid) {
		if (!(is_numeric($uid))) return $uid;
		if ($uid=="-Server-") return $Mline[0][1];
		return $this->cliID[$uid];
	}
}
