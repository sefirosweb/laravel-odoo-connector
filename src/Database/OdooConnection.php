<?php

declare(strict_types=1);

namespace Sefirosweb\LaravelOdooConnector\Database;

use Illuminate\Database\Connection;
use Sefirosweb\LaravelOdooConnector\Rpc\OdooJsonRpc;

class OdooConnection extends Connection
{
    /**
     * Odoo driver: $query is a JSON-RPC payload compiled by OdooGrammar, not SQL.
     *
     * @param  array{model: string, operation: string, params: array, object: array}  $query
     */
    public function select($query, $bindings = [], $useReadPdo = true, array $fetchUsing = [])
    {
        $data = OdooJsonRpc::execute_kw(
            $query['model'],
            $query['operation'],
            $query['params'],
            $query['object'],
            $this->config['connection_name']
        );

        return $data;
    }

    /**
     * @param  array{model: string, operation: string, params: array, object: array}  $query
     */
    public function insert($query, $bindings = [])
    {
        $data = OdooJsonRpc::execute_kw(
            $query['model'],
            $query['operation'],
            $query['params'],
            $query['object'],
            $this->config['connection_name']
        );

        return $data;
    }

    /**
     * @param  array{model: string, operation: string, params: array, object: array}  $query
     */
    public function update($query, $bindings = [])
    {
        $data = OdooJsonRpc::execute_kw(
            $query['model'],
            $query['operation'],
            $query['params'],
            $query['object'],
            $this->config['connection_name']
        );

        return $data;
    }

    /**
     * @param  array{model: string, operation: string, params: array, object: array}  $query
     */
    public function delete($query, $bindings = [])
    {
        $data = OdooJsonRpc::execute_kw(
            $query['model'],
            $query['operation'],
            $query['params'],
            $query['object'],
            $this->config['connection_name']
        );

        return $data;
    }

    protected function getDefaultPostProcessor()
    {
        return new OdooProcessor;
    }
}
