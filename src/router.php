<?php

require_once("config/routes.php");

$availableRouteNames = array_keys(AVAILABLE_ROUTES);

if (isset($_GET['page']) && in_array($_GET['page'], $availableRouteNames, true)){
    $route = AVAILABLE_ROUTES[$_GET['page']];
}   else {
    $route = DEFAULT_ROUTE;
}

require 'controllers/' . $route["controller"];
