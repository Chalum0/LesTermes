<?php

require('../vendor/autoload.php');
require_once('../src/utils/cards.php');

$mustache = new Mustache_Engine;

$template = file_get_contents('../src/templates/layout.php');
$header = file_get_contents('../src/templates/partials/_nav.php');
$content = file_get_contents('../src/templates/pages/game.php');
$footer = "";

$data = [
    'title' => 'Les termes - On Game',
    'header' => $header,
    'template' => $content,
    'footer' => $footer
];

echo $mustache->render($template, $data);
