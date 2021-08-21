<?php

class DomDocumentParser {

  private $doc;

  public function __construct($url) {
    // echo "URL: $url";

    $options = array(
      'http' => array('method' => "GET", 'header'=> "User-Agent: doodleBot/0.1\n") // User Agent is who requested it
    );

    $context = stream_context_create($options);

    $this->doc = new DomDocument();
    @$this->doc -> loadHTML(file_get_contents($url, false, $context)); // false if want to use include path or not and @ means dont show any warnings
  }

  public function getLinks() {
    return $this->doc->getElementsByTagName("a");
  }

  public function getTitletags() {
    return $this->doc->getElementsByTagName("title");
  }

  public function getMetatags() {
    return $this->doc->getElementsByTagName("meta");
  }

  public function getImages() {
    return $this->doc->getElementsByTagName("img");
  }

}

?>