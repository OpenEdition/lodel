<?

include ("lodelconfig.php");
include ("$home/auth.php");
authenticate(LEVEL_SUPERADMIN,NORECORDURL);

$repertoire=$context[repertoire]="/www-bin/revues/import";
// il faut locker la base parce que le dump ne doit pas se faire en meme temps que quelqu'un ecrit un fichier.

if ($fichier && preg_match("/^revue-.*-\d+.tar.gz/i",$fichier) && file_exists("$repertoire/$fichier")) {
  $fichier="$repertoire/".$fichier;

  // detar dans le repertoire de la revue
  system("tar zxf $fichier -C ../../ lodel/txt lodel/rtf docannexe 2>&1")!==FALSE or die ("impossible d'executer tar");

   system("tar zxf $fichier -O 'revue-*.sql' | $mysqldir/mysql $currentdb -h $dbhost -u $dbusername -p$dbpasswd 2>/tmp/import.tmp")!==FALSE or die ("impossible d'executer tar et mysql");
#   system("tar zxf $fichier -O 'revue-*.sql' 2>/tmp/import.tmp | /usr/local/mysql/bin/mysql $currentdb -u $dbusername -p$dbpasswd 2>>/tmp/impot.tmp")!==FALSE or die ("impossible d'executer tar et mysql");

#die (join("<br>",file("/tmp/import.tmp")));
//  // detar le fichier sql
//  $tmpfile=tempnam("","");
//  system("tar zxf $fichier -O 'revue-*.sql' >$tmpfile")!==FALSE or die ("impossible d'executer tar");
//  
//  $sql=preg_split ("/;/",join('',file($tmpfile)));
//  foreach ($sql as $cmd) {
//    $cmd=trim(preg_replace ("/#.*?$/m","",$cmd));
//    if ($cmd) {
//	print "$cmd<BR>\n";
//	mysql_db_query($currentdb,$cmd) or print ("<font COLOR=red>".mysql_error()."</font><br>");
//	print "<BR>\n";
//    }
//  }
//

// verifie les .htaccess dans le CACHE
   $dirs=array("CACHE","lodel/admin/CACHE","lodel/edition/CACHE","lodel/txt","lodel/rtf");
   foreach ($dirs as $dir) {
     $file="../../$dir/.htaccess";
     if (file_exists($file)) @unlink($file);
     $f=@fopen ($file,"w");
     if (!$f) {
       print ("<font COLOR=red>Impossible d'ecrire dans le repertoire $dir. Faire: chmod 770 $dir ou chmod 777 $dir</font><br>");
       $err=1;
     } else {
       fputs($f,"deny from all\n");
       fclose ($f);
     }
   }
   

   if (!$err) { include_once ("$home/func.php"); back();}
}


include ("$home/calcul-page.php");
calcul_page($context,"import");


function boucle_fichiers(&$context)


{
  global $repertoire;
  $dir=opendir($repertoire);
  while (($file=readdir($dir))!==FALSE) {
    if (!preg_match("/^revue-.*-\d+.tar.gz/i",$file)) continue;
    $context[nom]=$file;
    code_boucle_fichiers($context);
  }
  closedir ($dir);
}



?>
