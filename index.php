<?php

require_once __DIR__ . "/Wlc.php";

$app_id = "e44158030c7341819aedf04a147f3e8a";
$secret_key = "d59bbdefd68b71f906c4d67e52841700";
$biz_id = "1101999999";
$wlc = new Wlc($app_id, $secret_key, $biz_id);

//实名认证
$body = [
    'ai' => '100000000000000001',
    'name' => "某一一",
    'idNum' => "110000190101010001",
];
$res = $wlc->setBody($body)->check();

//实名认证查询
$params = [
    'ai' => '100000000000000001',
];
$res = $wlc->setParams($params)->query();

//上下线上报
$body = [
    [
        'no' => 1, 
        'si' => uniqid(), 
        'ot' => time(), 
        'bt' => 1, 
        'ct' => 0, 
        'di' => '', 
        'pi' => '1fffbjzos82bs9cnyj1dna7d6d29zg4esnh99u'
    ],
];
$res = $wlc->setBody($body)->loginout();
