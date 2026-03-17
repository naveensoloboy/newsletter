<?php
// ─── models/SubscriberModel.php ──────────────────────────────
use MongoDB\BSON\UTCDateTime;

class SubscriberModel {
    private \MongoDB\Collection $col;

    public function __construct() {
        $this->col = Database::getInstance()->getCollection('subscribers');
        $this->col->createIndex(['email' => 1, 'newsletter_id' => 1], ['unique' => true]);
    }

    public function subscribe(string $email, ?string $newsletterId = null): bool {
        try {
            $this->col->insertOne([
                'email'          => strtolower(trim($email)),
                'newsletter_id'  => $newsletterId,
                'subscribed_at'  => new UTCDateTime()
            ]);
            return true;
        } catch (\Exception $e) {
            return false; // Already subscribed
        }
    }

    public function getAllEmails(): array {
        $cursor = $this->col->find([], ['projection' => ['email' => 1]]);
        $emails = array_map(fn($d) => (string)$d->email, iterator_to_array($cursor, false));
        return array_unique($emails);
    }

    public function count(): int {
        return $this->col->countDocuments([]);
    }

    public function unsubscribe(string $email): void {
        $this->col->deleteMany(['email' => strtolower(trim($email))]);
    }
}
