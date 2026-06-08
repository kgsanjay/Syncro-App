<?php
declare(strict_types=1);

namespace Syncro\Models;

use PDO;
use Exception;

class QueryBuilder
{
    private PDO $pdo;
    private string $table = '';
    private array $columns = ['*'];
    private array $conditions = [];
    private array $params = [];
    private string $orderBy = '';
    private string $limit = ''; // NEW: Added to support pagination and query capping

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function table(string $table): self
    {
        $this->table = $table;
        return $this;
    }

    public function select(array $columns = ['*']): self
    {
        $this->columns = $columns;
        return $this;
    }

    public function where(string $column, $operator, $value = null): self
    {
        // Allow omitting the operator for basic equals logic (e.g., ->where('id', 5))
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        
        $paramName = str_replace('.', '_', $column) . '_' . count($this->params);
        $this->conditions[] = "{$column} {$operator} :{$paramName}";
        $this->params[$paramName] = $value;
        
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orderBy = "ORDER BY {$column} {$direction}";
        return $this;
    }

    /**
     * NEW: Adds LIMIT and OFFSET to the query for pagination
     */
    public function limit(int $limit, int $offset = 0): self
    {
        $this->limit = "LIMIT {$limit} OFFSET {$offset}";
        return $this;
    }

    public function get(): array
    {
        $sql = "SELECT " . implode(', ', $this->columns) . " FROM {$this->table}";
        
        if (!empty($this->conditions)) {
            $sql .= " WHERE " . implode(' AND ', $this->conditions);
        }
        if (!empty($this->orderBy)) {
            $sql .= " {$this->orderBy}";
        }
        if (!empty($this->limit)) {
            $sql .= " {$this->limit}"; // Apply the limit clause
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function first(): ?array
    {
        // Force the query to only fetch 1 row for maximum database performance
        $this->limit(1); 
        $result = $this->get();
        return !empty($result) ? $result[0] : null;
    }

    public function insert(array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_map(fn($col) => ':' . $col, array_keys($data)));
        
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        
        return (int)$this->pdo->lastInsertId();
    }

    public function update(array $data): bool
    {
        if (empty($this->conditions)) {
            throw new Exception("CRITICAL: Update queries must have a WHERE clause to prevent mass overwrites.");
        }

        $setParts = [];
        foreach ($data as $column => $value) {
            $setParts[] = "{$column} = :update_{$column}";
            $this->params["update_{$column}"] = $value;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $setParts);
        $sql .= " WHERE " . implode(' AND ', $this->conditions);

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($this->params);
    }

    public function delete(): bool
    {
        if (empty($this->conditions)) {
            throw new Exception("CRITICAL: Delete queries must have a WHERE clause to prevent mass deletions.");
        }

        $sql = "DELETE FROM {$this->table} WHERE " . implode(' AND ', $this->conditions);
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($this->params);
    }
}