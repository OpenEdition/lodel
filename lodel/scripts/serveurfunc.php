<?php
/*
 *
 *  LODEL - Logiciel d'Edition ELectronique.
 *
 *  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 *  Copyright (c) 2003-2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 *
 *  Home page: http://www.lodel.org
 *
 *  E-Mail: lodel@lodel.org
 *
 *                            All Rights Reserved
 *
 *     This program is free software; you can redistribute it and/or modify
 *     it under the terms of the GNU General Public License as published by
 *     the Free Software Foundation; either version 2 of the License, or
 *     (at your option) any later version.
 *
 *     This program is distributed in the hope that it will be useful,
 *     but WITHOUT ANY WARRANTY; without even the implied warranty of
 *     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *     GNU General Public License for more details.
 *
 *     You should have received a copy of the GNU General Public License
 *     along with this program; if not, write to the Free Software
 *     Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.*/



function contact_servoo ($cmds,$uploadedfiles,$destfile="") 

{

  $options=getoption(array("servoourl","servoousername","servoopasswd"),"");

  if (!$options || !$options[servoourl]) { // get form the lodelconfig file
    $options[servoourl]=$GLOBALS[servoourl];
    $options[servoousername]=$GLOBALS[servoousername];
    $options[servoopasswd]=$GLOBALS[servoopasswd];
  }
  if (!is_array($uploadedfiles)) $uploadedfiles=array($uploadedfiles);

  $ret=upload($options[servoourl],
	      array("username"=>$options[servoousername],
		    "passwd"=>$options[servoopasswd],
		    "commands"=>$cmds),
	      $uploadedfiles, # fichier a uploaded
	      0, # cookies
	      $destfile
	      );
  if ($ret) { # erreur
    return $ret;
  }
}




if (!function_exists("file_get_contents")) {
  function file_get_contents($file) 
  {
    $fp=fopen($file,"r") or die("Impossible de lire le fichier $file");
    while(!feof($fp)) $res.=fread($fp,2048);
    fclose($fp);
    return $res;
  }
}

function removeaccentsandspaces($string){
return strtr(
 strtr(utf8_decode(preg_replace("/[\s_\r]/","",$string)),
  '¦´¨¸¾ÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÑÒÓÔÕÖØÙÚÛÜÝàáâãäåçèéêëìíîïñòóôõöøùúûüýÿ',
  'SZszYAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy'),
array('Þ' => 'TH', 'þ' => 'th', 'Ð' => 'DH', 'ð' => 'dh', 'ß' => 'ss',
  '¼' => 'OE', '½' => 'oe', 'Æ' => 'AE', 'æ' => 'ae', 'µ' => 'u'));
}




function upload($url,$vars,$files=0,$cookies=0,$outfile="")

{
#  $t=time();
  //
  // create the request header
  // Should we use the PEAR insteed ?

  $url=parse_url($url);
  if (!$url[path]) $url[path]="/";
  $boundary="---------------------------".md5($files[0].microtime());

  $request="POST $url[path] HTTP/1.1\r\nConnection: keep-alive\r\nHost: $url[host]\r\nContent-Type: multipart/form-data; boundary=$boundary\r\nKeep-Alive: 300";

  if ($cookies) {
    list ($key, $val) = each ($cookies);
    $request.="\r\nCookie: $key=$val";
  }

  $request.="\r\n";

  // envoie les variables
  foreach($vars as $var =>$val) {
    $content.="\r\n--$boundary\r\nContent-Disposition: form-data; name=\"$var\"\r\n\r\n$val";
  }

  // envoie les fichiers
  if ($files) {
    foreach($files as $file) {
      $content.="\r\n--$boundary\r\nContent-Disposition: form-data; name=\"file".(++$count)."\"; filename=\"$file\"\r\nContent-Type: application/octet-stream\r\n\r\n".file_get_contents($file);
    }
  }
  $content.="\r\n--$boundary--\r\n";

  $request.="Content-length: ".strlen($content)."\r\n".$content."\r\n";

  $port=$url[port] ? $url[port] : 80;
  $fp = fsockopen ("$url[host]", $port, $errno, $errstr, 30);
  if (!$fp) die("ERROR: cannot connect to $url[host]:$port\n");
    
  if (fputs ($fp,$request)!=strlen($request)) die("ERROR: cannot write to $url[host]:$port\n");

  //
  // ok, the header is sent.
  // let's listen the response.
  //

  // read the header
  $line="";
  while (!feof($fp) && $line!="\r\n") {
    $line=fgets($fp,1024);
    #echo "line:".htmlentities($line)."<br/>";
    if (strpos($line,"Transfer-Encoding:")===0 && $line!="Transfer-Encoding: chunked\r\n") die ("Bug a reporter: le transfert encoding n'est pas chunked: <br>".$line);
  }
  if ($outfile) {
    if (file_exists($outfile)) { if (! (unlink($outfile)) ) die ("Ne peut pas supprimer $outfile. Probleme de droit sur les fichiers et repertoire surement"); }
   $fout=fopen($outfile,"w");
   if (!$fout) die("impossible d'ouvrir le fichier $outifle en ecriture");
  }

  $size=-1;
  $res="";
  $retvar=array();

  if (feof($fp)) die("erreur de transfert");

  do {
    $chunk_head=fgets($fp,1024);
    if (!preg_match("/^[A-Fa-f0-9]+\s*\r\n/",$chunk_head)) {
      #while ($chunk_head) { echo ord($chunk_head)," "; $chunk_head=substr($chunk_head,1); }
      #while (!feof($fp)) { echo "line: ".htmlentities(fgets($fp,1024))."<br />"; }
      die ("ERROR: chunk head invalid: \"$chunk_head\" eof:".(feof($fp) ? "yes" : "no")."\n");
      
    }
    $chunksize=hexdec($chunk_head); # lit le chunck size
    #error_log("chunk: \"$chunk_head\" $chunksize\n",3,"/tmp/log");
    while ($chunksize) {
      if ($outfile) {
	$bytetoread=min($chunksize,2048);
      } else {
	$bytetoread=$chunksize;
      }
      #error_log("buf to be read $bytetoread \n",3,"/tmp/log");
      $buf=fread ($fp,$bytetoread);
      $byteread=strlen($buf);
      #error_log("buf read $size\n",3,"/tmp/log");
      $chunksize-=$byteread;

      if ($size==-1) {
	//
	// we handle very simplified header.
	// the processing is not good here... to improve.
	//
	if (preg_match("/^(ERROR|SAY):/",$buf,$result)) { return array($buf); }
	if (preg_match("/^version:\s*(.*?)\r?\n/",$buf,$result)) {
	  $retvar[version]=$result[1];
	  $buf=substr($buf,strlen($result[0]));
	  if (!$buf) continue;
	}
	if (preg_match("/^content-length:\s*(\d+)\s*\r?\n/",$buf,$result)) {
	  $size=$result[1];
	  $buf=substr($buf,strlen($result[0]));
	  if (!$buf) continue;
	  $byteread=strlen($buf);
	} else {
	  while (!feof($fp)) { echo fgets($fp,1024); }
	  die("content-length not found: \"$buf\"");
	}
      }
      if ($outfile) {
	fwrite($fout,$buf);
      } else {
	$res.=$buf;
      }
      $size-=$byteread;
    } // while the chunk is not read 
    fgets($fp,1024); # ligne vide
  } while (!feof($fp) && $size!=0);
  fclose ($fp);

# echo "tout ",(time()-$t),"<br>\n";
  return array($res,$retvar);
}




?>
