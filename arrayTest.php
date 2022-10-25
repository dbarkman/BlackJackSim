<?php

/**
 * arrayTest.php
 * Project: bjSim
 * Created with PhpStorm
 * Developer: David Barkman
 * Created on: 10/25/22 @ 07:24
 */

$arr1 = ['a' => 1, 'b' => 1];
$arr2 = ['a' => 1, 'b' => 1];

$results = array_merge($arr1, $arr2);
var_dump($results);
