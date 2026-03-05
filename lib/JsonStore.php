<?php
// ============================================================
// JsonStore – Layer di persistenza su file JSON
// ============================================================

class JsonStore {
    private string $file;
    private array  $data = [];

    public function __construct(string $file) {
        $this->file = $file;
        $this->load();
    }

    // ----------------------------------------------------------
    // Lettura
    // ----------------------------------------------------------
    private function load(): void {
        if (!file_exists($this->file)) {
            $this->data = [];
            return;
        }
        $raw = file_get_contents($this->file);
        $this->data = json_decode($raw, true) ?? [];
    }

    public function all(): array {
        return $this->data;
    }

    public function find(string $id): ?array {
        foreach ($this->data as $item) {
            if (($item['id'] ?? null) === $id) {
                return $item;
            }
        }
        return null;
    }

    public function where(string $field, mixed $value): array {
        return array_values(array_filter($this->data, fn($item) => ($item[$field] ?? null) === $value));
    }

    public function whereIn(string $field, array $values): array {
        return array_values(array_filter($this->data, fn($item) => in_array($item[$field] ?? null, $values, true)));
    }

    // ----------------------------------------------------------
    // Scrittura
    // ----------------------------------------------------------
    public function insert(array $record): array {
        if (empty($record['id'])) {
            $record['id'] = $this->generateId();
        }
        if (empty($record['created_at'])) {
            $record['created_at'] = date('c');
        }
        $this->data[] = $record;
        $this->save();
        return $record;
    }

    public function update(string $id, array $fields): ?array {
        foreach ($this->data as &$item) {
            if (($item['id'] ?? null) === $id) {
                $item = array_merge($item, $fields, ['updated_at' => date('c')]);
                $this->save();
                return $item;
            }
        }
        return null;
    }

    public function delete(string $id): bool {
        $before = count($this->data);
        $this->data = array_values(array_filter($this->data, fn($item) => ($item['id'] ?? null) !== $id));
        if (count($this->data) < $before) {
            $this->save();
            return true;
        }
        return false;
    }

    public function count(): int {
        return count($this->data);
    }

    public function countWhere(string $field, mixed $value): int {
        return count($this->where($field, $value));
    }

    // ----------------------------------------------------------
    // Utilità
    // ----------------------------------------------------------
    private function save(): void {
        $dir = dirname($this->file);
        if (!is_dir($dir)) {
            mkdir($dir, 0750, true);
        }
        file_put_contents($this->file, json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    }

    private function generateId(): string {
        return bin2hex(random_bytes(8));
    }
}
