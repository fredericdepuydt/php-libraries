<?php namespace Feed\RSS;

/**
 * RSS Entry Class.
 */
class RSS2 extends Item {

    function __construct($_doc){
        foreach($_doc->childNodes as $item) {
            //echo(" ". $item->localName ."\n");

            switch(strtolower($item->localName)){
                case "title": // TITLE
                    $this->title = $item->nodeValue;
                    break;
                case "guid": // ID
                    $this->guid = $item->nodeValue;
                    break;
                case "pubdate":
                    $tmpDate = new \DateTime($item->nodeValue);
                    $this->pubDate = $tmpDate->format("Y-m-d H:i:s");
                    break;
                case "lastbuilddate":
                    $tmpDate = new \DateTime($item->nodeValue);
                    $this->lastBuildDate = $tmpDate->format("Y-m-d H:i:s");
                    break;
                case "description":
                    $this->description = $item->nodeValue;
                    break;
                case "author":
                    $this->author = $item->nodeValue;
                    break;
                case "category":
                    $this->category = $item->nodeValue;
                    break;
                case "comments":
                    $this->comments = $item->nodeValue;
                    break;
                case "link":
                    $this->link = $item->nodeValue;
                    break;
                default:
                    throw new \Exception("Unknown entry element: ".$item->nodeName);
                    break;
            }
        }
        return $this;
    }
}