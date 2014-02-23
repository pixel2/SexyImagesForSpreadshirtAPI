<?php
class TinyPngApi
{
  private function getLocation($response, $curl) 
  {
    $headers = substr($response, 0, curl_getinfo($curl, CURLINFO_HEADER_SIZE));
    foreach (explode("\r\n", $headers) as $header) {
      if (substr($header, 0, 10) === "Location: ") {
        return substr($header, 10);
      }
    }
  }

  private function downloadTinyPng($url, $tmpImagePath)
  {
    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => $url,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_SSL_VERIFYPEER => true
    ));

    return file_put_contents($tmpImagePath, curl_exec($curl));
  }

  public function getTinyPng($image)
  {
    $tinyPngApiKey = getenv('tinyPngApiKey');

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api.tinypng.com/shrink",
        CURLOPT_USERPWD => "api:" . $tinyPngApiKey,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_BINARYTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_POST => true,
        CURLOPT_HEADER => true,
        CURLOPT_POSTFIELDS => file_get_contents($image)));

    $tmpImagePath = sprintf("pictures/tmp/%s-tiny.png", time() * rand(2,10));

    $response = curl_exec($curl);
    var_dump($response);
    if (curl_getinfo($curl, CURLINFO_HTTP_CODE) === 201)
    {
      if ($this->downloadTinyPng($this->getLocation($response, $curl), $tmpImagePath))
      {
        return $tmpImagePath;
      }
    }
    
    return $image;
  }
}
?>
