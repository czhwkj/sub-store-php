<?php

declare(strict_types=1);

namespace SubStore\Utils;

use PDO;
use PDOException;
use Exception;

/**
 * SQLite 数据库操作类
 */
class Database
{
    private PDO $pdo;
    private string $dbPath;
    
    /**
     * 构造函数
     * @param string $dbPath 数据库文件路径
     */
    public function __construct(string $dbPath)
    {
        $this->dbPath = $dbPath;
        
        // 确保目录存在
        $dir = dirname($dbPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        try {
            $this->pdo = new PDO("sqlite:{$dbPath}");
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // 初始化表结构
            $this->initializeTables();
        } catch (PDOException $e) {
            throw new Exception("数据库连接失败: " . $e->getMessage());
        }
    }
    
    /**
     * 初始化数据库表
     */
    private function initializeTables(): void
    {
        $tables = [
            "CREATE TABLE IF NOT EXISTS settings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                key TEXT UNIQUE NOT NULL,
                value TEXT
            )",
            "CREATE TABLE IF NOT EXISTS subs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT UNIQUE NOT NULL,
                data TEXT NOT NULL,
                created_at INTEGER DEFAULT (strftime('%s', 'now')),
                updated_at INTEGER DEFAULT (strftime('%s', 'now'))
            )",
            "CREATE TABLE IF NOT EXISTS collections (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT UNIQUE NOT NULL,
                data TEXT NOT NULL,
                created_at INTEGER DEFAULT (strftime('%s', 'now')),
                updated_at INTEGER DEFAULT (strftime('%s', 'now'))
            )",
            "CREATE TABLE IF NOT EXISTS artifacts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT UNIQUE NOT NULL,
                data TEXT NOT NULL,
                created_at INTEGER DEFAULT (strftime('%s', 'now')),
                updated_at INTEGER DEFAULT (strftime('%s', 'now'))
            )",
            "CREATE TABLE IF NOT EXISTS files (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                path TEXT UNIQUE NOT NULL,
                content TEXT NOT NULL,
                created_at INTEGER DEFAULT (strftime('%s', 'now')),
                updated_at INTEGER DEFAULT (strftime('%s', 'now'))
            )",
            "CREATE TABLE IF NOT EXISTS tokens (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                token TEXT NOT NULL,
                type TEXT NOT NULL,
                name TEXT NOT NULL,
                exp INTEGER,
                created_at INTEGER DEFAULT (strftime('%s', 'now'))
            )",
            "CREATE TABLE IF NOT EXISTS archives (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                data TEXT NOT NULL,
                created_at INTEGER DEFAULT (strftime('%s', 'now'))
            )",
            "CREATE TABLE IF NOT EXISTS modules (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT UNIQUE NOT NULL,
                data TEXT NOT NULL,
                created_at INTEGER DEFAULT (strftime('%s', 'now')),
                updated_at INTEGER DEFAULT (strftime('%s', 'now'))
            )",
        ];
        
        foreach ($tables as $sql) {
            $this->pdo->exec($sql);
        }
    }
    
    /**
     * 查询单条记录
     * @param string $table 表名
     * @param string $columns 列名
     * @param array $conditions 条件
     * @return array|false
     */
    public function selectOne(string $table, string $columns = '*', array $conditions = []): array|false
    {
        $whereClause = '';
        $params = [];
        
        if (!empty($conditions)) {
            $whereParts = [];
            foreach ($conditions as $key => $value) {
                $whereParts[] = "{$key} = :{$key}";
                $params[$key] = $value;
            }
            $whereClause = 'WHERE ' . implode(' AND ', $whereParts);
        }
        
        $sql = "SELECT {$columns} FROM {$table} {$whereClause} LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetch();
    }
    
    /**
     * 查询多条记录
     * @param string $table 表名
     * @param string $columns 列名
     * @param array $conditions 条件
     * @param string $orderBy 排序
     * @return array
     */
    public function selectAll(string $table, string $columns = '*', array $conditions = [], string $orderBy = ''): array
    {
        $whereClause = '';
        $params = [];
        
        if (!empty($conditions)) {
            $whereParts = [];
            foreach ($conditions as $key => $value) {
                $whereParts[] = "{$key} = :{$key}";
                $params[$key] = $value;
            }
            $whereClause = 'WHERE ' . implode(' AND ', $whereParts);
        }
        
        $orderClause = $orderBy ? "ORDER BY {$orderBy}" : '';
        $sql = "SELECT {$columns} FROM {$table} {$whereClause} {$orderClause}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * 插入记录
     * @param string $table 表名
     * @param array $data 数据
     * @return int 最后插入的ID
     */
    public function insert(string $table, array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        
        return (int) $this->pdo->lastInsertId();
    }
    
    /**
     * 更新记录
     * @param string $table 表名
     * @param array $data 数据
     * @param array $conditions 条件
     * @return int 受影响的行数
     */
    public function update(string $table, array $data, array $conditions): int
    {
        $setParts = [];
        $params = [];
        
        foreach ($data as $key => $value) {
            $setParts[] = "{$key} = :set_{$key}";
            $params["set_{$key}"] = $value;
        }
        
        $whereParts = [];
        foreach ($conditions as $key => $value) {
            $whereParts[] = "{$key} = :where_{$key}";
            $params["where_{$key}"] = $value;
        }
        
        $setClause = implode(', ', $setParts);
        $whereClause = implode(' AND ', $whereParts);
        
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$whereClause}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->rowCount();
    }
    
    /**
     * 删除记录
     * @param string $table 表名
     * @param array $conditions 条件
     * @return int 受影响的行数
     */
    public function delete(string $table, array $conditions): int
    {
        $whereParts = [];
        $params = [];
        
        foreach ($conditions as $key => $value) {
            $whereParts[] = "{$key} = :{$key}";
            $params[$key] = $value;
        }
        
        $whereClause = implode(' AND ', $whereParts);
        $sql = "DELETE FROM {$table} WHERE {$whereClause}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->rowCount();
    }
    
    /**
     * 执行事务
     * @param callable $callback 回调函数
     * @return mixed
     */
    public function transaction(callable $callback): mixed
    {
        try {
            $this->pdo->beginTransaction();
            $result = $callback($this);
            $this->pdo->commit();
            return $result;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
    
    /**
     * 执行原生 SQL
     * @param string $sql SQL 语句
     * @param array $params 参数
     * @return \PDOStatement
     */
    public function execute(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    /**
     * 获取 PDO 实例
     * @return PDO
     */
    public function getPDO(): PDO
    {
        return $this->pdo;
    }
}
