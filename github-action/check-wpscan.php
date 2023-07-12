<?php

function get_plugins_and_themes( $wpContentPath ) {
    $pluginsPath = $wpContentPath . '/plugins';
    $themesPath = $wpContentPath . '/themes';

    $plugins = get_extension_with_version( $pluginsPath, 'plugin' );
    $themes = get_extension_with_version( $themesPath, 'theme' );

    return array_merge($plugins, $themes);
}

function get_extension_with_version( $path, $type ) {
    $items = [];

    if ( is_dir( $path ) ) {
        $itemDirs = scandir( $path );

        foreach ( $itemDirs as $dir ) {
            $full_path = $path . '/' . $dir;

            if ( $dir !== '.' && $dir !== '..' && is_dir( $full_path ) ) {
                $item = [
                    'name' => $dir,
                    'version' => $type === 'plugin' ? get_plugin_data( $full_path ) : get_theme_data( $full_path ),
                    'type' => $type,
                    'slug' => basename($full_path)
                ];

                $items[] = $item;
            }
        }
    }

    return $items;
}

function get_plugin_data( $pluginPath ) {
    $pluginFile = $pluginPath . '/' . basename( $pluginPath ) . '.php';

    if ( file_exists( $pluginFile ) ) {
        $pluginData = get_header_data( $pluginFile, 'plugin' );

        return $pluginData['Version'];
    }

    return 'Unknown';
}

function get_theme_data( $themePath ) {
    $themeFile = $themePath . '/style.css';

    if ( file_exists( $themeFile ) ) {
        $themeData = get_header_data( $themeFile, 'theme' );

        return $themeData['Version'];
    }

    return 'Unknown';
}

function get_header_data( $file, $type ) {
    $fileData = file_get_contents( $file );

    $headers = [];

    $regex = $type === 'plugin' ? '/^\s*\*\s*Version:\s*(.*)$/m' : '/^Version:\s*(.*)$/m' ;

    if (preg_match( $regex, $fileData, $matches ) ) {
        $headers['Version'] = $matches[1];
    } else {
        $headers['Version'] = 'Unknown';
    }

    return $headers;
}

function get_vulns( $extension_slug, $extension_type ) {
    $api_type_path = $extension_type === 'plugin' ? 'plugins' : 'themes';
    $api_url = "https://wpscan.com/api/v3/$api_type_path/{$extension_slug}";
    return make_wpscan_api_request( $api_url );
}

function make_wpscan_api_request( $api_url ) {
    $token = $_ENV['WPSCAN_TOKEN'];

    $options = array(
        'http'=>array(
          'method'=>"GET",
          'header'=>"Authorization: Token token=$token\r\n"
        )
      );

    $context = stream_context_create( $options );

  	$result = file_get_contents( $api_url, false, $context );

  	$parsed = json_decode( $result, true );

  	$response = [];
  	$vulnerabilities = array_values( $parsed )[0]['vulnerabilities'];
  	foreach ( $vulnerabilities as $vulnerability ) {
 		array_push( $response, [ 'id' => $vulnerability['id'], 'title' => $vulnerability['title'], 'fixed_in' => $vulnerability['fixed_in'], 'description' => $vulnerability['description'] ] );
 	}
  	return $response;
 }

$wp_content_path = $_ENV['WP_CONTENT_PATH'];

$extensions = get_plugins_and_themes( $wp_content_path );

$found_vulns = false;

foreach ( $extensions as $extension ) {
    $extension['vulnerabilities'] = array_filter(
        get_vulns( $extension['slug'], $extension['type'] ),
        function( $vuln ) use ( $extension ) {
            return version_compare( $extension['version'], $vuln['fixed_in'], '<' );
        }
    );

    if( ! empty( $extension['vulnerabilities'] ) ) {
        $found_vulns = true;
        echo( "Found vulnerability in the following extension: " );
        var_dump( $extension );
    }
}

if( $found_vulns ){
    exit(1);
}

exit(0);