<?php
declare(strict_types=1);

/**
 *
 * This file is part of the php-speedlimit package.
 *
 * (c) Dino <dino_ma@163.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace DinoMa\PhpSpeedLimit;


use DinoMa\PhpSpeedLimit\Services\SpeedLimitService;

class SpeedLimit extends SpeedLimitService
{
    public function test(): int
    {
        return parent::test();
    }

}