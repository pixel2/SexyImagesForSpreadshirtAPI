<?php
require_once("apisettings.php");

class TinyPngApi
{ 
  public function getTinyPng($image)
  {
    global $tinyPngApiKey;
    $curl = curl_init();
    
    curl_setopt($curl, CURLOPT_URL, "http://api.tinypng.org/api/shrink");
    curl_setopt($curl, CURLOPT_USERPWD, "api:" . $tinyPngApiKey);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_BINARYTRANSFER, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, file_get_contents($image));
      
    $tmpImagePath = sprintf("pictures/tmp/%s-tiny.png", time() * rand(2,10));
    $result = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    if ($result && $httpCode == 200)
    { 
      $json = json_decode($result, true);
      if ($this->downloadTinyPng($json["output"]["url"], $tmpImagePath))
        return $tmpImagePath;
    }
   
    return $image;
  }
  
  public function downloadTinyPng($url, $tmpImagePath)
  {
    global $tinyPngApiKey;
    $curl = curl_init();
    
    curl_setopt($curl, URLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_USERPWD, "api:" . $tinyPngApiKey);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    
    $result = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    if ($result && $httpCode == 200)
    { 
      file_put_contents($tmpImagePath, $result);
      return true;
    }
    
    return false;
  }
}
?>
