#!/usr/bin/perl

use XML::Parser;

$p=new XML::Parser(ProtocolEncoding =>"ISO-8859-1");
$p->parsefile($ARGV[0]);
