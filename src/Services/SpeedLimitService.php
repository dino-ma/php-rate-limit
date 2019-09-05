<?php
/**
 * Created by PhpStorm.
 * User: dino.ma
 * Date: 2019/9/5
 * Time: 4:23 PM
 */

namespace DinoMa\PhpSpeedLimit\Services;


class SpeedLimitService implements SpeedLimitServiceInterface
{


    private static $instance;

    private function __construct()
    {
    }

    private function __clone()
    {
    }


    public static function getInstance(): SpeedLimitService
    {
        if (!self::$instance instanceof self) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function test() : int
    {
        return 0;
    }
}