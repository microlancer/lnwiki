<?php

namespace App\Model;

use App\Util\Config;
use App\Util\Mysql;
use App\Model\Page;
use App\Model\Factory\Page as PageFactory;
use App\Util\Logger;
use App\Model\Invoice;

class Pages
{
    private $mysql;
    private $config;
    private $pageFactory;
    private $logger;
    
    public function __construct(Mysql $mysql, Config $config, 
        PageFactory $pageFactory, Logger $logger)
    {
        $this->mysql = $mysql;
        $this->config = $config;
        $this->pageFactory = $pageFactory;
        $this->logger = $logger;
    }
    
    public function savePage(Page $page)
    {
        if (!isset($page->id)) {
            $query = 'insert into pages ' .
                '(name, content) ' . 
                'values ' .
                '(?, ?)';

            $params = [
                $page->name, $page->content
            ];
                
            $added = $this->mysql->query($query, 'ss', $params);

            if ($added != 1) {
                throw new \Exception('Failed to add page record');
            }

            $page->id = $this->mysql->getLastId();
            
        }
    }

    public function loadActivePage(Page $page)
    {

      $query = 'select p.id, p.content from pages p left join invoices i on p.id = i.pageId where p.name = ? and i.status = ? order by createdTs desc';

      $rows = $this->mysql->query($query, 's', [$page->name, Invoice::STATUS_PAID]);

      if (!empty($rows)) {
        $page->id = $rows[0]['p.id'];
        $page->content = $rows[0]['p.content'];
      }

    }
    
}
