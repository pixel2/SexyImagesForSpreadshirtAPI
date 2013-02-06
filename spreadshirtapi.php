<?php
class HttpException extends Exception 
{
  public function __construct($headerCode, $message)
  {
    $this->headerCode = $headerCode;
    $this->message = $message;
  }
  
  private $headerCode;
  
  public function getHeader()
  {
    if ($this->headerCode == 404)
    {
      return "HTTP/1.1 404 Not Found";
    }
    else if ($this->headerCode == 400)
    {
      return "HTTP/1.1 400 Bad Request";
    }
    else if ($this->headerCode == 415)
    {
      return "HTTP/1.1 415 Unsupported Media Type";
    }
    else if ($this->headerCode == 500)
    {
      return "HTTP/1.1 500 Internal Server Error";
    }
    
    return "HTTP/1.1 501 Not Implemented";
  }
}

class SpreadShirtApi
{
  private $locale = "en_SE";
  
  public $defaultSettings;
  public $userDefinedSettings;
 
  public function __construct()
  {
    $this->defaultSettings = array(
      "view" => 1,
      "appearanceId" => 1,
      "mediaType" => "png",
      "width" => 300,
      "height" => 300,
      "backgroundColor" => "white",
      "reflection" => true);
    
    $this->setUserDefinedSettings("shopId", "integer");
    $this->setUserDefinedSettings("productId", "integer");
    $this->setUserDefinedSettings("view", "integer");
    $this->setUserDefinedSettings("appearanceId", "integer");
    
    $this->setUserDefinedSettings("height", "integer");
    $this->setUserDefinedSettings("width", "integer");
    
    if ($this->isSettingUserDefined("height") && !$this->isSettingUserDefined("width"))
      $this->setDefaultSettings("width", "integer", $this->getSetting("height"));
    
    if ($this->isSettingUserDefined("width") && !$this->isSettingUserDefined("height"))
      $this->setDefaultSettings("height", "integer", $this->getSetting("width"));  
     
    $this->setUserDefinedSettings("mediaType");
    if ($this->isSettingUserDefined("mediaType"))
      $this->userDefinedSettings["mediaType"] = strtolower($this->getSetting("mediaType"));
    
    $this->setUserDefinedSettings("reflection", "boolean");
    $this->setUserDefinedSettings("backgroundColor");
  }
  
  public function validate() 
  {
    if (!$this->hasSettingValue("productId")) 
    {
      throw new HttpException(400, "You need to supply the product-id.");
    }
    
    $_currentMediaType = strtolower($this->getSetting("mediaType"));
    $_allowedMediaTypes = array("png","jpg","jpeg","gif");
    if (!in_array($_currentMediaType, $_allowedMediaTypes))
    {
      throw new HttpException(415, "The requested format is not supported by the given resource.");
    }
    
    if ($this->getSetting("width") > 1200 ||
        $this->getSetting("height") > 1200)
    {
      throw new HttpException(400, "Your request contains a forbidden image size.");
    }
  }
  
  public function getSetting($key)
  {
    if (array_key_exists($key, $this->userDefinedSettings))
    {
      return $this->userDefinedSettings[$key];
    }
    else if (array_key_exists($key, $this->defaultSettings))
    {
      return $this->defaultSettings[$key];
    }
    
    return null;
  }
  
  public function hasSettingValue($key)
  {
    $setting = $this->getSetting($key);
    return !empty($setting);
  }
  
  public function isSettingUserDefined($key)
  {
    return array_key_exists($key, $this->userDefinedSettings);
  }
  
  private function setDefaultSettings($key, $type=null, $manualValue=null)
  {
    $this->setSettings($this->defaultSettings, $key, $type, $manualValue);
  }
  
  private function setUserDefinedSettings($key, $type=null, $manualValue=null)
  {
    $this->setSettings($this->userDefinedSettings, $key, $type, $manualValue);
  }
  
  private function setSettings(&$settingsDictionary, $key, $type=null, $manualValue=null) 
  {      
    if (isset($_GET[$key]) || isset($manualValue))
    { 
      $value = !isset($manualValue) ? trim($_GET[$key]) : trim($manualValue);
      
      if (preg_match("/\.(?:png|jpe?g|gif)/i",trim($value), $matches))
      {
        $settingsDictionary["mediaType"] = ltrim($matches[0],".");
        $value = str_replace($matches[0], "", $value);
      }
       
      if ($type == "integer" && is_numeric($value))
      {
        $settingsDictionary[$key] = (int)$value;
      }
      else if ($type == "float" && is_numeric($value)) 
      { 
        $settingsDictionary[$key]  = (float)$value;
      }
      else if ($type == "boolean" && is_numeric($value))
      {
        $settingsDictionary[$key] = (boolean)$value;
      } 
      else if ($type == "boolean")
      {
        $settingsDictionary[$key] = strtolower($value) == "true" ? true : false;
      }
      else if (is_null($type) || $type == "string")
      {
        $settingsDictionary[$key]  = $value;
      }
    }
  }
  
