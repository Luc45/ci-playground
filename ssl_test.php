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

$url = "https://curl.se";

// First attempt without CA certificate
$response = makeCurlRequest($url);
if ($response['error']) {
    echo "Request failed without CA certificate: " . $response['error'] . PHP_EOL;
} else {
    echo "Request unexpectedly succeeded without CA certificate" . PHP_EOL;
    exit(1);
}

// Download CA certificate
$caCertUrl = "http://curl.haxx.se/ca/cacert.pem";
$caCertPath = __DIR__ . '/cacert.pem';
file_put_contents($caCertPath, fopen($caCertUrl, 'r'));

// Second attempt with CA certificate
$response = makeCurlRequest($url, $caCertPath);
if ($response['error']) {
    echo "Request failed with CA certificate: " . $response['error'] . PHP_EOL;
    exit(1);
} else {
    echo "Request succeeded with CA certificate" . PHP_EOL;
}

?>
