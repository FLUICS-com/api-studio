<?php
/**
 * FLUICS Middleware Proxy
 * =======================
 * Forwards requests to the FLUICS CONNECT REST API with proper CORS headers.
 *
 * Why a proxy?
 *   Browsers block direct cross-origin requests to api.fluics.com. This single-file
 *   PHP script runs on the same host as fc-studio.html and relays API calls to
 *   FLUICS, adding the necessary CORS headers so the browser is satisfied.
 *
 * Usage
 *   Place this file in the same directory as fc-studio.html on any PHP host.
 *   The JS app calls: fc-studio-proxy.php?p=/v2/objects
 *   which this script forwards to: https://api.fluics.com/api/public/v2/objects
 *
 * Security
 *   This proxy does NOT store credentials. The Authorization header (Bearer token)
 *   is forwarded transparently from the browser and never persisted here.
 *   API Key IDs and Secrets are stored only in the user's browser localStorage.
 *   Only paths of the public API v2 are forwarded (no other hosts, no ".."),
 *   and upstream redirects are not followed.
 *
 * Copyright (c) 2026 FLUICS GmbH. Released under the MIT License, see LICENSE or
 * https://github.com/FLUICS-com/api-studio/blob/main/LICENSE
 */

define('FC_API', 'https://api.fluics.com/api/public');

// ── CORS headers ───────────────────────────────────────────────────────────────
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── Build target URL ───────────────────────────────────────────────────────────
$path = isset($_GET['p']) ? ltrim($_GET['p'], '/') : '';
if (!$path) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing ?p= parameter']);
    exit;
}

// Strip any extra query params from $path and preserve them
$pathParts = explode('?', $path, 2);
$cleanPath = $pathParts[0];

// Only relay the public API v2: a plain path, no traversal, no other host
if (!preg_match('#^v2/[A-Za-z0-9/_.-]*$#', $cleanPath) || strpos($cleanPath, '..') !== false) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Path not allowed']);
    exit;
}

// Rebuild query string: everything from ?p= onward except 'p' itself
$fwdParams = [];
foreach ($_GET as $k => $v) {
    if ($k !== 'p') $fwdParams[] = urlencode($k) . '=' . urlencode($v);
}
// Also preserve inline query from path part
if (isset($pathParts[1])) $fwdParams[] = $pathParts[1];

$targetUrl = FC_API . '/' . $cleanPath;
if ($fwdParams) $targetUrl .= '?' . implode('&', $fwdParams);

// ── Forward request ────────────────────────────────────────────────────────────
$method  = $_SERVER['REQUEST_METHOD'];
$body    = file_get_contents('php://input');
$headers = [];

// Forward Authorization header
$auth = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
if (!$auth) {
    // Some Apache configs strip the header; reconstruct from $_SERVER
    foreach ($_SERVER as $k => $v) {
        if (strtolower($k) === 'http_authorization') { $auth = $v; break; }
    }
}
if ($auth) $headers[] = 'Authorization: ' . $auth;

// Forward Content-Type
$ct = $_SERVER['CONTENT_TYPE'] ?? ($_SERVER['HTTP_CONTENT_TYPE'] ?? '');
if ($ct) $headers[] = 'Content-Type: ' . $ct;

$response = proxyRequest($method, $targetUrl, $headers, $body);
echo $response;


// ── curl helper ────────────────────────────────────────────────────────────────
function proxyRequest(string $method, string $url, array $headers, string $body): string {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CUSTOMREQUEST  => strtoupper($method),
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);

    if (in_array(strtoupper($method), ['POST', 'PUT', 'PATCH']) && strlen($body) > 0) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }

    $result   = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $mimeType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    http_response_code($httpCode ?: 502);

    // Forward Content-Type from upstream if available
    if ($mimeType) {
        // Strip charset if already set, let PHP handle encoding
        $mime = explode(';', $mimeType)[0];
        header('Content-Type: ' . $mime);
    } else {
        header('Content-Type: application/json');
    }

    if ($result === false) {
        return json_encode(['error' => 'Proxy curl error: ' . $curlErr]);
    }
    return $result;
}
