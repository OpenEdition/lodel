<?

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
