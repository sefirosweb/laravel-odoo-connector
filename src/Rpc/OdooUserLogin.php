<?php

declare(strict_types=1);

namespace Sefirosweb\LaravelOdooConnector\Rpc;

use Exception;
use Illuminate\Support\Facades\Http;

class OdooUserLogin
{
    public $uid = null;
    public function __construct(
        public $url,
        public $database,
        public $username,
        public $password,
        public $defaultOptions = []
    ) {
        $this->login();
    }

    private function login()
    {
        $data = [
            "jsonrpc" => "2.0",
            "method" => "call",
            "params" => [
                "service" => "common",
                "method" => "login",
                "args" => [
                    $this->database,
                    $this->username,
                    $this->password
                ]
            ]
        ];

        $timeout = $this->defaultOptions['timeout'] ?? 20;

        $response = Http::timeout($timeout)->accept('application/json')->post($this->url . '/jsonrpc', $data)->json();

        if (!$response) {
            throw new Exception(sprintf(
                'Cannot reach Odoo server at %s (empty or non-JSON response).',
                $this->url,
            ));
        }

        if (isset($response['error'])) {
            throw new Exception($response['error']['data']['message']);
        }

        // Odoo's common.login returns `false` (not an error payload) when the
        // database does not exist or the username / password pair is invalid.
        // Detecting this here turns a silent misconfiguration into a loud,
        // actionable error instead of producing confusing downstream failures.
        if ($response['result'] === false || $response['result'] === null) {
            throw new Exception(sprintf(
                'Odoo authentication failed against %s (database=%s, user=%s).',
                $this->url,
                $this->database,
                $this->username,
            ));
        }

        $this->uid = $response['result'];
        return $this->uid;
    }
}
