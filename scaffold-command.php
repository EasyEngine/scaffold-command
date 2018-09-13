<?php

if ( ! class_exists( 'EE' ) ) {
	return;
}

$autoload = dirname( __FILE__ ) . '/vendor/autoload.php';
if ( file_exists( $autoload ) ) {
	require_once $autoload;
}
include_once 'src/Scaffold_Command.php';

EE::add_command( 'scaffold', 'Scaffold_Command' );
