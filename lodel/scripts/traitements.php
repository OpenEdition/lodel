<?

// strip tags qui garde les footnotes de OpenOffice

function local_strip_tags($text,$keeptags)

{
  $arr=preg_split("/(<sup>(?:<font\b[^>]*>)*<a class=\"sdfootnoteanc\"[^>]+><sup>[^<>]+<\/sup><\/a>(?:<\/font>)*<\/sup>)/s",$text,-1,PREG_SPLIT_DELIM_CAPTURE);
  $count=count($arr);
  for($i=0; $i<$count; $i+=2) $arr[$i]=strip_tags($arr[$i],$keeptags);
  return join("",$arr);
}


?>
