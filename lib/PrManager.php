<?php
// ============================================================
// PrManager – CRUD PR / Promoter + commissioni
// ============================================================

require_once LIB_PATH . '/JsonStore.php';

class PrManager {
    private JsonStore $store;

    public function __construct() {
        $this->store = new JsonStore(DATA_PATH . '/prs/prs.json');
    }

    // ----------------------------------------------------------
    // CRUD
    // ----------------------------------------------------------
    public function create(array $data): array {
        $pr = [
            'id'              => '',
            'name'            => trim($data['name'] ?? ''),
            'email'           => strtolower(trim($data['email'] ?? '')),
            'phone'           => trim($data['phone'] ?? ''),
            'commission_rate' => (float)($data['commission_rate'] ?? 0.0),
            'link_token'      => bin2hex(random_bytes(12)),
            'active'          => true,
        ];
        return $this->store->insert($pr);
    }

    public function all(): array {
        return $this->store->all();
    }

    public function active(): array {
        return array_values(array_filter($this->all(), fn($p) => !empty($p['active'])));
    }

    public function find(string $id): ?array {
        return $this->store->find($id);
    }

    public function findByToken(string $token): ?array {
        $results = $this->store->where('link_token', $token);
        return $results[0] ?? null;
    }

    public function update(string $id, array $data): ?array {
        return $this->store->update($id, $data);
    }

    public function delete(string $id): bool {
        return $this->store->delete($id);
    }

    // ----------------------------------------------------------
    // Statistiche PR
    // ----------------------------------------------------------
    public function stats(string $prId): array {
        $guestMgr  = new GuestManager();
        $guests    = $guestMgr->byPr($prId);
        $checkedIn = array_filter($guests, fn($g) => !empty($g['checked_in']));
        $pr        = $this->find($prId);
        $totalPax  = array_sum(array_column($guests, 'pax'));
        $checkedPax= array_sum(array_column(iterator_to_array((function() use ($checkedIn) { yield from $checkedIn; })()), 'pax'));

        return [
            'pr'              => $pr,
            'guests_total'    => count($guests),
            'guests_checked'  => count($checkedIn),
            'pax_total'       => $totalPax,
            'pax_checked'     => $checkedPax,
            'commission_earned' => round($checkedPax * ($pr['commission_rate'] ?? 0), 2),
        ];
    }

    public function leaderboard(): array {
        $guestMgr = new GuestManager();
        $result   = [];
        foreach ($this->active() as $pr) {
            $guests  = $guestMgr->byPr($pr['id']);
            $checked = array_filter($guests, fn($g) => !empty($g['checked_in']));
            $result[] = [
                'pr'             => $pr,
                'guests_total'   => count($guests),
                'guests_checked' => count($checked),
                'commission'     => round(count($checked) * ($pr['commission_rate'] ?? 0), 2),
            ];
        }
        usort($result, fn($a, $b) => $b['guests_total'] <=> $a['guests_total']);
        return $result;
    }

    public function registrationUrl(array $pr, string $eventId): string {
        return APP_URL . '/public/register.php?pr=' . urlencode($pr['link_token']) . '&event=' . urlencode($eventId);
    }
}
