<?php
namespace Nayjest\DI;
include __DIR__.'/../vendor/autoload.php';
$h = new Hub();
$c = new BlockComponent('block_data');
$h->add($c);
var_dump($h);