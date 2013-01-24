<?php
require("spreadshirtapi.php");
require("spreadshirtproductimage.php");
require("tinypngapi.php");

$s = new SpreadShirtApi;

try
{ 
  $s->validate();
  $i = new SpreadShirtProductImage;
  if (!$i->imageExists($s->getS3ImagePath()))
  {
    if (!$s->isSettingUserDefined("appearanceId") &&
         $s->hasSettingValue("shopId"))
    {
      $s->getDefaultSettings();
    }
    
    $downloadedImagePath = $s->downloadProductImage();
    $sexyImagePath = $i->createImage(
      $s->getSetting("width"),
      $s->getSetting("height"),
      $s->getSetting("mediaType"),
      $s->getSetting("reflection"),
      $s->getSetting("backgroundColor"),
      $s->getImagePath(),
      $downloadedImagePath);
    
    $t = new TinyPngApi();
    $tinyImagePath = $t->getTinyPng($sexyImagePath);
    $saved = $i->saveImage($tinyImagePath, $s->getS3ImagePath());
    $i->cleanup($downloadedImagePath, $sexyImagePath, $tinyImagePath);
    
    if ($saved)
      header("Location: http://". $s3bucket ."/". str_replace("%2F","/",urlencode($s->getS3ImagePath())));
    else
      throw new HttpException(500, "Unkown error");
  }
  else
  {
    header("Location: http://". $s3bucket ."/". str_replace("%2F","/",urlencode($s->getS3ImagePath())));
  }
}
catch (HttpException $e)
{
  header($e->getHeader());
  echo "Sorry, the server encountered an internal error that prevent it from fulfilling this request!\r\n";
  echo " Reason: ". $e->getMessage();
}
?>
