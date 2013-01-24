SexyImagesForSpreadshirtAPI
===========================

Extension of the Spreadshirt Image API to remove the shadow and add a reflection. Uses tinypng.org API to compress the completed file and caches to Amazon S3.

You will need an Amazon S3 account for file storage and API access to tinypng.org (currently in private beta)

Install
-------
- Create a file called apisettings.php
- Set the following variables

```
    $s3awsAccessKey = "";
    $s3awsSecretKey = "";
    $s3bucket = "";
  
    $tinyPngApiKey = "";
```
