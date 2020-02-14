<?php

namespace App\Util;

class Logger implements SharedObject
{
    const DEBUG = 'DEBUG';
    const INFO = 'INFO';
    const WARN = 'WARN';
    const ERROR = 'ERROR';

    /** @var Gearman */
    private $gearman;

    private $context;

    public function __construct(Gearman $gearman)
    {
        $this->context = [];
        $this->gearman = $gearman;
    }

    public function setContext(array $context)
    {
        $this->context = $context;
    }

    public function log($msg, $level = self::DEBUG, array $data = [], $withTrace = false)
    {
        $payload = [
            'ts' => date('Y-m-d H:i:s'),
            'level' => $level,
            'msg' => $msg,
        ] + $this->context;

        if ($withTrace) {
            $payload['trace'] = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        }

        if (!empty($data)) {
            $payload['data'] = $data;
        }

        $this->gearman->doBackground(Gearman::LOG, $payload);
    }

    public function logTimerEnd($msg, $startTime)
    {
        $seconds = sprintf("%.4f", microtime(true) - $startTime);
        $this->log($msg, self::DEBUG, ['timeElapsedSeconds' => $seconds]);
    }

    /**
     *
     * @param array $payload
     * @codeCoverageIgnore
     */
    public function logToFileJob(array $payload)
    {
        $log = json_encode($payload) . "\n";
        file_put_contents(__DIR__ . '/../../logs/app.log', $log, FILE_APPEND);
    }

    public function logRemoteInfo()
    {
        $this->log('Remote info', self::DEBUG, $_SERVER);
    }
}
