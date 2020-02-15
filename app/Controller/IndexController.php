<?php

namespace App\Controller;

use App\Model\Factory\Page as PageFactory;
use App\Model\Pages;
use App\Model\Page;
use App\Model\ViewContext;

class IndexController extends ViewController
{
    private $pageFactory;
    private $pages;
    
    public function __construct(ViewContext $viewContext, PageFactory $pageFactory,
        Pages $pages)
    {
        parent::__construct($viewContext);
        
//        $this->authNotRequired = [
//            '',
//            'index',
//            'index/index',
//        ];
        
        $this->pageFactory = $pageFactory;
        $this->pages = $pages;
    }
    
    public function indexAction(array $params)
    {
        if (!isset($params['name'])) {
            $params['name'] = 'index'; // default page is home page
        }
        
        // Normalize the name
        
        $name = strtolower($params['name']);
        $name = str_replace(' ', '-', $name);
        $name = preg_replace('/[^a-z0-9\-]{1}/', '', $name);
        
        // Normalize the op
        
        $op = isset($params['op']) ? $params['op'] : '';
        
        if (!in_array($op, ['edit', 'history'])) {
            $op = ''; // default is view
        }
        
        if ($params['name'] != $name) {
            $url = $name . ($op ? '/' . $op : '');
            $this->headers->redirect($url);
            return;
        }
        
        $page = $this->pageFactory->createPage();
        $page->name = $name;
        $this->pages->loadActivePage($page);
        
        if (!$page->id) {
            $content = '';
            $exists = false;
        } else {
            $content = $page->content;
            $exists = true;
        }
        
        $this->view->addVars([
            'page' => $page,
            'name' => $name, 
            'op' => $op, 
            'content' => $content,
            'pageExists' => $exists,
        ]);
      
        $this->view->render('index/index');
    }
}
