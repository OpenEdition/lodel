<?

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

  // lit le header
  $line="";
  while (!feof($fp) && $line!="\r\n") {
    $line=fgets($fp,1024);
    if (strpos($line,"Transfer-Encoding:")===0 && $line!="Transfer-Encoding: chunked\r\n") die ("Bug a reporter: le transfert encoding n'est pas chunked: <br>".$line);
  }

  fgets($fp,1024); # contient la taille du chunk suivant
  $line=fgets($fp,1024);
  if (preg_match("/^ERROR:/",$line)) { return $line; }
  if (!preg_match("/^content-length:\s*(\d+)/",$line,$result)) {
    echo ("Le content-length n'a pas ete envoye (serveurfunc.php):<br>".$line."<br>");
    while (!feof($fp) && !preg_match("/^content-length/",$line)) { echo $line=fgets($fp),"<br>"; }
    exit(1);
  }
  $size=$result[1];
  #echo ":::<br>";

  if ($outfile) {
    if (file_exists($outfile)) { if (! (unlink($outfile)) ) die ("Ne peut pas supprimer $outfile. probleme de droit contacter Luc ou Ghislain"); }
   $fout=fopen($outfile,"w");
   if (!$fout) die("impossible d'ouvrir le fichier $outifle en ecriture");
  }

  $res="";
  while (!feof($fp) && $size) {
    #echo "vide:",fgets($fp,1024),"<br>"; # ligne vide
    fgets($fp,1024); # ligne vide
    $chunksize=hexdec(fgets($fp,1024)); # lit le chunck size
    $size-=$chunksize;
    #echo "SIZE: $chunksize $size<br>\n";
    
    if ($outfile) {
      while ($chunksize) {
	$bytetoread=min($chunksize,2048); $chunksize-=$bytetoread;
	fwrite($fout,fread ($fp,$bytetoread));
      }
    } else {
      $res.=fread ($fp,$chunksize);
    }

  }
  fclose ($fp);

# echo "tout ",(time()-$t),"<br>\n";
 return $res; 
}




?>
