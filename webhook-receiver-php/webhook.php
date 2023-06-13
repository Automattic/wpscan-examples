<?php

$imaginary_installed_software = [
	'plugins' => [
		'some-plugin-slug' => '3.2.1',
	],
	'themes' => [
		'some-theme-slug' => '30.3',
	],
	'wordpresses' => [
		'6.2',
	],
];

// Get the posted JSON body
$data = file_get_contents('php://input');

// JSON decode it
$data = json_decode($data);

$detected_vulnerable_software = [];

// Check if one of the installed extensions (plugins & themes) is vulnerable
foreach (['plugins', 'themes'] as $type) {
	foreach ($imaginary_installed_software[$type] as $slug => $version) {

		// Skip if the extension is not in the webhook data
		if (!isset($data->$type->$slug)) continue;

		$extension = $data->$type->$slug;

		if ((empty($extension->fixed_in) || version_compare($extension->fixed_in, $version, '>'))
			&& (empty($extension->introduced_in) || version_compare($extension->introduced_in, $version, '<='))
		) {
			$detected_vulnerable_software[] = [
				'type' => $type,
				'slug' => $slug,
				'version' => $version,
			];
		}
	}
}

// Check if one of the installed WordPress versions is vulnerable
foreach ($imaginary_installed_software['wordpresses'] as $version) {

	// Skip version if it is not in the webhook data
	if (!isset($data->wordpresses->$version)) continue;

	$wordpress = $data->wordpresses->$version;

	if ((empty($wordpress->fixed_in) || version_compare($wordpress->fixed_in, $version, '>'))
		&& (empty($wordpress->introduced_in) || version_compare($wordpress->introduced_in, $version, '<='))
	) {
		$detected_vulnerable_software[] = [
			'type' => 'wordpress',
			'version' => $version,
		];
	}
}

// Process the results somehow - we're sending it to the error log for now.
error_log('Vulnerable software detected: ' . print_r($detected_vulnerable_software, true) );