  public function getDefaultSettings()
  { 
    $productInfoUri = "http://api.spreadshirt.net/api/v1/";
    
    if ($this->hasSettingValue("shopId"))
      $productInfoUri .= sprintf("shops/%s/", $this->getSetting("shopId"));
    
    $productInfoUri .= sprintf("products/%s?locale=%s&mediaType=json",
      $this->getSetting("productId"), $this->locale);
    
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $productInfoUri);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    
    if (($result = curl_exec($curl)))
    { 
      $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
      if ($httpCode == 200)
      {
        $json = json_decode($result, true);
        $this->setDefaultSettings("appearanceId", "integer", $json["appearance"]["id"]);
        
        if (!$this->isSettingUserDefined("view")) 
          $this->setDefaultSettings("view", "integer", $json["defaultValues"]["defaultView"]["id"]);
      }
      else if ($httpCode == 404)
      {
        throw new HttpException(404, sprintf("Could not retrieve entity or list. See cause for further information. Product %s does not belong to given shop %s and can thus not be returned!", $this->getSetting("productId"), $this->getSetting("shopId")));
      }
      else if ($httpCode == 500)
      {
        throw new HttpException(500, sprintf("Shop %s does not exist!", $this->getSetting("userId"), $this->getSetting("shopId")));
      }
      else
      {
        throw new HttpException($httpCode, "Unknown error");
      }
    }
    else
    {
      throw new HttpException(500, "Could not connect to spreadshirt API");
    }
    
    curl_close($curl);
  }
  
  public function downloadProductImage()
  {
    $productImageUri = sprintf("http://image.spreadshirt.net/image-server/v1/products/%s/views/%s?width=1200&height=1200&mediaType=png",
      $this->getSetting("productId"), $this->getSetting("view"));
    
    if ($this->hasSettingValue("appearanceId"))
      $productImageUri .= sprintf("&appearanceId=%s", $this->getSetting("appearanceId"));
    
    $tmpImagePath = sprintf("pictures/tmp/%s-original.png", time() * rand(2,10));
    
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $productImageUri);
    curl_setopt($curl, CURLOPT_FAILONERROR, true);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    
    if (($result = curl_exec($curl)))
    { 
      $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
      if ($httpCode == 200)
        file_put_contents($tmpImagePath, $result);
      else
        throw new HttpException(404, sprintf("Could not find product image at %s", $productImageUri)); 
      }
    else
    {
      throw new HttpException(404, sprintf("Could not find product image at %s", $productImageUri));
    }
    
    curl_close($curl);
    return $tmpImagePath;
  }
  
  public function getImagePath()
  {
    return sprintf("pictures/%s/%s.png", $this->getSetting("view"), $this->getSetting("appearanceId") );
  }
  
  public function getS3ImagePath() 
  {    
    $path = "image-server/v1";
    $path_parameters = array();
    
    if ($this->isSettingUserDefined("shopId"))
      $path .= sprintf("/shops/%s", $this->getSetting("shopId"));
          
    $path .= sprintf("/products/%s/views/%s", $this->getSetting("productId"), $this->getSetting("view"));
    
    if ($this->isSettingUserDefined("appearanceId"))
      array_push($path_parameters, sprintf("appearanceId=%s", $this->getSetting("appearanceId")));
    
    if ($this->isSettingUserDefined("height"))
      array_push($path_parameters, sprintf("height=%s", $this->getSetting("height")));
      
    if ($this->isSettingUserDefined("width"))
      array_push($path_parameters, sprintf("width=%s", $this->getSetting("width")));
    
    if ($this->isSettingUserDefined("backgroundColor") && $this->getSetting("mediaType") != "png")
      array_push($path_parameters, sprintf("backgroundColor=%s", $this->getSetting("backgroundColor")));
    
    if ($this->isSettingUserDefined("reflection"))
      array_push($path_parameters, sprintf("reflection=%s", $this->getSetting("reflection")));
    
    if (!empty($path_parameters))
      $path .= sprintf("?%s", implode("&", $path_parameters));
    
    $path .= sprintf(".%s", $this->getSetting("mediaType"));
    
    return $path;
  }
}
?>
