<?php
$source = file_get_contents('admin/engine_settings.php');
if ($source === false) die("Cannot read file");
$tokens = token_get_all($source);
$output = "";
foreach ($tokens as $token) {
    if (is_string($token)) {
        $output .= $token;
    } else {
        $id = $token[0];
        $text = $token[1];
        if ($id === T_COMMENT || $id === T_DOC_COMMENT) {
            $newlines = substr_count($text, "\n");
            $output .= str_repeat("\n", $newlines);
        } else {
            $output .= $text;
        }
    }
}

$output = preg_replace('/<!--(.*?)-->/s', '', $output);
file_put_contents('admin/engine_settings.php', $output);
echo "Done";
?>
