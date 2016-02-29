<?php

define('DB_NAME', 'viet-anh.sqlite');
define('TABLE_NAME', 'TuDien_VietAnh');
define('WORD_COLUMN', 'Tu');
define('DEFINITION_COLUMN', 'Dich');
define('DICTIONARY_TITLE', 'Viet Anh Dictionary');
define('DICTIONARY_CREATOR', 'andrey@somemilk.org');
define('DICTIONARY_DESCRIPTION', 'Viet Anh Dictionary for Kindle');
define('DICTIONARY_COVER', 'cover.png');

$output_filename_html = __DIR__ . '/output/dictionary-viet-anh.html';
$output_filename_opf = __DIR__ . '/output/dictionary-viet-anh.opf';

$entry_template = file_get_contents(__DIR__ . '/templates/entry.html');
$header_template = file_get_contents(__DIR__ . '/templates/header.html');
$footer_template = file_get_contents(__DIR__ . '/templates/footer.html');
$opf_template = file_get_contents(__DIR__ . '/templates/dictionary.opf');

$bom = pack('H*','EFBBBF');

$db = new PDO('sqlite:' . DB_NAME);

$offset = 0;
$limit = 100;

$f = fopen($output_filename_html, 'w');

$header_template = str_replace('{%dictionary title%}', DICTIONARY_TITLE, $header_template);
$header_template = str_replace('{%creator%}', DICTIONARY_CREATOR, $header_template);
$header_template = str_replace('{%description%}', DICTIONARY_DESCRIPTION, $header_template);
$header_template = str_replace('{%date%}', date('m/d/Y'), $header_template);
// BOM header required
$header_template = $bom . preg_replace("`^$bom`", '', $header_template);

fwrite($f, $header_template);

$i = 1;
while (true) {
    $result = $db->query("SELECT * FROM " . TABLE_NAME . " LIMIT $offset, $limit")->fetchAll();
    if (!$result) break;
    foreach ($result as $row) {
        $word = $row[WORD_COLUMN];
        $definition = definition_format($row[DEFINITION_COLUMN]);
        $entry = str_replace('{%word%}', $word, str_replace('{%definition%}', $definition, $entry_template));
        echo "$i: $word\n";
        fwrite($f, $entry);
        $i++;
    }
    $offset += $limit;
}

fwrite($f, $footer_template);
fclose($f);

$opf = $opf_template;
$opf = str_replace('{%dictionary title%}', DICTIONARY_TITLE, $opf);
$opf = str_replace('{%creator%}', DICTIONARY_CREATOR, $opf);
$opf = str_replace('{%description%}', DICTIONARY_DESCRIPTION, $opf);
$opf = str_replace('{%cover%}', DICTIONARY_COVER, $opf);
$opf = str_replace('{%date%}', date('m/d/Y'), $opf);

// BOM header required
$opf = $bom . preg_replace("`^$bom`", '', $opf);

file_put_contents($output_filename_opf, $opf);

/**
 * Formatting definition for the dictionary I have
 * (yours might be different so maybe you'll have to rewrite or remove this code)
 *
 * @param $definition
 * @return mixed
 */
function definition_format($definition)
{
    $definition = preg_replace('`\[MENU\]([^\[]+)`', '<em>\1</em>', $definition);
    $definition = str_replace('[ENTER]', "<br />\n", $definition);
    $definition = str_replace('[CATEGORY]', "&bull;", $definition);
    $definition = str_replace('[TAB]', "&nbsp;&nbsp;&nbsp;&nbsp;", $definition);
    $definition = str_replace('->', "&rarr;", $definition);
    return $definition;
}