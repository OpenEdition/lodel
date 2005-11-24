<?php

/*
 *
 *  LODEL - Logiciel d'Edition ELectronique.
 *
 *  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 *  Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 *  Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cnou
 *  Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy
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

// traitement des images
function resize_image($taille, $src, & $dest)
{
	do { // exception
		// cherche le type de l'image
		$result = getimagesize($src);
		if ($result[2] == 1 && function_exists("ImageCreateFromGIF"))	{
			$im = ImageCreateFromGIF($src);
		}	elseif ($result[2] == 2 && function_exists("ImageCreateFromJPEG")) {
			$im = ImageCreateFromJPEG($src);
		}	elseif ($result[2] == 3 && function_exists("ImageCreateFromPNG"))	{
			$im = ImageCreateFromPNG($src);
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
				$height = intval(($taille * $result[1]) / $result[0]);
			}	else {
				$height = $taille;
				$width = intval(($taille * $result[0]) / $result[1]);
			}
		}	else {
			if (!preg_match("/(\d+)[x\s]+(\d+)/", $taille, $result2)) {
				break;
			}
			$width = $result2[1] ? $result2[1] : $result[0];
			$height = $result2[2] ? $result2[2] : $result[1];
		}
		if (!($gdv = GDVersion())) {
			return false; // Pas de GD installé
		}
		if ($gdv >= 2) { //Sur la GD2 la version a changé
			$im2 = ImageCreateTrueColor($width, $height);
			if (!$im2) {
				return false;
			}
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
				$result[2] = 2;
			}
		}
		if ($result[2] == 2) {
			if (function_exists("ImageJPEG")) {
				ImageJPEG($im2, $dest);
			}	else { // make a PNG rather
				$dest = preg_replace("/\.jpe?g$/i", ".png", $dest);
				$result[2] = 2;
			}
		}
		if ($result[2] == 3) {
			ImagePNG($im2, $dest);
		}
		return true;
	}	while (0); // exception
	copy($src, $dest);
	return true;
}

/**
 * Get which version of GD is installed, if any.
 *
 * Returns the version (1 or 2) of the GD extension.
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
	$info = ob_get_contents();
	ob_end_clean();
	$info = stristr($info, 'gd version');
	preg_match('/\d+/', $info, $gd);
	$gdversion = $gd[0];
	return $gdversion;
}
?>