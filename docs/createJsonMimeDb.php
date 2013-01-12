<?php

if ( ! function_exists('glob_recursive'))
{
    // Does not support flag GLOB_BRACE
   
    function glob_recursive($pattern, $flags = 0)
    {
        $files = glob($pattern, $flags);
       
        foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir)
        {
            $files = array_merge($files, glob_recursive($dir.'/'.basename($pattern), $flags));
        }
       
        return $files;
    }
}

$entries = array();

if ($argc < 2) {
	die("specify the directory to use as a source for creating the mimeDb" . PHP_EOL);
}

$x = glob_recursive($argv[1] . DIRECTORY_SEPARATOR . "*");

$finfo = finfo_open(FILEINFO_MIME);
foreach($x as $f) {
	$r = realpath($f);
	if(is_dir($r)) {
		continue;
	}
	$entries[$r] = finfo_file($finfo, $r);
}
finfo_close($finfo);

echo json_encode($entries);
