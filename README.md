MarthaBot
===

Marthabot is an IRC bot written in PHP.
Its main feature is that it is modular. Only a small amount of boilerplate code needs to be written to write a module.

Requirements:

 * PHP 5.4 or later
 * PostgreSQL

Supported features:

 * SV, a nickname server
 * SVC, a basic channel access bot
 * Linking to ngIRCd networks
 * Connecting as a client (if you avoid service modules)

TODO features:

 * UnrealIRCd link support
 * TS6 support with full UUID going out from the pseudoserver
 * Ditto with InspIRCD support
 * Should I implement TS5?
 * Bahamut support
 * IRCNet >2.10 (only requires small modification to the ngIRCd code)
 * Advanced OperServ access and use SVSOPER (for supportive ircds)

Planned for MarthaBot 0.5:

 * Multiple socket support a la Eggdrop or Atheme
