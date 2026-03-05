<?php
// ============================================================
// GuestManager – CRUD ospiti e check-in
// ============================================================

require_once LIB_PATH . '/JsonStore.php';

class GuestManager {
    private JsonStore $store;

    public function __construct() {
        $this->store = new JsonStore(DATA_PATH . '/guests/guests.json');
    }

    // ----------------------------------------------------------
    // CRUD
    // ----------------------------------------------------------
    public function create(array $data): array {
        $guest = [
            'id'           => '',
            'event_id'     => $data['event_id'] ?? '',
            'pr_id'        => $data['pr_id'] ?? null,
            'name'         => trim($data['name'] ?? ''),
            'phone'        => trim($data['phone'] ?? ''),
            'email'        => strtolower(trim($data['email'] ?? '')),
            'vip'          => (bool)($data['vip'] ?? false),
            'table_number' => $data['table_number'] ?? null,
            'note'         => trim($data['note'] ?? ''),
            'pax'          => max(1, (int)($data['pax'] ?? 1)),
            'checked_in'   => false,
            'checkin_at'   => null,
            'qr_token'     => bin2hex(random_bytes(16)),
        ];
        return $this->store->insert($guest);
    }

    public function find(string $id): ?array {
        return $this->store->find($id);
    }

    public function findByToken(string $token): ?array {
        $results = $this->store->where('qr_token', $token);
        return $results[0] ?? null;
    }

    public function byEvent(string $eventId): array {
        return $this->store->where('event_id', $eventId);
    }

    public function byPr(string $prId): array {
        return $this->store->where('pr_id', $prId);
    }

    public function all(): array {
        return $this->store->all();
    }

    public function update(string $id, array $data): ?array {
        return $this->store->update($id, $data);
    }

    public function delete(string $id): bool {
        return $this->store->delete($id);
    }

    // ----------------------------------------------------------
    // Check-in
    // ----------------------------------------------------------
    public function checkIn(string $qrToken): array {
        $guest = $this->findByToken($qrToken);
        if (!$guest) {
            return ['success' => false, 'message' => 'QR non valido o ospite non trovato.'];
        }
        if (!empty($guest['checked_in'])) {
            return [
                'success' => false,
                'message' => 'Ospite già registrato alle ' . date(DATETIME_FORMAT, strtotime($guest['checkin_at'])),
                'guest'   => $guest,
            ];
        }
        $updated = $this->store->update($guest['id'], [
            'checked_in' => true,
            'checkin_at' => date('c'),
        ]);
        return ['success' => true, 'message' => 'Check-in effettuato!', 'guest' => $updated];
    }

    // ----------------------------------------------------------
    // Ricerca
    // ----------------------------------------------------------
    public function search(string $eventId, string $query): array {
        $query  = strtolower(trim($query));
        $guests = $this->byEvent($eventId);
        return array_values(array_filter($guests, function ($g) use ($query) {
            return str_contains(strtolower($g['name'] ?? ''), $query)
                || str_contains($g['phone'] ?? '', $query)
                || str_contains(strtolower($g['email'] ?? ''), $query);
        }));
    }

    // ----------------------------------------------------------
    // QR URL
    // ----------------------------------------------------------
    public function qrUrl(array $guest): string {
        $checkUrl = APP_URL . '/public/checkin.php?token=' . urlencode($guest['qr_token']);
        return QR_BASE_URL . urlencode($checkUrl);
    }
}
