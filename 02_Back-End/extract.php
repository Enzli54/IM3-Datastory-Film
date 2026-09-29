<?php

header('Content-Type: text/plain charset=utf-8');

$json = file_get_contents('data/filme.json'); //Datei als Text

$data = json_decode($json, true);