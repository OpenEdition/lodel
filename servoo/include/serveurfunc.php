<?


function upload($url,$vars,$files=0,$cookies=0)

{
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
      $content.="\r\n-----------------------------$bound\r\nContent-Disposition: form-data; name=\"file".(++$count)."\"; filename=\"$file\"\r\nContent-Type: application/octet-stream\r\n\r\n".join("",file($file));
    }
  }
  $content.="\r\n-----------------------------$bound--\r\n";

  $request.="Content-length: ".strlen($content)."\r\n".$content."\r\n";

$port=$url[port] ? $url[port] : 80;
$fp = fsockopen ("$url[host]", $port, $errno, $errstr, 30);
if (!$fp) {
    die("ERREUR: cannot connect to $port\n");
} else {
  fputs ($fp,$request);
  $res="";
  while (!feof($fp)) {
    $res.=fread ($fp,1024);
  }
  fclose ($fp);
}
 return $res; 
}

?>
