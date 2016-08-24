<?php

// get data from data.txt
if(!file_exists('data.txt')){
	file_put_contents('data.txt', '');
	$data = array();
} else {
	$data = explode(PHP_EOL, file_get_contents('data.txt'));
}

