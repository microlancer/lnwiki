<?php

namespace App\Util;

use App\Util\Di;
use App\Util\HeaderParams;
use App\Util\Logger;

/**
 * @codeCoverageIgnore
 */
class Route implements SharedObject
{
    private $resources;
    private $headers;
    private $logger;

    public function __construct(HeaderParams $headers, Logger $logger)
    {
        $this->resources = [];
        $this->headers = $headers;
        $this->logger = $logger;
    }

    public function addResources(array $resources)
    {
        $this->resources = $resources;
    }

    public function dispatch($unfilteredRequestParams)
    {
        if (!isset($unfilteredRequestParams['q'])) {
            $route = 'index/index';
        } else {
            $route = $unfilteredRequestParams['q'];
        }

        $unfilteredRequestParams['route'] = $route;
        
        $rawBody = file_get_contents('php://input');
        
        if (!empty($rawBody)) {
            $unfilteredRequestParams['rawBody'] = $rawBody;
        }

        $routeParts = explode('/', $route);       

        if (isset($routeParts[2])) {
            $moduleName = Route::toModuleName($routeParts[0]);
            $controllerName = Route::toControllerName($routeParts[1]);
            $actionName = Route::toControllerActionName($routeParts[2]);
            $fullyQualifiedControllerName = "App\Controller\\$moduleName\\$controllerName";
        } elseif (isset($routeParts[1]) && !empty($routeParts[1])) {
            $controllerName = Route::toControllerName($routeParts[0]);
            $actionName = Route::toControllerActionName($routeParts[1]);
            $fullyQualifiedControllerName = "App\Controller\\$controllerName";
        } else {
            $controllerName = Route::toControllerName($routeParts[0]);
            $actionName = Route::toControllerActionName('index');
            $fullyQualifiedControllerName = "App\Controller\\$controllerName";
        }

        if (!class_exists($fullyQualifiedControllerName)) {
            return $this->dispatch(['q' => 'page/index', 'name' => $routeParts[0], 'op' => $routeParts[1]]);
//            $this->headers->setResponseCode(404);
//            echo '404 Not Found';
//            $this->logger->log('404 for controller: ' . $fullyQualifiedControllerName);
//            return;
        }

        $controller = Di::getInstance()->get($fullyQualifiedControllerName);

        if (method_exists($controller, 'preDispatch')) {
            $continue = call_user_func([$controller, 'preDispatch'], $unfilteredRequestParams);
        } else {
            $continue = false;
        }

        if (!$continue) {
            return;
        }

        if (method_exists($controller, $actionName)) {
            $this->logger->log("Route::dispatch to $fullyQualifiedControllerName::$actionName");
            call_user_func([$controller, $actionName], $unfilteredRequestParams);
        } else {
            return $this->dispatch(['q' => 'index/index']);
//            $this->headers->setResponseCode(404);
//            echo '404 Not Found';
//            $this->logger->log('404 for controller: ' . $controllerName . ' action: ' . $actionName);
        }
    }

    public function toModuleName($str)
    {
        $routeWords = explode('-', $this->filterString($str));
        foreach ($routeWords as $routeWord) {
            $moduleWords[] = ucfirst($routeWord);
        }
        return implode('', $moduleWords);
    }

    public function toControllerName($str)
    {
        $routeWords = explode('-', $this->filterString($str));
        foreach ($routeWords as $routeWord) {
            $controllerWords[] = ucfirst($routeWord);
        }
        return implode('', $controllerWords) . 'Controller';
    }

    public function toControllerActionName($str)
    {
        $routeWords = explode('-', $this->filterString($str));
        foreach ($routeWords as $i => $routeWord) {
            if ($i == 0) {
                $actionWords[] = $routeWord;
            } else {
                $actionWords[] = ucfirst($routeWord);
            }
        }
        return implode('', $actionWords) . 'Action';
    }

    private function filterString($str)
    {
        return preg_replace('/[^a-zA-Z0-9-]/', '', $str);
    }
}
