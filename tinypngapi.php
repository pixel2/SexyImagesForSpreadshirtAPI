<?php
require_once("apisettings.php");

class TinyPngApi
{ 
  public function getTinyPng($image)
  {
    global $tinyPngApiKey;
    $curl = curl_init();
    
    curl_setopt_array($curl, array(
      CURLOPT_URL => "http://api.tinypng.org/api/shrink",
      CURLOPT_USERPWD => "api:" . $tinyPngApiKey,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_POST           => true,
      CURLOPT_BINARYTRANSFER => true,
      CURLOPT_POSTFIELDS => file_get_contents($image)));
      
    $data = curl_exec($curl);//get curl response
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
    
    curl_setopt_array($curl, array(
      CURLOPT_URL => $url,
      CURLOPT_USERPWD => "api:" . $tinyPngApiKey,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_RETURNTRANSFER => true));
    
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
