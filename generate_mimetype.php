<?php

$mimefile = '/etc/mime.types';

$fp = fopen ($mimefile, 'rb');
if ( ! is_resource ($fp) )
	return;

$year = date ('Y');

$udef = array (
	'application/x-sh' => 'bash',
	'application/x-httpd-php' => 'php',
	'application/x-httpd-php-source' => 'phps',
	'text/plain' => 'ada sap awk conf cpp cs d diff dump e erl f f90 for gdb hs ' .
					'inc j java lisp jsp lua m q pas pl py rb s scheme sql spec vb vim xpp yaml',
	'application/octet-stream' => 'pyc'
);

echo <<<EOF
<?php
/*
 * Generated by WebUtil package
 * http://pear.oops.org/docs/li_WebAPI.html
 *
 * Copyright (c) $year JoungKyun.Kim <http://oops.org>
 * \$Id: \$
 */
switch (\$file) {

EOF;

while ( ! feof ($fp) ) {
	$line = trim (fgets ($fp, 1024));
	$line = trim (preg_replace ('/\s*#.*/', '', $line));

	if ( ! $line )
		continue;

	$la = preg_split ('/\s+/', $line);
	if ( count ($la) < 2 )
		continue;

	$mime = array_shift ($la);

	foreach ( $la as $item )
		printf ('%scase \'%s\' :%s', "\t", $item, PHP_EOL);

	if ( $udef[$mime] ) {
		$lla = preg_split ('/\s+/', $udef[$mime]);
		foreach ( $lla as $item )
			printf ('%scase \'%s\' :%s', "\t", $item, PHP_EOL);
		unset ($udef[$mime]);
	}

	printf ('%s$mime = \'%s\';%s', "\t\t", $mime, PHP_EOL);
	printf ('%sbreak;%s', "\t\t", PHP_EOL);
}

foreach ( $udef as $mime => $items ) {
	$lla = preg_split ('/\s+/', $items);
	foreach ( $lla as $item ) {
		printf ('%scase \'%s\' :%s', "\t", $item, PHP_EOL);
	}
	printf ('%s$mime = \'%s\';%s', "\t\t", $mime, PHP_EOL);
	printf ('%sbreak;%s', "\t\t", PHP_EOL);
}

echo <<<EOF
	default:
		\$mime = 'application/octet-stream';
}
?>
EOF;

fclose ($fp);


?>
