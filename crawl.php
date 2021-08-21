<?php

include("config.php");
include("classes/DomDocumentParser.php");

$alreadyCrawled = array();
$crawling = array();
$alreadyFoundImages = array();

function linkExists($url){
  global $con;

  $query = $con->prepare("SELECT * FROM sites WHERE url = :url"); // placeholders :

  $query->bindParam(":url", $url);
  $query->execute();

  return $query->rowCount() != 0;

}

function insertLink($url, $title, $description, $keywords){
  global $con;

  $query = $con->prepare("INSERT INTO sites(url, title, description, keywords)
                          VALUES(:url, :title, :description, :keywords)"); // placeholders :

  $query->bindParam(":url", $url);
  $query->bindParam(":title", $title);
  $query->bindParam(":description", $description);
  $query->bindParam(":keywords", $keywords);

  return $query->execute(); // execute returns true or false if it works or not
}

function insertImage($url, $src, $alt, $title){
  global $con;

  $query = $con->prepare("INSERT INTO images(siteUrl, imageUrl, alt, title) VALUES (:siteUrl, :imageUrl, :alt, :title)"); // placeholders :

  $query->bindParam(":siteUrl", $url);
  $query->bindParam(":imageUrl", $src);
  $query->bindParam(":alt", $alt);
  $query->bindParam(":title", $title);

  return $query->execute(); // execute returns true or false if it works or not
}

function createLink($src, $url) {
  // echo "SRC: $src<br>";
  // echo "URL: $url<br>";

  $scheme = parse_url($url)["scheme"]; // http
  $host = parse_url($url)["host"]; // www.reecekenney.com

  if(substr($src, 0, 2) == "//"){
    $src = $scheme . ":" . $src; // scheme is https or http
  }
  else if(substr($src, 0, 1) == "/") {
    $src = $scheme . "://" . $host . $src;
  }
  else if(substr($src, 0, 2) == "./"){
    $src = $scheme . "://" . $host . dirname(parse_url($url)["path"]) . substr($src, 1); // last part to start from first character which is . and to ignore . in ./about/aboutUs.php
  }
  else if(substr($src, 0, 3) == "../"){
    $src = $scheme . "://" . $host . "/" . $src; // puts https://www.reecekenney.com/../about/aboutUs.php
  }
  else if(substr($src, 0, 5) != "https" && substr($src, 0, 4) != "http"){ // 4th number is next character, starts at 0
    $src = $scheme . "://" . $host . "/" . $src;
  }

  return $src;
}

function getDetails($url){

  global $alreadyFoundImages;

  $parser = new DomDocumentParser($url);

  $titleArray = $parser->getTitleTags();

  if(sizeof($titleArray) == 0 || $titleArray-> item(0) == NULL){
    return;
  }

  $title = $titleArray->item(0)->nodeValue;
  $title = str_replace("\n", "", $title); // replace any new lines with empty string

  if($title == ""){
    return;
  }
  // echo "URL: $url, Title: $title<br>";

  $description = "";
  $keywords = "";

  $metasArray = $parser->getMetatags();

  foreach($metasArray as $meta){

    if($meta->getAttribute("name") == "description") {
      $description = $meta->getAttribute("content");
    }

    if($meta->getAttribute("name") == "keywords") {
      $keywords = $meta->getAttribute("content");
    }
  }

  $description = str_replace("\n", "", $description);
  $keywords = str_replace("\n", "", $keywords);

  // echo "URL: $url, Description: $description, keywords: $keywords<br>";

  if(linkExists($url)) {
    echo "$url already exists<br>";
  }
  else if(insertLink($url, $title, $description, $keywords)){
    echo "SUCCESS: $url<br>";
  }
  else {
    echo "ERROR: Failed to insert $url<br>";
  }

  $imageArray = $parser->getImages();
  foreach($imageArray as $image){
    $src = $image->getAttribute("src");
    $alt = $image->getAttribute("alt");
    $title = $image->getAttribute("title");

    if(!$title && !$alt){
      continue;
    }

    $src = createLink($src, $url); // converts to absolute link from relative link

    if(!in_array($src, $alreadyFoundImages)){
      $alreadyFoundImages[] = $src; // put this in the array

      // Insert the image
      echo "INSERT: " . insertImage($url, $src, $alt, $title);

    }
  }
}

function followLinks($url) {

  global $alreadyCrawled;
  global $crawling;

  $parser = new DomDocumentParser($url);

  $linkList = $parser->getLinks();

  foreach($linkList as $link){
    $href = $link->getAttribute("href");


    if(strpos($href, "#") !== false){
      continue;
    }
    else if(substr($href, 0, 11) == "javascript:"){
      continue;
    }

    $href = createLink($href, $url);

    if(!in_array($href, $alreadyCrawled)){
      $alreadyCrawled[] = $href; // [] the next item will be equal to $href
      $crawling[] = $href;

      // Insert $href
      getDetails($href);
    }
    // else return; // prevents us gettign many results and didnt habe to wait very long to see out

    // echo $href . "<br>";
  }

  array_shift($crawling); // array_shift takes top item off

  foreach($crawling as $site){
    followLinks($site);
  }
}

// $startUrl = "http://www.reecekenney.com";
$startUrl = "http://www.wikipedia.org";
// $startUrl = "http://www.bbc.com";
followLinks($startUrl);
?>