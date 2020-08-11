<?php

// +----------------------------------------------------------------------------
// | dino.ma版权所属
// +----------------------------------------------------------------------------
// | Copyright (c) 2019 https://mashengjie.com All rights reserved.
// +----------------------------------------------------------------------------
// | Author: dino.ma <dino_ma@163.com>
// +----------------------------------------------------------------------------
declare(strict_types=1);
namespace SpeedLimit;

use Predis\Client;
use \SpeedLimit\Exceptions\SlidingWindowRateLimitException;

class SlidingWindowRateLimit
{

    const SLIDING_WINDOW_PRE = 'sliding_window_ratelimie:window:';

    private $redis     = null;
    private $key       = null;
    private $window    = null;
    private $limit     = null;
    private $blocks    = null;


    /**
     * SlidingWindowRateLimit constructor.
     * @param string $key   redisKey
     * @param int $window   窗口时间（秒）
     * @param int $limit    请求次数限制
     * @param int $blocks   窗口分多少块（块越多流量控制越平滑）
     * @param Client $redis
     * @throws SlidingWindowRateLimitException
     */
    public function __construct(string $key, int $window, int $limit, int $blocks, Client $redis)
    {
        if (empty($key)) {
            throw new SlidingWindowRateLimitException('key 不能为空');
        }
        if ($window <= 0) {
            throw new SlidingWindowRateLimitException('窗口时间需要大于0');
        }
        if ($limit <= 0) {
            throw new SlidingWindowRateLimitException('请求次数限制应大于0，小于等于0则无意义');
        }
        if ($blocks <= 0) {
            throw new SlidingWindowRateLimitException('窗口块/时间片不能为空');
        }
        if (!$redis instanceof Client) {
            throw new SlidingWindowRateLimitException('请使用Jredis SDK');
        }

        $this->key      =   self::SLIDING_WINDOW_PRE . $key;
        $this->window   =   $window;
        $this->limit    =   $limit;
        $this->blocks   =   $blocks;
        $this->redis    =   $redis;
    }

    /**
     * 基于滑动窗口时间片做流量控制 类似于 tcp/ip
     * @return bool
     * @throws SlidingWindowRateLimitException
     */
    public function access()    :   bool
    {
        try {
            $now = microtime(true);//当前时间
            $result = $this->redis->pipeline(function ($pipeline) use ($now) {
                $pipeline->zrangebyscore($this->key, 0, $now - $this->window);
                $pipeline->zrange($this->key, 0, -1);
                $pipeline->zadd($this->key, $now, $now);
                $pipeline->expire($this->key, $this->window);
            });

            $timestamps = $result[1];//获取ZRANGE的返回值

            return (max(0, $this->limit - count($timestamps)))>0;
        } catch (SlidingWindowRateLimitException $exception) {
            throw new SlidingWindowRateLimitException($exception->getMessage());
        }
    }
}