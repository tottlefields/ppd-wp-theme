<?php

ini_set("auto_detect_line_endings", true);

$header = array();
$classes = array();
$lho_options = array();
$show_id = 0;

$file = (isset($_GET['file'])) ? $_GET['file'] : str_replace('file=', '', $argv[1]);

if (($handle = fopen($file, "r")) !== FALSE) {
	$row = 0;
	while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
		if ($row == 0){
			//head row - capture column titles
			$header = $data;
			$row ++;
			continue;
		}
		//Array([0] => showID    [1] => classDate    [2] => classNo    [3] => className    [4] => minLevel    [5] => maxLevel    [6] => minHeight    [7] => maxHeight    [8] => noDogs
	    //[9] => isSpecial    [10] => hasLHO    [11] => isABC    [12] => isNBC    [13] => isJuniors    [14] => price)
	    if ($show_id == 0){ $show_id = $data[0]; }
		
		if (!isset($classes[$data[1]])){
			$classes[$data[1]] = array();
		}
		if ($data[10]){
			if (!isset($lho_options[$data[6]])){
				$lho_options[$data[6]] = 1;
			}
			if (!isset($lho_options[$data[7]])){
				$lho_options[$data[7]] = 1;
			}
		}
		
		$tmp_array = array();
		for ($col = 2; $col < count($header); $col++) {
			$tmp_array[$header[$col]] = $data[$col];
		}
		array_push($classes[$data[1]], $tmp_array);	
	}
	echo "DELETE FROM wpao_postmeta WHERE post_id=".$show_id." AND meta_key='classes';\n";
	echo "DELETE FROM wpao_postmeta WHERE post_id=".$show_id." AND meta_key='lho_options';\n";
	echo "INSERT INTO wpao_postmeta VALUES (NULL, ".$show_id.", 'classes', '".serialize($classes)."');\n";
	echo "INSERT INTO wpao_postmeta VALUES (NULL, ".$show_id.", 'lho_options', '".serialize($lho_options)."');\n";
}
?>
