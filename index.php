<?php

require_once "classes/Page_.php";

$page = new Page(
    "Головна сторінка",
    "Ласкаво просимо до системи ProHour."
);

$page->render();