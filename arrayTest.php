<?php

/**
 * arrayTest.php
 * Project: bjSim
 * Created with PhpStorm
 * Developer: David Barkman
 * Created on: 10/22/22 @ 21:35
 */

$things = [0 => 8, 1 => 9, 2 => 10];

$something = true;

foreach($things as &$thing) {
    echo $thing . PHP_EOL;
    unset($things[0]);
    if ($something) {
        $something = false;
        $things[1] = 11;
    }
}