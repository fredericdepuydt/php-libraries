<?php namespace Feed\RSS;

/**
 * RSS Entry Class.
 */
class Item {

    protected $author;
    protected $category;
    protected $copyright;
    protected $title;
    protected $description;
    protected $guid;
    protected $lastBuildDate;
    protected $pubDate;
    protected $managingEditor;
    protected $link;
    protected $comments;
    protected $ttl;
    protected $image;
    protected $hidden;

    function __construct($_item){
        $this->author = $_item['author'];
        $this->category = $_item['category'];
        $this->copyright = $_item['copyright'];
        $this->title = $_item['title'];
        $this->description = $_item['description'];
        $this->guid = $_item['guid'];
        $this->lastBuildDate = $_item['lastBuildDate'];
        $this->pubDate = $_item['pubDate'];
        $this->managingEditor = $_item['managingEditor'];
        $this->link = $_item['link'];
        $this->comments = $_item['comments'];
        $this->image = $_item['image'];
        $this->hidden = $_item['hidden'];
    }

    public function post_processing(){
        if(!isset($this->lastBuildDate)){
            $this->lastBuildDate = $this->pubDate;
        }
    }

    public function store($_db){
        $sql = "INSERT INTO `items`
                    (`author`, `category`, `copyright`,
                     `title`, `description`, `guid`, `lastBuildDate`,
                     `pubDate`, `managingEditor`, `link`, `comments`,
                     `image`, `hidden`)
                VALUES  ('".$_db->escape($this->author)."',  '".$_db->escape($this->category)."',       '".$_db->escape($this->copyright)."',
                         '".$_db->escape($this->title)."',   '".$_db->escape($this->description)."',    '".$_db->escape($this->guid)."', '".$_db->escape($this->lastBuildDate)."',
                         '".$_db->escape($this->pubDate)."', '".$_db->escape($this->managingEditor)."', '".$_db->escape($this->link)."', '".$_db->escape($this->comments)."',
                         '".$_db->escape($this->image)."',   '".$_db->escape($this->hidden)."');";
        try{
            $_db->query($sql);
        }catch(\Database\MySQLException $e){
            switch($e->getCode()){
                case \Database\MySQLException::ER_DUP_ENTRY:
                    break;
                default:
                    throw $e;
            }
        }
    }

    public function echo(){
        $str = "        <item>\n";
        $str.= (isset($this->title)&&$this->title!=""?"            <title>".htmlspecialchars($this->title)."</title>\n":"");
        $str.= (isset($this->link)&&$this->link!=""?"            <link>".$this->link."</link>\n":"");
        $str.= (isset($this->description)&&$this->description!=""?"            <description>".htmlspecialchars($this->description)."</description>\n":"");
        $str.= (isset($this->author)&&$this->author!=""?"            <author>".$this->author."</author>\n":"");
        $str.= (isset($this->category)&&$this->category!=""?"            <category>".htmlspecialchars($this->category)."</category>\n":"");
        $str.= (isset($this->comments)&&$this->comments!=""?"            <comments>".$this->comments."</comments>\n":"");
        $str.= (isset($this->guid)&&$this->guid!=""?"            <guid>".$this->guid."</guid>\n":"");
        $str.= (isset($this->pubDate)&&$this->pubDate!=""?"            <pubDate>".date('r', strtotime($this->pubDate))."</pubDate>\n":"");
        $str.= (isset($this->image)&&$this->image!=""?"            <enclosure url=\"".htmlspecialchars($this->image)."\" length=\"128000\" type=\"image/jpeg\" />\n":"");
        $str.= "        </item>\n";
        return $str;
    }
}