<?php

declare(strict_types=1);

namespace Sefirosweb\LaravelOdooConnector\Rpc;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class OdooJsonRpc
{
    private static $id = 0;
    private static $instance;
    public $conections = [];

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
        if (isset($instance->conections[$connection])) {
            return $instance->conections[$connection];
        }

        $instance->conections[$connection] = new OdooUserLogin(
            config('database.connections.' . $connection . '.host'),
            config('database.connections.' . $connection . '.database'),
            config('database.connections.' . $connection . '.username'),
            config('database.connections.' . $connection . '.password'),
            config('database.connections.' . $connection . '.defaultOptions', [])
        );

        return $instance->conections[$connection];
    }

    public static function execute_kw($model, $operation, $params = [], $object = [], $connection = 'odoo', $cache = false)
    {
        self::$id++;
        $connection = self::get_connection($connection);
        $timeout = $connection->defaultOptions['timeout'] ?? 20;

        if (isset($connection->defaultOptions['context']) && !isset($object['context'])) {
            $object['context'] = $connection->defaultOptions['context'];
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
                    $params,
                    $object
                ]
            ],
            "id" => self::$id
        ];

        if ($cache) {
            $response = Cache::get(self::cache_key($query), function () use ($query, $timeout, $connection) {
                return Http::timeout($timeout)->accept('application/json')->post($connection->url . '/jsonrpc', $query)->json();
            });
        } else {
            $response = Http::timeout($timeout)->accept('application/json')->post($connection->url . '/jsonrpc', $query)->json();
        }


        if (!$response) {
            throw new Exception('Cant connect to to odoo server!');
        }

        if (isset($response['error'])) {
            throw new Exception($response['error']['data']['message']);
        }

        return isset($response['result']) ? $response['result'] : true;
    }

    private static function cache_key($options)
    {
        return 'Odoo_cache_' . md5(json_encode($options));
    }
}
