<?php

namespace App\Util;

class Date implements SharedObject
{
    /**
     *
     * @return int
     * @codeCoverageIgnore
     */
    public function currentTime()
    {
        return strtotime(date('Y-m-d H:i:s'));
    }

    /**
     * @return string
     * @codeCoverageIgnore
     */
    public function currentDateTimeString()
    {
        return date('Y-m-d H:i:s');
    }

    /**
     * @return string
     * @codeCoverageIgnore
     */
    public function currentDateString()
    {
        return date('Y-m-d');
    }
    
    public function timeElapsed($datetime, $full = false) {
        $now = new \DateTime;
        $ago = new \DateTime($datetime);
        $diff = $now->diff($ago);
    
        $diff->w = floor($diff->d / 7);
        $diff->d -= $diff->w * 7;
    
        $string = array(
            'y' => 'year',
            'm' => 'month',
            'w' => 'week',
            'd' => 'day',
            'h' => 'hour',
            'i' => 'minute',
            's' => 'second',
        );
        foreach ($string as $k => &$v) {
            if ($diff->$k) {
                $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
            } else {
                unset($string[$k]);
            }
        }
    
        if (!$full) $string = array_slice($string, 0, 1);
        return $string ? implode(', ', $string) . ' ago' : 'just now';
    }
}
