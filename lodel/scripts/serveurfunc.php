<?php
if (!function_exists("file_get_contents")) {
  function file_get_contents($file) 
  {
    $fp=fopen($file,"r") or die("Impossible de lire le fichier $file");
    while(!feof($fp)) $res.=fread($fp,2048);
    fclose($fp);
    return $res;
  }
}


function upload($url,$vars,$files=0,$cookies=0,$outfile="")

{
#  $t=time();
  $url=parse_url($url);
  $bound=md5($files[0].microtime());

  $request="POST $url[path] HTTP/1.1\r\nConnection: keep-alive\r\nHost: $url[host]\r\nContent-Type: multipart/form-data; boundary=---------------------------$bound\r\nKeep-Alive: 300";

  if ($cookies) {
    list ($key, $val) = each ($cookies);
    $request.="\r\nCookie: $key=$val";
  }

  $request.="\n";

  // envoie les variables
  foreach($vars as $var =>$val) {
    $content.="\r\n-----------------------------$bound\r\nContent-Disposition: form-data; name=\"$var\"\r\n\r\n$val";
  }
  
  // envoie les fichiers
  if ($files) {
    foreach($files as $file) {
      $content.="\r\n-----------------------------$bound\r\nContent-Disposition: form-data; name=\"file".(++$count)."\"; filename=\"$file\"\r\nContent-Type: application/octet-stream\r\n\r\n".file_get_contents($file);
    }
  }
  $content.="\r\n-----------------------------$bound--\r\n";

  $request.="Content-length: ".strlen($content)."\r\n".$content."\r\n";

$port=$url[port] ? $url[port] : 80;
$fp = fsockopen ("$url[host]", $port, $errno, $errstr, 30);
if (!$fp) die("ERROR: cannot connect to $url[host]:$port\n");
    
 fputs ($fp,$request);

# $fp2=fopen("/tmp/tmp1","w");
# while (!feof($fp)) { $buf=fread($fp,1024); fwrite($fp2,$buf); }
# exit();

  // lit le header
  $line="";
  while (!feof($fp) && $line!="\r\n") {
    $line=fgets($fp,1024);
    if (strpos($line,"Transfer-Encoding:")===0 && $line!="Transfer-Encoding: chunked\r\n") die ("Bug a reporter: le transfert encoding n'est pas chunked: <br>".$line);
  }
  if ($outfile) {
    if (file_exists($outfile)) { if (! (unlink($outfile)) ) die ("Ne peut pas supprimer $outfile. Probleme de droit sur les fichiers et repertoire surement"); }
   $fout=fopen($outfile,"w");
   if (!$fout) die("impossible d'ouvrir le fichier $outifle en ecriture");
  }

  $size=0;
  $res="";

  if (feof($fp)) die("erreur de transfert");

  do {
    $chunk_head=fgets($fp,1024);
    if (!preg_match("/^[A-Fa-f0-9]+\s*\r\n/",$chunk_head)) {
      #while ($chunk_head) { echo ord($chunk_head)," "; $chunk_head=substr($chunk_head,1); }
      die ("ERROR: chunk head invalid: \"$chunk_head\"");
    }
    $chunksize=hexdec($chunk_head); # lit le chunck size
    error_log("chunk: \"$chunk_head\" $chunksize\n",3,"/tmp/log");
    while ($chunksize) {
      if ($outfile) {
	$bytetoread=min($chunksize,2048);
      } else {
	$bytetoread=$chunksize;
      }
      error_log("buf to be read $bytetoread \n",3,"/tmp/log");
      $buf=fread ($fp,$bytetoread);
      error_log("buf read $size\n",3,"/tmp/log");

# $fp2=fopen("/tmp/tmp1","w");
# fwrite($fp2,$buf);
# exit();

      if ($size==0) {
	if (preg_match("/^ERROR:[^\n\r]+/",$buf,$result)) { return $result[0]; }
	if (preg_match("/^content-length:\s*(\d+)\s*\r?\n/",$buf,$result)) {
	  $size=$result[1];
	  $buf=substr($buf,strlen($result[0]));
	} else {
	  #for($i=0; $i<40; $i++) { echo substr($buf,0,1)," ",ord($buf),"  "; $buf=substr($buf,1);}
	  die("content-length not found: \"$buf\"");
	}
	error_log("content-lenght $size\n",3,"/tmp/log");
      }
      if ($outfile) {
	fwrite($fout,$buf);
      } else {
	$res.=$buf;
      }
      $chunksize-=$bytetoread;
      $size-=$bytetoread;
    }
    fgets($fp,1024); # ligne vide
  } while (!feof($fp) && $size>0);
  fclose ($fp);

# echo "tout ",(time()-$t),"<br>\n";
 return $res; 
}




?>
