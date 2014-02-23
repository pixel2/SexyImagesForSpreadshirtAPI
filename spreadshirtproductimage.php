<?php
require("S3.php");

class SpreadShirtProductImage
{
  private $s3;
  private $forceRefresh = false;
  
  public function __construct()
  {
    $s3awsAccessKey = getenv("s3awsAccessKey");
    $s3awsSecretKey = getenv("s3awsSecretKey");

    $this->s3 = new S3($s3awsAccessKey, $s3awsSecretKey);
    $this->s3->setEndpoint("s3-eu-west-1.amazonaws.com");
    
    if (isset($_GET["forceRefresh"]))
    {
      $this->forceRefresh = trim($_GET["forceRefresh"]) == "1" || strtolower(trim($_GET["forceRefresh"])) == "true" ? true : false;
    }
  }
  
  public function imageExists($s3path) {
    $s3bucket = getenv("s3bucket");
    if (!$this->forceRefresh && ($info = $this->s3->getObjectInfo($s3bucket , $s3path)) !== false)
      return true;
    
    return false;
  }
  
  public function saveImage($imagePath, $s3path) 
  {
    $s3bucket = getenv("s3bucket");
    $input = $this->s3->inputFile($imagePath);
    if ($this->s3->putObject(
      $input,
      $s3bucket, $s3path, 
      S3::ACL_PUBLIC_READ))
      return true;
      
    return false;
  }
  
  public function removeShadow(
    $imagePath,
    $downloadedImagePath)
  {
    $im = new Imagick($imagePath);
    $product = new Imagick($downloadedImagePath);
    $product->cropImage(550, 600, 325, 545);
    $im->compositeImage($product, imagick::COMPOSITE_OVER, 325, 545);
    
    return $im;
  }
  
  public function createImage(
    $outputWidth, 
    $outputHeight,
    $outputImageFormat,
    $outputReflection,
    $outputBackgroundColor,
    $imagePath,
    $downloadedImagePath)
  {   
    $im = $this->removeShadow($imagePath, $downloadedImagePath);
    
    if ($outputReflection)
    {
      $originalHeight = $im->getImageHeight();
      
      if ($originalHeight > $outputHeight) 
      {
        $outputShadowHeight = $outputHeight / 6;
        $outputReflectionOffset = floor($outputHeight / 18);
        $im->thumbnailImage($outputHeight - $outputShadowHeight + $outputReflectionOffset, null);
      }

      $reflection = $im->clone();
      $reflection->flipImage();

      $gradient = new Imagick();

      $shadowHeight = $outputShadowHeight;
      $shadowOffset = $shadowHeight * 3;

      $gradient->newPseudoImage($reflection->getImageWidth(), $shadowHeight + $shadowOffset, "gradient:transparent-black");
      $reflection->compositeImage($gradient, imagick::COMPOSITE_DSTOUT, 0, $shadowOffset * -1);

      $canvas = new Imagick();

      $reflectionOffset = ceil($im->getImageHeight() / 18);
      $width = $outputWidth;
      $left = ($outputWidth - $im->getImageWidth()) / 2;
      $height = $outputHeight;
      $canvas->newImage($width, $height, new ImagickPixel($this->getImageBackgroundColor($outputImageFormat, $outputBackgroundColor)));
      $canvas->setImageFormat($outputImageFormat);

      $canvas->compositeImage($im, imagick::COMPOSITE_OVER, $left, 0);
      $canvas->compositeImage($reflection, imagick::COMPOSITE_OVER, $left, $im->getImageHeight() - $reflectionOffset);
    }
    else
    {
      $im->thumbnailImage($outputHeight, null);
      $width = $outputWidth;
      $left = ($outputWidth - $im->getImageWidth()) / 2;
      $height = $outputHeight;
      
      $canvas = new Imagick();
      $canvas->newImage($width, $height, new ImagickPixel($this->getImageBackgroundColor($outputImageFormat, $outputBackgroundColor)));
      $canvas->setImageFormat($outputImageFormat);
      $canvas->compositeImage($im, imagick::COMPOSITE_OVER, $left, 0);
    }
    
    $tmpImagePath = sprintf("pictures/tmp/%s-sexy.%s", time() * rand(2,10), $outputImageFormat);
    $canvas->writeImage($tmpImagePath);
    
    return $tmpImagePath;
  }
  
  public function cleanup(
    $downloadedTmpImagePath,
    $tmpImagePath,
    $tmpTinyImagePath)
  {
    @unlink($downloadedTmpImagePath);
    @unlink($tmpImagePath);
    @unlink($tmpTinyImagePath);
  }
  
  private function getImageBackgroundColor($outputImageFormat, $backgroundColor) 
  {
    if ($outputImageFormat == "png")
      return "transparent";
    else
      return $backgroundColor;
  }
}
?>
