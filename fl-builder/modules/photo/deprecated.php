<?php

FLBuilder::register_module_deprecations( 'photo', [
	'v1' => [
		'config' => [
			'include_wrapper' => true,
			'element_setting' => true,
		],
		'files'  => [
			'includes/frontend.php',
		],
	],
	'v2' => [], // Deprecates the old markup in the frontend.php file
] );
