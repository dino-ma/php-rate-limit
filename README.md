## PHP限流

* 基于PHP7.1+ Redis
* 基于Predis
* 实现的简单版的限流限速。（一段时间内限制请求次数）
  
    

## 有问题反馈

* 邮件(dino_ma#163.com, 把#换成@)
* Wechat: 9393103


## example

```php
<?php
include './vendor/autoload.php';
use \SpeedLimit\RateLimit;
use \Predis\Client;


$redis = new Client([
    'scheme' => env('redis_scheme', 'tcp'),
    'host'   => env('redis_host', 'redis'),
    'port'   => env('redis_port', 6379),
]);
$rate = new RateLimit(15, RateLimit::MINUTE, $redis);
$rate->setRedisKey('123');


for ($i = 1; $i < 100; $i++) {
    $is = $rate->rate();
    if (!$is) {
        echo 'not allow[' . $i . ']' . PHP_EOL;
    } else {
        echo 'ok' . '[' . $i . ']' . PHP_EOL;
    }
}
```
