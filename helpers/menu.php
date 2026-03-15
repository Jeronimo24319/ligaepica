<?php

function currentModule() {
    $path = $_SERVER['PHP_SELF'];
    $parts = explode('/', $path);

    $key = array_search('modules', $parts);

    if ($key !== false && isset($parts[$key + 1])) {
        return $parts[$key + 1];
    }

    return '';
}

function isActive($moduleName) {
    return currentModule() === $moduleName 
        ? 'active bg-primary text-white' 
        : 'text-white';
}