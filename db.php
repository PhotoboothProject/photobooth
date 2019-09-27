<?php
// get data from db.txt
if(!file_exists('data/db.txt')){
	file_put_contents('data/db.txt', json_encode(array()));
}
$images = json_decode(file_get_contents('data/db.txt'));
