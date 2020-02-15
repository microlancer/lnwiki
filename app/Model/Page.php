<?php
namespace App\Model;

use App\Util\Config;
use App\Util\Date;
use App\Util\WithParsedown;
use Parsedown;

/**
 * Class Page
 * @property int $id
 * @property int $name
 * @property int $content
 * @property int $createdTs
 */
class Page
{
    use WithProperties;
    use WithParsedown;

    private $config;
    private $date;
    private $parsedown;
    
    public function __construct(Config $config, Date $date, Parsedown $parsedown)
    {
        $this->defineProperties([
            'id',
            'name',
            'content',
            'createdTs',
        ]);

        $this->config = $config;
        $this->date = $date;
        $this->parsedown = $parsedown;
        
        $this->init(['content' => '']);
    }
    
    public function getCreatedTimeElapsed()
    {
        return $this->date->timeElapsed($this->createdTs);
    }
    
    public function getContentAsHtml()
    {
        $ret = $this->parse($this->content);
        return $ret;
//        $ret = preg_replace("/\n/", "<br>", htmlentities(trim($this->content)));
//        return $ret;
    }
}
