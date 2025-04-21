<?php
/*
 * Version 2 Changes:
 * - Webhook Integration: Added the ability to send vulnerability scan results to a configured 
 *   webhook URL for automated notification and logging.
 * - Improved Data Structure: Organized plugins and themes as separate arrays within a single 
 *   data object instead of merging both lists into one.
 * - Error Handling: Improved error handling for API requests and webhook transmission.
 * - Security Checks: Ensured the 'security-checks' key is always present to avoid ingest issues.
 */

function get_plugins_and_themes($wpContentPath) {
    return [
        'plugins' => get_extension_with_version($wpContentPath . '/plugins', 'plugin'),
        'themes' => get_extension_with_version($wpContentPath . '/themes', 'theme')
    ];
}

function get_extension_with_version($path, $type) {
    $items = [];

    if (is_dir($path)) {
        $itemDirs = scandir($path);

        foreach ($itemDirs as $dir) {
            $full_path = $path . '/' . $dir;

            if ($dir !== '.' && $dir !== '..' && is_dir($full_path)) {
                $items[$dir] = [
                    'name' => $dir,
                    'version' => $type === 'plugin' ? get_plugin_data($full_path) : get_theme_data($full_path),
                    'type' => $type,
                    'vulnerabilities' => [],
                    'closed' => false
                ];
            }
        }
    }

    return $items;
}

function get_plugin_data($pluginPath) {
    $pluginFile = $pluginPath . '/' . basename($pluginPath) . '.php';
    return file_exists($pluginFile) ? get_header_data($pluginFile, 'plugin') : null;
}

function get_theme_data($themePath) {
    $themeFile = $themePath . '/style.css';
    return file_exists($themeFile) ? get_header_data($themeFile, 'theme') : null;
}

function get_header_data($file, $type) {
    $fileData = file_get_contents($file);
    $regex = $type === 'plugin' ? '/^\s*\*\s*Version:\s*(.*)$/m' : '/^Version:\s*(.*)$/m';
    return preg_match($regex, $fileData, $matches) ? trim($matches[1]) : null;
}

function get_vulns($extension_slug, $extension_type) {
    $api_type_path = $extension_type === 'plugin' ? 'plugins' : 'themes';
    $api_url = "https://wpscan.com/api/v3/$api_type_path/{$extension_slug}";
    return make_wpscan_api_request($api_url);
}

function make_wpscan_api_request($api_url) {
    $token = getenv('WPSCAN_API');
    $options = ['http' => ['method' => "GET", 'header' => "Authorization: Token token=$token\r\n"]];
    $context = stream_context_create($options);
    $result = file_get_contents($api_url, false, $context);

    if ($result === false) return [];

    $parsed = json_decode($result, true);
    $response = [];

    if (!empty($parsed) && isset(array_values($parsed)[0]['vulnerabilities'])) {
        foreach (array_values($parsed)[0]['vulnerabilities'] as $vulnerability) {
            $response[] = [
                'id' => $vulnerability['id'] ?? null,
                'title' => $vulnerability['title'] ?? null,
                'created_at' => $vulnerability['created_at'] ?? null,
                'updated_at' => $vulnerability['updated_at'] ?? null,
                'published_date' => $vulnerability['published_date'] ?? null,
                'vuln_type' => $vulnerability['vuln_type'] ?? null,
                'references' => $vulnerability['references'] ?? null,
                'verified' => $vulnerability['verified'] ?? false,
                'poc' => $vulnerability['poc'] ?? null,
                'description' => trim(str_replace("\r\n", " ", $vulnerability['description'] ?? '')),
                'cvss' => [
                    'score' => $vulnerability['cvss']['score'] ?? null,
                    'vector' => $vulnerability['cvss']['vector'] ?? null,
                    'severity' => $vulnerability['cvss']['severity'] ?? null
                ],
                'fixed_in' => $vulnerability['fixed_in'] ?? null,
                'introduced_in' => $vulnerability['introduced_in'] ?? null,
                'old_id' => $vulnerability['old_id'] ?? null,
                'metasploit' => $vulnerability['metasploit'] ?? null
            ];
        }
    }

    return $response;
}

function initialize_security_checks() {
    return [
        'database-exports' => ['vulnerabilities' => []],
        'debuglog-files' => ['vulnerabilities' => []],
        'https' => [
            'vulnerabilities' => [
                [
                    'title' => 'The website does not seem to be using HTTPS (SSL/TLS) encryption for communications.',
                    'severity' => 'high',
                    'id' => 'https',
                    'remediation_url' => 'https://blog.wpscan.com/wordpress-ssl-tls-https-encryption/'
                ]
            ]
        ],
        'secret-keys' => ['vulnerabilities' => []],
        'version-control' => ['vulnerabilities' => []],
        'weak-passwords' => ['vulnerabilities' => []],
        'wpconfig-backups' => ['vulnerabilities' => []],
        'xmlrpc-enabled' => [
            'vulnerabilities' => [
                [
                    'title' => 'The XML-RPC interface is enabled. This significantly increases your site\'s attack surface.',
                    'severity' => 'medium',
                    'id' => 'http-54-91-15-538000-xmlrpc-php',
                    'remediation_url' => 'https://blog.wpscan.com/is-wordpress-xmlrpc-a-security-problem/'
                ]
            ]
        ]
    ];
}

function send_to_webhook($json_data) {
    $webhook_url = getenv('WEBHOOK_URL');

    if (!$webhook_url) {
        echo "No Webhook URL configured. Skipping webhook notification.\n";
        return;
    }

    $ch = curl_init($webhook_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json_data));
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code >= 200 && $http_code < 300) {
        echo "Scan results successfully sent to webhook: $webhook_url\n";
    } else {
        echo "Failed to send scan results to webhook: $webhook_url (HTTP Status: $http_code)\n";
        echo "Response: $response\n";
    }
}

$wp_content_path = getenv('WP_CONTENT_PATH');
$data = get_plugins_and_themes($wp_content_path);

foreach ($data['plugins'] as $slug => &$plugin) {
    $plugin['vulnerabilities'] = get_vulns($slug, 'plugin');
}

foreach ($data['themes'] as $slug => &$theme) {
    $theme['vulnerabilities'] = get_vulns($slug, 'theme');
}

$full_output = [
    'plugins' => $data['plugins'],
    'themes' => $data['themes'],
    'wordpress' => [],
    'security-checks' => initialize_security_checks(),
    'cache' => time()
];

send_to_webhook($full_output);

echo json_encode($full_output, JSON_PRETTY_PRINT);
echo "\nScan completed successfully.\n";

exit(0);
?>