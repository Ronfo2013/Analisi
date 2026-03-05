<?php
// ============================================================
// EventManager – CRUD eventi
// ============================================================

require_once LIB_PATH . '/JsonStore.php';

class EventManager {
    private JsonStore $store;

    public function __construct() {
        $this->store = new JsonStore(DATA_PATH . '/events/events.json');
    }

    // ----------------------------------------------------------
    // CRUD
    // ----------------------------------------------------------
    public function create(array $data): array {
        $event = [
            'id'          => '',
            'name'        => trim($data['name'] ?? ''),
            'date'        => $data['date'] ?? '',
            'time_open'   => $data['time_open'] ?? '22:00',
            'time_close'  => $data['time_close'] ?? '06:00',
            'location'    => trim($data['location'] ?? ''),
            'dj'          => trim($data['dj'] ?? ''),
            'theme'       => trim($data['theme'] ?? ''),
            'tables_total'=> (int)($data['tables_total'] ?? 0),
            'max_guests'  => (int)($data['max_guests'] ?? 0),
            'description' => trim($data['description'] ?? ''),
            'active'      => true,
            'guests'      => [],
        ];
        return $this->store->insert($event);
    }

    public function all(): array {
        $events = $this->store->all();
        usort($events, fn($a, $b) => strcmp($b['date'] ?? '', $a['date'] ?? ''));
        return $events;
    }

    public function active(): array {
        return array_values(array_filter($this->all(), fn($e) => !empty($e['active'])));
    }

    public function upcoming(): array {
        $today = date('Y-m-d');
        return array_values(array_filter($this->active(), fn($e) => ($e['date'] ?? '') >= $today));
    }

    public function find(string $id): ?array {
        return $this->store->find($id);
    }

    public function update(string $id, array $data): ?array {
        return $this->store->update($id, $data);
    }

    public function delete(string $id): bool {
        return $this->store->delete($id);
    }

    // ----------------------------------------------------------
    // Statistiche
    // ----------------------------------------------------------
    public function stats(string $eventId): array {
        $guestMgr = new GuestManager();
        $guests   = $guestMgr->byEvent($eventId);
        $checkins = array_filter($guests, fn($g) => !empty($g['checked_in']));
        $vips     = array_filter($guests, fn($g) => !empty($g['vip']));
        $event    = $this->find($eventId);

        return [
            'total_guests'   => count($guests),
            'checked_in'     => count($checkins),
            'vip_total'      => count($vips),
            'vip_checked_in' => count(array_filter($vips, fn($g) => !empty($g['checked_in']))),
            'tables_used'    => $this->tablesUsed($eventId),
            'tables_total'   => (int)($event['tables_total'] ?? 0),
        ];
    }

    private function tablesUsed(string $eventId): int {
        $guestMgr = new GuestManager();
        $tables   = array_column($guestMgr->byEvent($eventId), 'table_number');
        return count(array_filter(array_unique($tables)));
    }
}
