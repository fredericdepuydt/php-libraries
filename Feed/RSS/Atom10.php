<?php namespace Feed\RSS;

/**
 * RSS Entry Class.
 */
class Atom10 extends Item {

    function __construct($_item){
        foreach($_item->childNodes as $node) {
            //echo(" ". $item->localName ."\n");
            switch($node->localName){
                case "title": // TITLE
                    //if(array_key_exists('type',$element)){
                        $type = $node->attributes->getNamedItem("type")->nodeValue;
                        switch($type){
                            case "text":
                                $this->title = $node->nodeValue;
                                break;
                            default:
                                throw new \Exception("Title type unknown: ".$type);
                                break;
                        }
                    /*}else{
                        throw new \Exception("Title has no type attribute");
                    }*/
                    break;
                case "id": // ID
                    //if(ctype_digit($element[0])){
                        $this->guid = $node->nodeValue;
                    /*}else{
                        throw new \Exception("ID is non numerical");
                    }*/
                    break;
                case "published":
                    $tmpDate = new \DateTime($node->nodeValue);
                    $this->pubDate = $tmpDate->format("Y-m-d H:i:s");
                    break;
                case "updated":
                    $tmpDate = new \DateTime($node->nodeValue);
                    $this->lastBuildDate = $tmpDate->format("Y-m-d H:i:s");
                    break;
                case "summary":
                    $type = $node->attributes->getNamedItem("type")->nodeValue;
                    switch($type){
                        case "text":
                            $this->description = $node->nodeValue;
                            break;
                        default:
                            throw new \Exception("Summary type unknown: ".$type);
                            break;
                    }
                    break;
                case "nstag":
                    //$this->nstag = new Tag($node);
                    break;
                case "nslabeltag":
                    //$this->nslabeltag = new Tag($node);
                    break;
                case "link":
                    $type = $node->attributes->getNamedItem("type")->nodeValue;
                    $rel = $node->attributes->getNamedItem("rel")->nodeValue;
                    switch($type){
                        case "text/html":
                            $this->link = $node->attributes->getNamedItem("href")->nodeValue;
                            break;
                        case "application/atom+xml":
                            //$this->image = $node->getNamedItem("href");
                            break;
                        case "image/jpeg":
                        case "image/png":
                            $this->image = $node->attributes->getNamedItem("href")->nodeValue;
                            break;
                        default:
                            throw new \Exception("Unknown link type: ". $this->rel);
                            break;
                    }
                    //$this->link[] = new Link($node);
                    break;
                default:
                    throw new \Exception("Unknown entry element: ".$node->nodeName);
                    break;
            }
        }
        return $this;
    }
}