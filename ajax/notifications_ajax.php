<?php

include ("../inc/includes.php");

Session::checkLoginUser();

header('Content-Type: application/json; charset=utf-8');

$return = false;

/*$return[] = [
   'title'  => 'New ticket',
   'body'   => 'This is a new ticket',
   'url'    => '/front/ticket.php?id=1'
];*/
echo json_encode($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
