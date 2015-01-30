<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier utilitaire proposant diverses fonction de gestion d'images
 */

/**
 * Redimensionner une image
 *
 * <p>Cette fonction utilise la librairie GD de PHP. Il faut donc qu'elle soit installée pour
 * que cette fonction soit utilisable. Si la GD n'est pas installée, la fonction essayera avec Imagick, 
 * et retournera false si l'extension n'est pas installée</p>
 *
 * @param string $taille la nouvelle taille de l'image. Peut être un entier ou une chaine représentant la longueur et la largeur
 * @param string $src l'image source
 * @param string &$dest l'image redimensionnée
 * @return boolean true si l'image a bien pue être transformée.
 */
function resize_image($taille, $src, &$dest)
{
	do { // exception
		
/*		if(extension_loaded('imagick'))
		{
			$result = @getimagesize($src);
			if (is_numeric($taille))
			{ // la plus grande taille
				if ($result[0] > $result[1]) {
					$width = $taille;
					$height = 0;
				}	else {
					$height = $taille;
					$width = 0;
				}
			}
			elseif (preg_match("/(\d+)[x\s]+(\d+)/", $taille, $result2)) {
					$width = $result2[1];
					$height = $result2[2];
			}

			if(!isset($width)) return false;

			$image = new Imagick($src);
			$jpg = substr($src, -4) === '.jpg' || substr($src, -4) === 'jpeg';

			if(!$jpg && substr($src, -4) === '.png')
			{
				if($image->getImageColors() > 256)
				{
					$type = $image->getImageType();
					$dest = str_replace('.png', '.jpg', $dest);
					$image->setImageFormat('jpg');
					$jpg = true;
				}
			}

			if(file_exists($dest))
			{
				$image->destroy();
				return true;
			}

			$colors = $image->getImageColors();
			$colorSpace = $image->getImageColorspace();
			$depth = $image->getImageDepth();
			isset($type) || $type = $image->getImageType();
			$compr = $image->getImageCompressionQuality();

			$image->thumbnailImage($width, $height);
			$image->quantizeImage($colors, $colorSpace, $depth, false, false);
			$image->setImageType($type);
			$image->setImageDepth($depth);

			$image->setImageCompression($jpg ? Imagick::COMPRESSION_JPEG : imagick::COMPRESSION_LZW);
			
			$image->setImageCompressionQuality($compr > 0 && $compr < 80 ? $compr : 80);

			$image->writeImage($dest);

			$image->destroy();

			return true;
		}
		else*/if (!($gdv = GDVersion())) {
			return false; // Pas de Imagick ni de GD installé
		}

		// cherche le type de l'image
		$result = @getimagesize($src);
		if ($result[2] == 1 && function_exists("ImageCreateFromGIF"))	{
			$im = @ImageCreateFromGIF($src);
		}	elseif ($result[2] == 2 && function_exists("ImageCreateFromJPEG")) {
			$im = @ImageCreateFromJPEG($src);
		}	elseif ($result[2] == 3 && function_exists("ImageCreateFromPNG"))	{
			$im = @ImageCreateFromPNG($src);
		}	else {
			return false;
		}
		if (!$im) {
			return false; // error de chargement
		}
		// taille de l'image a produire
		if (is_numeric($taille)) { // la plus grande taille
			if ($result[0] > $result[1]) {
				$width = $taille;
				$height = (int)(($taille * $result[1]) / $result[0]);
			}	else {
				$height = $taille;
				$width = (int)(($taille * $result[0]) / $result[1]);
			}
		}	else {
			if (!preg_match("/(\d+)[x\s]+(\d+)/", $taille, $result2)) {
				break;
			}
			$width = $result2[1] ? $result2[1] : $result[0];
			$height = $result2[2] ? $result2[2] : $result[1];
		}
		if ($gdv >= 2) { //Sur la GD2 la version a changé
			$im2 = @ImageCreateTrueColor($width, $height);
			if (!$im2) {
				return false;
			}
			// conservation de la transparence
			imagealphablending($im2, FALSE);
			imagesavealpha($im2, TRUE);
			ImageCopyResampled($im2, $im, 0, 0, 0, 0, $width, $height, $result[0], $result[1]);
		}	else {
			$im2 = ImageCreate($width, $height);
			if (!$im2) {
				return false;
			}
			ImageCopyResized($im2, $im, 0, 0, 0, 0, $width, $height, $result[0], $result[1]);
		}
		if (file_exists($dest)) {
			unlink($dest);
		}
		if ($result[2] == 1) {
			if (function_exists("ImageGIF")) {
				ImageGIF($im2, $dest);
			}	else { // sometimes writing GIF is not allowed... make a PNG it's anyway better.
				$dest = preg_replace("/\.gif$/i", ".png", $dest);
				$result[2] = 3;
			}
		}
		if ($result[2] == 2) {
			if (function_exists("ImageJPEG")) {
				ImageJPEG($im2, $dest, 100);
			}	else { // make a PNG rather
				$dest = preg_replace("/\.jpe?g$/i", ".png", $dest);
				$result[2] = 3;
			}
		}
		if ($result[2] == 3) {
			ImagePNG($im2, $dest, 9, PNG_ALL_FILTERS);
		}
		return true;
	}	while (0); // exception
	copy($src, $dest);
	return true;
}

/**
 * Récupère le numéro de version de la GD si celle-ci est installée
 * Get which version of GD is installed, if any.
 *
 * @return  la version (1 or 2) de l'extension GD installée
 */
function GDVersion()
{
	static $gdversion;
	if ($gdversion) {
		return $gdversion;
	}
	// method since 4.3.0
	if (function_exists("gd_info")) {
		$info = gd_info();
		preg_match('/\d+/', $info["GD Version"], $gd);
		$gdversion = $gd[0];
		if ($gdversion) {
			return $gdversion;
		}
	}
	// brute force
	if (!extension_loaded('gd')) {
		return;
	}
	ob_start();
	phpinfo(8);
	$info = ob_get_clean();
	$info = stristr($info, 'gd version');
	preg_match('/\d+/', $info, $gd);
	$gdversion = $gd[0];
	return $gdversion;
}