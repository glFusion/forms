<?php

include_once 'english.php';
$LANG_source = $LANG_FORMS;
include_once $argv[1] . '.php';

foreach ($LANG_source as $key=>$value) {
    if (!isset($LANG_FORMS[$key])) {
        echo "Missing: '$key' => '$value'\n";
        $LANG_FORMS[$key] = $value;
    }
}
ksort($LANG_FORMS);
var_export($LANG_FORMS);
?>
