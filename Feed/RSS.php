<?php namespace Feed;

/************************************/
/*** Author: Frederic Depuydt     ***/
/*** Mail: f.depuydt@outlook.com  ***/
/************************************/

/**
 * RSS Feed Class.
 */
class RSS {

    protected $document;
    private $items;
    protected $db;

    public function __construct(){
        $this->document = new \DOMDocument();
        $this->db = new \Database\MySQL();
        $sql = "INSERT INTO `logs`(`time`, `ip`) VALUES ('".date('Y-m-d H:i:s')."','".$_SERVER['REMOTE_ADDR']."');";
        $this->db->query($sql);
    }

    public function __init(){
        $sql = "CREATE TABLE IF NOT EXISTS `items` (
            `id` int(11) NOT NULL,
            `author` tinytext NOT NULL,
            `category` tinytext NOT NULL,
            `copyright` tinytext NOT NULL,
            `title` text NOT NULL,
            `description` text NOT NULL,
            `guid` tinytext NOT NULL,
            `lastBuildDate` tinytext NOT NULL,
            `pubDate` tinytext NOT NULL,
            `managingEditor` tinytext NOT NULL,
            `link` tinytext NOT NULL,
            `comments` text NOT NULL,
            `image` tinytext NOT NULL,
            `hidden` tinyint(1) NOT NULL DEFAULT '0'
        );";
        $this->db->query($sql);
    }

    public function load($_url){
        $this->document->load($_url);

        foreach($this->document->childNodes as $node) {
            switch($node->nodeName){
                case "feed":
                    foreach($node->childNodes as $item) {
                        if($item->nodeName == "entry"){
                            $tmp = new RSS\Atom10($item);
                            $tmp->post_processing();
                            $this->items[] = $tmp;
                        }
                    }
                    break;
                case "rss":
                    if($node->childNodes->length == 1 && $node->childNodes[0]->nodeName == "channel"){
                        foreach($node->childNodes[0]->childNodes as $item) {
                            if($item->nodeName == "item"){
                                $tmp = new RSS\RSS2($item);
                                $tmp->post_processing();
                                $this->items[] = $tmp;
                            }
                        }
                    }else{
                        throw new \Exception("Unknown node in rss root node: " . $node->childNodes[0]->nodeName);
                    }
                    break;
                case "xml-stylesheet":
                    break;
                default:
                    throw new \Exception("Unknown root node: " . $node->nodeName);
                    break;
            }
        }

        /*$items = $this->document->getElementsByTagName("entry");
        echo "Starting...\n";
        foreach($items as $item) {
            $this->items[] = new RSS\Item\Atom10($item);
        }
        echo "Stopping...\n";*/
    }

    public function store(){
        //var_dump($this->items);
        foreach($this->items as $item) {
            $item->store($this->db);
        }
    }

    public function retrieve($_limit = 100){
        $sql = "SELECT * FROM `items` WHERE `hidden` = 0 ORDER BY `lastBuildDate` DESC LIMIT ".$_limit.";";
        $items = $this->db->select($sql);
        foreach($items as $item){
            $this->items[] = new RSS\Item($item);
        }
    }

    public function echo(){
        $str = "";
        foreach($this->items as $item){
            $str .= $item->echo();
        }
        return $str;
    }
}