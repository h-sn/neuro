<?php

function getParam($param) {
    if(isset($_REQUEST[$param])) {
        return trim($_REQUEST[$param]);
    }
    return null;
}


if(getParam('action') == 'save') {
    $data = json_decode(getParam('data'));
    if(empty($data) || !isset($data->variants) || !isset($data->data) || !getParam('name')) {
        die('wrong data');
    }
    file_put_contents(getParam('name'),getParam('knowledge').'.json');
    die('OK');
}
if(getParam('action') == 'load') {

    if(!file_exists('knowledges/'.getParam('name').'.json')) {
        die('data file not found');
    }

    $data = file_get_contents('knowledges/'.getParam('name').'.json');
    die($data);
}