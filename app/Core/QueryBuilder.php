<?php

declare(strict_types=1);

namespace App\Core;

final class QueryBuilder
{
    private Database $database;

    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    /**
     * @param array<int, string> $columns
     * @param array<string, mixed> $where
     * @return array<int, array<string, mixed>>
     */
    public function select(string $table, array $columns = ['*'], array $where = []): array
    {
        $sql = 'SELECT ' . implode(', ', $columns) . ' FROM ' . $table;
        [$whereSql, $params] = $this->buildWhereClause($where);

        return $this->database->fetchAll($sql . $whereSql, $params);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function insert(string $table, array $data): bool
    {
        $columns = array_keys($data);
        $placeholders = array_map(static fn (string $column): string => ':' . $column, $columns);
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        return $this->database->execute($sql, $data);
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $where
     */
    public function update(string $table, array $data, array $where): int
    {
        $assignments = [];
        $params = [];

        foreach ($data as $column => $value) {
            $param = 'set_' . $column;
            $assignments[] = $column . ' = :' . $param;
            $params[$param] = $value;
        }

        [$whereSql, $whereParams] = $this->buildWhereClause($where, 'where_');
        $statement = $this->database->query(
            'UPDATE ' . $table . ' SET ' . implode(', ', $assignments) . $whereSql,
            array_merge($params, $whereParams)
        );

        return $statement->rowCount();
    }

    /**
     * @param array<string, mixed> $where
     */
    public function delete(string $table, array $where): int
    {
        [$whereSql, $params] = $this->buildWhereClause($where, 'where_');
        $statement = $this->database->query('DELETE FROM ' . $table . $whereSql, $params);

        return $statement->rowCount();
    }

    /**
     * @param array<string, mixed> $where
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function buildWhereClause(array $where, string $prefix = ''): array
    {
        if ($where === []) {
            return ['', []];
        }

        $clauses = [];
        $params = [];

        foreach ($where as $column => $value) {
            $param = $prefix . $column;
            $clauses[] = $column . ' = :' . $param;
            $params[$param] = $value;
        }

        return [' WHERE ' . implode(' AND ', $clauses), $params];
    }
}
