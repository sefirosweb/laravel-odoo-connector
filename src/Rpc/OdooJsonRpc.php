<?php

declare(strict_types=1);

namespace Sefirosweb\LaravelOdooConnector\Rpc;

use Exception;
use Illuminate\Support\Facades\Http;

class OdooJsonRpc
{
    private static $id = 0;
    private static $instance;
    public $connections = [];

    public function __construct() {}

    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private static function get_connection($connection = 'odoo'): OdooUserLogin
    {
        $instance = self::get_instance();
        if (isset($instance->connections[$connection])) {
            return $instance->connections[$connection];
        }

        $instance->connections[$connection] = new OdooUserLogin(
            config('database.connections.' . $connection . '.host'),
            config('database.connections.' . $connection . '.database'),
            config('database.connections.' . $connection . '.username'),
            config('database.connections.' . $connection . '.password'),
            config('database.connections.' . $connection . '.defaultOptions', [])
        );

        return $instance->connections[$connection];
    }

    public static function execute_kw($model, $operation, $args = [], $kwargs = [], $connection = 'odoo')
    {
        self::$id++;
        $connection = self::get_connection($connection);
        $timeout = $connection->defaultOptions['timeout'] ?? 20;

        if (isset($connection->defaultOptions['context'])) {
            if (!isset($kwargs['context'])) {
                $kwargs['context'] = $connection->defaultOptions['context'];
            } else {
                $kwargs['context'] = array_merge($kwargs['context'], $connection->defaultOptions['context']);
            }
        }

        $query = [
            "jsonrpc" => "2.0",
            "method" => "call",
            "params" => [
                "service" => "object",
                "method" => "execute_kw",
                "args" => [
                    $connection->database,
                    $connection->uid,
                    $connection->password,
                    $model,
                    $operation,
                    $args,
                    $kwargs
                ]
            ],
            "id" => self::$id
        ];

        $response = Http::timeout($timeout)->accept('application/json')->post($connection->url . '/jsonrpc', $query)->json();

        if (!$response) {
            throw new Exception('Cant connect to to odoo server!');
        }

        if (isset($response['error'])) {
            throw new Exception($response['error']['data']['message']);
        }

        return isset($response['result']) ? $response['result'] : true;
    }
}
