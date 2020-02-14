<?php

namespace App\Util;

trait WithParsedown
{   
    private $replaceTags;
    private $coolTags;
    
    protected function convertTagsToPlaceholders($text)
    {
        
        // Find all tags (#tag no space between hash and word) and use a 
        // placeholder to replace them, before converting them to headings.
        $origTags = [];
        $this->replaceTags = [];
        $this->coolTags = [];
        
        preg_match_all("/[^&]{1}(#[\w\d\-]+)/", $text, $matches);
        
        if (isset($matches[1])) {
            foreach ($matches[1] as $i => $tag) {
                if (is_numeric($tag)) continue;
                if (strlen($tag) == 0) continue;
                $origTags[] = $tag;
                $tagLower = strtolower($tag);
                $tagWord = substr($tagLower, 1);
                $this->replaceTags[] = $replace = "@@TAG@PLACEHOLDER@{$i}@@";
                $this->coolTags[] = "<span class=\"chip\">$tagLower</span>";
                $text = preg_replace("/$tag/", $replace, $text, 1);
            }
        }
        
        return $text;
    }
    
    protected function convertTagPlaceholdersToCoolTags($text)
    {
        $text = str_replace($this->replaceTags, $this->coolTags, $text);
        return $text;
    }
    
    protected function parse($text)
    {
        static $num = 1;
        
        $this->parsedown->setSafeMode(true);
        
        // Usually `#word` means heading, but we'll treat it as a tag
        $text = preg_replace("/^([^&]{1})#([\w\d\-]+)/m", "$1__LEADING_TAG__$2", $text);
        
        $text = $this->parsedown->parse($text);    

        $text = preg_replace("/__LEADING_TAG__/m", "#", $text);
        
        // find all invoices
        
        preg_match_all("/(ln[bc|tb][\w\d]+)/", $text, $matches);
        
        if (isset($matches[1])) {
            foreach ($matches[1] as $invoice) {
                
                if (strlen($invoice) < 30) {
                    continue;
                }
                
//                $invoiceButton = file_get_contents(__DIR__ . '/../View/payment/invoice-button.phtml');
//                $invoiceButton = str_replace('{{invoice}}', '$1', $invoiceButton);
//                $invoiceButton = str_replace('{{num}}', $num, $invoiceButton);

//                $text = preg_replace("/($invoice)/", $invoiceButton, $text, 1, $count);
                
                $invoiceInput = "<input type='text' readonly='true' value='$invoice' />";
                
                $text = preg_replace("/($invoice)/", $invoiceInput, $text, 1, $count);
                
                $num++;
                
            }
        }
        
        // search for long words, excluding long links
        
        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$text);
        $links = $dom->getElementsByTagName('a');
        
        $linklessText = $text;
        $placeholders = [];
        $originalLinks = [];
        foreach ($links as $i => $link) {
            /* @var \DOMNode $link */
            //Extract and show the "href" attribute.
            $originalLinks[] = $link->getAttribute('href');
            $link->setAttribute('href', "__LINK-PLACEHOLDER-{$i}__");
            $link->setAttribute('target', "_blank");
            $link->setAttribute('rel', "noopener");
            
            $icon = $dom->createElement('i');
            $icon->setAttribute('class', 'fas fa-external-link-alt');
            
            $originalLinks[] = $link->nodeValue;
            
            $link->nodeValue = "__LINKVALUE-PLACEHOLDER-{$i}__" . "&nbsp;";
            
            $link->appendChild($icon);
            
            $placeholders[] = "__LINK-PLACEHOLDER-{$i}__";
            $placeholders[] = "__LINKVALUE-PLACEHOLDER-{$i}__";
        }
        
        $inputs = $dom->getElementsByTagName('input');
        foreach ($inputs as $i => $input) {
            
            $originalLinks[] = $input->getAttribute('value');
            $input->setAttribute('value', "__INPUT-PLACEHOLDER-{$i}__");
            $placeholders[] = "__INPUT-PLACEHOLDER-{$i}__";
            
        }
        
        $images = $dom->getElementsByTagName('img');
        foreach ($images as $i => $image) {
            
            $originalLinks[] = $image->getAttribute('src');
            $image->setAttribute('src', "__IMG-PLACEHOLDER-{$i}__");
            $placeholders[] = "__IMG-PLACEHOLDER-{$i}__";
            
        }
        
        $text = $dom->saveHTML();
        
        $origWords = [];
        $replaceWords = [];
        preg_match_all("/(\w+)/", $text, $matches);
        
        if (isset($matches[1])) {
            foreach ($matches[1] as $word) {
                if (strlen($word) >= 20) {
                    $origWords[] = $word;
                    $split = str_split($word, 20);
                    $replaceWords[] = implode("<wbr />", $split);
                }
            }
        }
        
        $text = $this->convertTagsToPlaceholders($text);
        $text = $this->convertTagPlaceholdersToCoolTags($text);
        
        $text = str_replace($origWords, $replaceWords, $text);

        $text = str_replace($placeholders, $originalLinks, $text);
        
        
        $text = str_replace('<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">
<?xml encoding="utf-8" ?><html><body>', '', $text);
        
        $text = str_replace('</body></html>', '', $text);
        
        //$text = preg_replace("#\n#", "<br>", $text);
        //$text = preg_replace("#li><br>#", "> ", $text);
        //$text = preg_replace("#ul><br>#", "ul> ", $text);
        //$text = preg_replace("#ol><br>#", "ol> ", $text);
        //$text = preg_replace("#/p><br>#", "/p> ", $text);
        return $text;
    }
}
