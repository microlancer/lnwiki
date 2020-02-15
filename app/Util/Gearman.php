<?php

namespace App\Util;

/**
* @codeCoverageIgnore
*/
class Gearman implements SharedObject
{
    const LOG = 'log';
    const MAIL = 'mail';

    private $gearmanClient;
    private $gearmanWorker;
    private $config;
    private $di;

    public function __construct(Config $config, Di $di)
    {
        $host = $config->get('gearmanHost', '127.0.0.1');
        $port = $config->get('gearmanPort', 4730);

        $this->gearmanClient = new \GearmanClient();
        //$this->gearmanClient->addServer($host, $port);

        $this->gearmanWorker = new \GearmanWorker();
        //$this->gearmanWorker->addServer($host, $port);

        $this->addFunctions([
            self::LOG => [Logger::class, 'logToFileJob'],
            self::MAIL => [Email::class, 'sendMailJob'],
        ]);

        $this->config = $config;
        $this->di = $di;
    }

    /**
     *
     * @param type $function
     * @param array $payload
     * @return type
     * @throws \Exception
     *
     * @codeCoverageIgnore
     */
    public function doBackground($function, array $payload)
    {
        if ($this->config->get('gearmanEnabled')) {
            $ret = $this->gearmanClient->doBackground($function, json_encode($payload));

            if ($this->gearmanClient->returnCode() != GEARMAN_SUCCESS) {
                throw new \Exception('Unable to run background job');
            }
        } else {
            list($class, $method) = $this->functions[$function];
            $object = $this->di->get($class);
            $ret = call_user_func([$object, $method], $payload);
        }

        return $ret;
    }

    public function addFunctions(array $functions)
    {
        $this->functions = $functions;
        foreach ($functions as $function => $callable) {
            $this->gearmanWorker->addFunction($function, function (\GearmanJob $job) use ($callable) {
                $payload = json_decode($job->workload(), true);
                list($class, $method) = $callable;
                $object = Di::getInstance()->get($class);
                call_user_func([$object, $method], $payload);
            });
        }
    }

    public function doWork()
    {
        echo '[' . date('Y-m-d H:i:s') . "] Gearman worker is now waiting for work\n";
        while ($this->gearmanWorker->work()) {
            usleep(1000); // 1000us = 0.001s
        }
    }
}
