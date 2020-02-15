<?php

namespace App\Model;

use App\Util\Config;
use App\Util\Mysql;
use App\Model\Invoice;
use App\Model\Factory\Invoice as InvoiceFactory;
use App\Model\InvoiceList;
use App\Model\Cache\User as UserCache;

class Invoices
{
    private $mysql;
    private $config;
    private $invoiceFactory;
    private $userCache;
    
    public function __construct(Mysql $mysql, Config $config, 
        InvoiceFactory $invoiceFactory, UserCache $userCache)
    {
        $this->mysql = $mysql;
        $this->config = $config;
        $this->invoiceFactory = $invoiceFactory;
        $this->userCache = $userCache;
    }
    
    public function saveInvoice(Invoice $invoice)
    {
        if (!isset($invoice->id)) {
            $query = 'insert into invoices ' .
                '(userId, postId, offerId, tipId, boostId, bolt11, label, status, createdTs, updatedTs) values ' .
                '(?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())';

            $params = [
                $invoice->userId, 
                $invoice->postId, 
                $invoice->offerId,
                $invoice->tipId,
                $invoice->boostId,
                $invoice->bolt11,
                $invoice->label,
                $invoice->status
            ];
                
            $added = $this->mysql->query($query, 'iiiiissi', $params);

            if ($added != 1) {
                throw new \Exception('Failed to add invoice record');
            }

            $invoice->id = $this->mysql->getLastId();
            
        } else {
            
            $set = [];
            $params = [];

            $typesMap = [
                'userId' => 'i',
                'postId' => 'i',
                'offerId' => 'i',
                'tipId' => 'i',
                'boostId' => 'i',
                'bolt11' => 's',
                'label' => 's',
                'status' => 'i',
            ];

            $types = [];

            foreach ($invoice->getModifiedProperties() as $property) {
                $set[] = "$property = ?";
                $types[] = $typesMap[$property];
                $params[] = $invoice->$property;
            }

            if (!empty($set)) {
                
                $params[] = $invoice->id;
                $types[] = 'i';
    
                $query = "update invoices set " . implode(", ", $set) . " where id = ?";
    
                $updated = $this->mysql->query($query, implode('', $types), $params);
    
                if ($updated != 1) {
                    throw new \Exception('Failed to update invoice record');
                }
                
            }
        }
        
        $this->userCache->delete($invoice->userId);
    }
    
    public function loadInvoiceList(InvoiceList $invoiceList)
    {
        $types = [];
        $params = [];
        $where = [];
        
        if (!empty($invoiceList->userId)) {
            $where[] = 'userId = ?';
            $types[] = 'i';
            $params[] = $invoiceList->userId;
        }
        
        if (!empty($invoiceList->postId)) {
            $where[] = 'postId = ?';
            $types[] = 'i';
            $params[] = $invoiceList->postId;
        }
        
        if (!empty($invoiceList->offerId)) {
            $where[] = 'offerId = ?';
            $types[] = 'i';
            $params[] = $invoiceList->offerId;
        }
        
        if (!empty($invoiceList->tipId)) {
            $where[] = 'tipId = ?';
            $types[] = 'i';
            $params[] = $invoiceList->tipId;
        }
        
        if (!empty($invoiceList->boostId)) {
            $where[] = 'boostId = ?';
            $types[] = 'i';
            $params[] = $invoiceList->boostId;
        }
        
        if (!empty($invoiceList->statuses)) {
            $subWhere = [];
            foreach ($invoiceList->statuses as $status) {
                $subWhere[] = "status = ?";
                $types[] = "i";
                $params[] = $status;
            }
            $where[] = '(' . implode(" or ", $subWhere) . ')';
        }
        
        $where = implode(' and ', $where);
    
        $types = implode('', $types);
        
        $query = 'select id, userId, postId, offerId, tipId, boostId, bolt11, label, status, createdTs, updatedTs ' . 
            'from invoices ' .
            "where $where order by status desc";
            
        $rows = $this->mysql->query($query, $types, $params);
        
        foreach ($rows as $row) {
            $invoice = $this->invoiceFactory->createInvoice();
            $invoice->init($row);
            $invoiceList->addInvoice($invoice);
        }
    }
}
