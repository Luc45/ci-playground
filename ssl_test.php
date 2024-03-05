<?php

echo "CAFile:\n";
var_dump(ini_get('openssl.cafile'));

echo "CAPath:\n";
var_dump(ini_get('openssl.capath'));

function downloadCACertificate($url, $destination) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $data = curl_exec($ch);
    curl_close($ch);

    if ($data === false) {
        return false;
    }

    return file_put_contents($destination, $data) !== false;
}

function makeCurlRequest($url, $caPath = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    // Set verbose mode
    curl_setopt($ch, CURLOPT_VERBOSE, true);

    // Set output file for verbose information
    $verbose = fopen('php://temp', 'w+');
    curl_setopt($ch, CURLOPT_STDERR, $verbose);

    if ($caPath) {
        curl_setopt($ch, CURLOPT_CAINFO, $caPath);
    }

    $output = curl_exec($ch);
    $error = curl_error($ch);

    // Rewind verbose output
    rewind($verbose);
    $verboseLog = stream_get_contents($verbose);

    // Close cURL handle
    curl_close($ch);

    // Output verbose information
    echo "Verbose information:\n", $verboseLog, "\n";

    return ['output' => $output, 'error' => $error];
}

$url = "https://qit.woo.com";
$shouldFail = isset($argv[1]) ? $argv[1] === 'true' : false;

// Detect OS
$isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

// First attempt without CA certificate
$response = makeCurlRequest($url);
if ($response['error']) {
    if ($isWindows) {
        if (!$shouldFail) {
            echo "Request unexpectedly failed without CA certificate: " . $response['error'] . PHP_EOL;
            exit(1);
        } else {
            echo "Request correctly failed without CA certificate (expected on Windows)" . PHP_EOL;
        }
    } else {
        echo "Request failed without CA certificate (expected on Linux and macOS)" . PHP_EOL;
    }
} else {
    if ($shouldFail) {
        if ($isWindows) {
            echo "Request unexpectedly succeeded without CA certificate on Windows" . PHP_EOL;
            exit(1);
        } else {
            echo "Request unexpectedly succeeded without CA certificate (expected on Linux and macOS)" . PHP_EOL;
            exit(1);
        }
    } else {
        echo "Request succeeded without CA certificate (expected on Linux and macOS)" . PHP_EOL;
    }
}

// Download and retry on Windows
if ($isWindows) {
    // Download CA certificate
    $caFile = 'cacert.pem';
    if (!downloadCACertificate('http://curl.haxx.se/ca/cacert.pem', $caFile)) {
        echo "Failed to download CA certificate\n";
        exit(1);
    }

    // Verify the downloaded CA certificate exists
    if (!file_exists($caFile)) {
        echo "Downloaded CA certificate file does not exist\n";
        exit(1);
    }

    // Print downloaded CA certificate contents for debugging
    echo "Downloaded CA certificate:\n";
    echo file_get_contents($caFile), "\n";

    // Retry cURL request with downloaded CA certificate path
    $response = makeCurlRequest($url, __DIR__ . DIRECTORY_SEPARATOR . $caFile);
    unlink($caFile); // Delete downloaded CA certificate

    if ($response['error']) {
        echo "Request failed with downloaded CA certificate: " . $response['error'] . PHP_EOL;
        exit(1);
    } else {
        echo "Request succeeded with downloaded CA certificate" . PHP_EOL;
    }
}
?>
