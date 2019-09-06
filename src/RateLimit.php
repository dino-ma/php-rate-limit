<?php
/**
 * Created by PhpStorm.
 * User: dino.ma
 * Date: 2019/9/6
 * Time: 3:25 PM
 */

namespace SpeedLimit;

use Predis\Client;
use SpeedLimit\Exceptions\RateException;

class RateLimit
{

    const MICROSECOND = 'microsecond';
    const MILLISECOND = 'millisecond';
    const SECOND = 'second';
    const MINUTE = 'minute';
    const HOUR   = 'hour';
    const DAY    = 'day';
    const WEEK   = 'week';
    const MONTH  = 'month';
    const YEAR   = 'year';

    private static $unitMap = [
        self::MICROSECOND =>        0.000001,
        self::MILLISECOND =>        0.001,
        self::SECOND      =>        1,
        self::MINUTE      =>       60,
        self::HOUR        =>     3600,
        self::DAY         =>    86400,
        self::WEEK        =>   604800,
        self::MONTH       =>  2629743.83,
        self::YEAR        => 31556926,
    ];

    private $redis;

    private $redisKey;

    private $rate;

    private $timer;

    public function __construct(int $rate, string $timer, Client $redis)
    {
        if (!isset(self::$unitMap[$timer])) {
            throw new \Exception('Failed is not timer');
        }
        $this->rate     =   (int)$rate;
        $this->timer    =   self::$unitMap[$timer];
        $this->redis    =   $redis;
    }

    public function setRedisKey(string $id) : self
    {
        $this->redisKey = md5(sha1($id.'ACK'.$this->timer));

        return $this;
    }


    public function getRedisKey() : string
    {
        return $this->redisKey;
    }


    public function rate() : bool
    {
        try {
            $nowTime = microtime(true);
            if (is_null($this->getRedisKey())) {
                throw new RateException('You must be set redis key');
            }
            $this->redis->watch($this->getRedisKey());
            $rateInfo = $this->redis->get($this->getRedisKey());
            $newVal = json_encode(['max_num' => $this->rate, 'timer' => $this->timer, 'last_time' => $nowTime]);
            if (!is_null($rateInfo)) {
                $rateObj = json_decode($rateInfo);
                $remainTime = $nowTime - $rateObj->last_time;
                if ($remainTime >= $this->timer) {
                    //超过时间则重新设置
                    $this->redis->set($this->getRedisKey(), $newVal);
                    return true;
                }
                $rateObj->max_num--;
                if ($rateObj->max_num > 0 && $remainTime > 0) {
                    //走本时间段内的次数减少
                    $newVal = json_encode(['max_num' => $rateObj->max_num--, 'timer' => $remainTime, 'last_time' => $nowTime]);
                    $this->redis->set($this->getRedisKey(), $newVal);
                    return true;
                }
            } else {
                $this->redis->set($this->getRedisKey(), $newVal);
                return true;
            }
            return false;
        } catch (RateException $exception) {
            throw new RateException($exception->getMessage());
        }
    }
}
