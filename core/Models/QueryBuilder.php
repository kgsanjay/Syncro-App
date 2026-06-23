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
    private array $joins = [];
    private array $conditions = [];
    private array $params = [];
    private string $orderBy = '';
    private string $limit = ''; // NEW: Added to support pagination and query capping

    protected static ?int $globalTenantId = null;
    protected bool $bypassTenantScope = false;
    protected bool $tenantScopeApplied = false;

    // Soft delete properties
    protected bool $useSoftDeletes = true;
    protected bool $withDeletes = false;
    protected bool $softDeleteScopeApplied = false;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public static function setTenantId(int $hotelId): void
    {
        self::$globalTenantId = $hotelId;
    }

    public function withoutTenantScope(): self
    {
        $this->bypassTenantScope = true;
        return $this;
    }

    private function applyTenantScope(): void
    {
        if ($this->bypassTenantScope || $this->tenantScopeApplied || self::$globalTenantId === null) {
            return;
        }

        $globalTables = ['users', 'hotels', 'super_admins', 'system_settings', 'phinxlog'];
        if (in_array($this->table, $globalTables)) {
            return;
        }

        $this->conditions[] = "hotel_id = :global_tenant_id";
        $this->params['global_tenant_id'] = self::$globalTenantId;
        
        $this->tenantScopeApplied = true;
    }

    public function withTrashed(): self
    {
        $this->withDeletes = true;
        return $this;
    }

    private function applySoftDeleteScope(): void
    {
        if (!$this->useSoftDeletes || $this->withDeletes || $this->softDeleteScopeApplied) {
            return;
        }

        $softDeleteTables = ['hotels', 'guests', 'reservations', 'invoices'];
        if (!in_array($this->table, $softDeleteTables)) {
            return;
        }

        $this->conditions[] = "{$this->table}.deleted_at IS NULL";
        $this->softDeleteScopeApplied = true;
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

    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER'): self
    {
        $this->joins[] = "{$type} JOIN {$table} ON {$first} {$operator} {$second}";
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
        $this->applyTenantScope();
        $this->applySoftDeleteScope();
        $sql = "SELECT " . implode(', ', $this->columns) . " FROM {$this->table}";
        
        if (!empty($this->joins)) {
            $sql .= " " . implode(' ', $this->joins);
        }
        
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

    public function paginate(int $perPage = 15, int $page = 1): array
    {
        $offset = ($page - 1) * $perPage;

        $this->applyTenantScope();
        $this->applySoftDeleteScope();

        $countSql = "SELECT COUNT(*) as total FROM {$this->table}";
        
        if (!empty($this->joins)) {
            $countSql .= " " . implode(' ', $this->joins);
        }
        
        if (!empty($this->conditions)) {
            $countSql .= " WHERE " . implode(' AND ', $this->conditions);
        }

        $countStmt = $this->pdo->prepare($countSql);
        $countStmt->execute($this->params);
        $totalResult = $countStmt->fetch(PDO::FETCH_ASSOC);
        $totalRecords = $totalResult ? (int)$totalResult['total'] : 0;

        $this->limit($perPage, $offset);
        $results = $this->get();

        return [
            'data' => $results,
            'total' => $totalRecords,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => (int)ceil($totalRecords / $perPage)
        ];
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
        $this->applyTenantScope();

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
        $this->applyTenantScope();

        if (empty($this->conditions)) {
            throw new Exception("CRITICAL: Delete queries must have a WHERE clause to prevent mass deletions.");
        }

        $softDeleteTables = ['hotels', 'guests', 'reservations', 'invoices'];
        $canSoftDelete = $this->useSoftDeletes && in_array($this->table, $softDeleteTables);

        if ($canSoftDelete) {
            $sql = "UPDATE {$this->table} SET deleted_at = NOW() WHERE " . implode(' AND ', $this->conditions);
        } else {
            $sql = "DELETE FROM {$this->table} WHERE " . implode(' AND ', $this->conditions);
        }
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($this->params);
    }

    public function forceDelete(): bool
    {
        $this->applyTenantScope();

        if (empty($this->conditions)) {
            throw new Exception("CRITICAL: Delete queries must have a WHERE clause to prevent mass deletions.");
        }

        $sql = "DELETE FROM {$this->table} WHERE " . implode(' AND ', $this->conditions);
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($this->params);
    }
}