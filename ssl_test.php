<?php

function makeCurlRequest($url, $caPath = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    if ($caPath) {
        curl_setopt($ch, CURLOPT_CAINFO, $caPath);
    }

    $output = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    return ['output' => $output, 'error' => $error];
}

$url = "https://qit.woo.com";
$shouldFail = isset($argv[1]) ? $argv[1] === 'true' : false;

// First attempt without CA certificate
$response = makeCurlRequest($url);
if ($response['error']) {
    if (!$shouldFail) {
        echo "Request unexpectedly failed without CA certificate: " . $response['error'] . PHP_EOL;
        exit(1);
    } else {
        echo "Request correctly failed without CA certificate (expected on Windows)" . PHP_EOL;
    }
} else {
    if ($shouldFail) {
        echo "Request unexpectedly succeeded without CA certificate on Windows" . PHP_EOL;
        exit(1);
    } else {
        echo "Request succeeded without CA certificate (expected on Linux and macOS)" . PHP_EOL;
    }
}
?>
