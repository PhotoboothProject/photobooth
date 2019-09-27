<?php
// get data from db.txt
if(!file_exists(__DIR__ . '/../data/db.txt')){
	file_put_contents(__DIR__ . '/../data/db.txt', json_encode(array()));
}
$images = json_decode(file_get_contents(__DIR__ . '/../data/db.txt'));
