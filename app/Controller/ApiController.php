<?php

namespace App\Controller;

use App\Model\Factory\Page as PageFactory;
use App\Model\Pages;
use App\Model\Page;
use App\Model\ViewContext;

class ApiController extends ViewController
{
    private $pageFactory;
    private $pages;
    
    public function __construct(ViewContext $viewContext, PageFactory $pageFactory,
        Pages $pages)
    {
        parent::__construct($viewContext);
        
        $this->pageFactory = $pageFactory;
        $this->pages = $pages;
    }
    
    public function checkPaymentAction(array $params)
    {
        $this->headers->setContentType(\App\Util\HeaderParams::CONTENT_TYPE_JSON);
        
        $pageId = intval($params['pageId']);
        
        echo json_encode(['pageId' => $pageId, 'status' => 'paid']);
    }
}
