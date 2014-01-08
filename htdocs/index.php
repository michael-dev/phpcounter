<?php

/* (C) 2014, Michael Braun
 * License: GPLv3
 */

$domain = $_SERVER["SERVER_NAME"];
$configdir = dirname(dirname(__FILE__));

# get salt
$saltfile = $configdir.'/salt.txt';

if (!file_exists($saltfile)) {
  $salt = md5(rand());
  file_put_contents($saltfile, $salt);
} else {
  $salt = file_get_contents($saltfile);
}

$etag = md5($salt.'#'.$domain);

# get counter
$counterfile = $configdir.'/'.md5($domain).'.txt';
if (!file_exists($counterfile)) {
  $counter = 1;
} else {
  $counter = (int) file_get_contents($counterfile);
}

# check if it is a new user
if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) || isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
  # user already has a copy
} elseif (isset($_COOKIE[$etag])) {
  # user has cookie set
} else {
  $counter++;
  file_put_contents($counterfile, $counter);
}

# mark user
setcookie($etag, gmdate('r'), time()+60*60*24*365*10, '/');
header('Cache-Control: private');
header('ETag: "' . md5($salt.'#'.$domain) . '"');
header('Last-Modified: ' . gmdate('r'));

# generate image
$font_size = 8;
$string = sprintf("%08d",$counter);

//Get the size of the string
$width = imagefontwidth($font_size) * strlen($string);
$height = imagefontheight($font_size);

//Create the image
$img = @imagecreatetruecolor($width, $height) or die("Cannot Initialize new GD image stream");

//Make it transparent
imagesavealpha($img, true);
$trans_colour = imagecolorallocatealpha($img, 0, 0, 0, 127);
imagefill($img, 0, 0, $trans_colour);

//Get the text color
$text_color = imagecolorallocate($img, 0, 0, 0);

//Draw the string
imagestring($img, $font_size, 0, 0,  $string, $text_color);

//Output the image
header("Content-Type: image/png");
imagepng($img);
imagedestroy($img);
