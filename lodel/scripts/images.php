<?php


function change_image($filename,$id,$classe,$champ)
     // change l'image si $filename existe
     // detruit l'image si $filename contient "delete"
{
  if ($classe=="sites") {
    $critere="id='$id'";
    $db=$GLOBALS[database];
  } else {
    $critere="identite='$id'";
    $db=$GLOBALS[currentdb];
  }
  
  $result=mysql_db_query($db,"SELECT $champ FROM $GLOBALS[tp]$classe WHERE $critere") or die (mysql_error());
  if (!mysql_num_rows($result)) die("Erreur interne. Le $critere ne repond pas dans $classe");
  list($oldimagefile)=mysql_fetch_row($result);

  $newimagefile="";
  if ($filename=="delete") {
    if ($oldimagefile && file_exists("../../$oldimagefile")) unlink("../../$oldimagefile");
    $newimagefile=""; // plus aucun fichier
  } else {
    // charge le fichier si necessaire
    if (!$filename || $filename=="none" || !is_uploaded_file($filename)) return FALSE; 
    $result=getimagesize($filename);
    if ($result[2]==1) { $ext="gif"; }
    elseif ($result[2]==2) { $ext="jpg"; }
    elseif ($result[2]==3) { $ext="png"; }
    else return FALSE;

    if ($oldimagefile) { unlink("../../$oldimagefile"); }
    $newimagefile="docannexe/img-$classe-$champ-$id.$ext";
    //    if ($context[taille]) {
    //      include_once($home."images.php");
    //      resize_image($context[taille],$filename,"../../$newimagefile");
    //    } else {
    copy($filename,"../../$newimagefile");
    // }
  }
  return $newimagefile;
}




// traitement des images


function resize_image ($taille,$src,$dest)

{
  do { // exception

    // cherche le type de l'image
    $result=getimagesize($src);
    if ($result[2]==1) { $im=@ImageCreateFromGIF($src); }
    elseif ($result[2]==2) { $im=@ImageCreateFromJPEG($src); }
    elseif ($result[2]==3) { $im=@ImageCreateFromPNG($src); }
    else { break; }
    if (!$im) break; // erreur de chargement

    // taille de l'image a produire
    if (is_numeric($taille)) { // la plus grande taille
      if ($result[0]>$result[1]) {
	$width=$taille;
	$height=intval(($taille*$result[1])/$result[0]);
      } else {
	$height=$taille;
	$width=intval(($taille*$result[0])/$result[1]);
      }
    } else {
      if (!preg_match("/(\d+)[x\s]+(\d+)/",$taille,$result2)) break;
      if (!($width=$result2[1])) break;
      if (!($height=$result2[2])) break;
    }

#ifndef LODELLIGHT
# GD 2.0
    $im2=@ImageCreateTrueColor($width,$height);
    ImageCopyResampled($im2,$im,0,0, 0,0, $width,$height,$result[0],$result[1]);
## GD 1.0	
#    $im2=ImageCreate($width,$height);
#    ImageCopyResized($im2,$im,0,0, 0,0, $width,$height,$result[0],$result[1]);
#else
#    if ($GLOBALS[dbhost]=="localhost") {
#    $im2=ImageCreate($width,$height);
#    ImageCopyResized($im2,$im,0,0, 0,0, $width,$height,$result[0],$result[1]);
#    } else {
#      $im2=@ImageCreateTrueColor($width,$height);
#      ImageCopyResampled($im2,$im,0,0, 0,0, $width,$height,$result[0],$result[1]);
#    }
#endif


    if ($result[2]==1) { ImageGIF($im2,$dest); }
    elseif ($result[2]==2) { ImageJPEG($im2,$dest); }
    elseif ($result[2]==3) { ImagePNG($im2,$dest); }

    return;
  } while (0); // exception
  copy($src,$dest);
}







?>
