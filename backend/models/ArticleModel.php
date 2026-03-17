<?php
// ─── models/ArticleModel.php ─────────────────────────────────
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

class ArticleModel {
    private \MongoDB\Collection $col;

    public function __construct() {
        $this->col = Database::getInstance()->getCollection('articles');
        $this->col->createIndex(['newsletter_id' => 1]);
    }

    public function create(string $newsletterId, string $title, string $author, string $content, string $image, string $createdBy): string {
        $result = $this->col->insertOne([
            'newsletter_id'    => $newsletterId,
            'title'            => $title,
            'author'           => $author,
            'content'          => $content,
            'image'            => $image,
            'status'           => 'draft',
            'rejection_reason' => '',
            'created_by'       => $createdBy,
            'created_at'       => new UTCDateTime(),
            'updated_at'       => new UTCDateTime()
        ]);
        return (string) $result->getInsertedId();
    }

    public function findById(string $id): ?array {
        try {
            $doc = $this->col->findOne(['_id' => new ObjectId($id)]);
            return $doc ? $this->toArray($doc) : null;
        } catch (\Exception $e) { return null; }
    }

    public function getByNewsletter(string $newsletterId): array {
        $cursor = $this->col->find(['newsletter_id' => $newsletterId], ['sort' => ['created_at' => 1]]);
        return array_map([$this, 'toArray'], iterator_to_array($cursor, false));
    }

    public function getByUser(string $userId): array {
        $cursor = $this->col->find(['created_by' => $userId], ['sort' => ['created_at' => -1]]);
        return array_map([$this, 'toArray'], iterator_to_array($cursor, false));
    }

    public function getAll(?string $status = null): array {
        $filter = $status ? ['status' => $status] : [];
        $cursor = $this->col->find($filter, ['sort' => ['created_at' => -1]]);
        return array_map([$this, 'toArray'], iterator_to_array($cursor, false));
    }

    public function update(string $id, array $data): void {
        $data['updated_at'] = new UTCDateTime();
        $this->col->updateOne(['_id' => new ObjectId($id)], ['$set' => $data]);
    }

    public function delete(string $id): void {
        $this->col->deleteOne(['_id' => new ObjectId($id)]);
    }

    private function toArray($doc): array {
        $arr = json_decode(json_encode($doc), true);
        $arr['_id'] = (string) $doc->_id;
        foreach (['created_at','updated_at'] as $f) {
            if (isset($doc->$f)) $arr[$f] = date('Y-m-d\TH:i:s\Z', $doc->$f->toDateTime()->getTimestamp());
        }
        return $arr;
    }

    public function serialize(array $a): array {
        // image field may be a JSON array of URLs or a single URL string
        $raw    = $a['image'] ?? '';
        $images = [];
        if ($raw) {
            $decoded = json_decode($raw, true);
            $images  = is_array($decoded) ? $decoded : [$raw];
        }
        return [
            'id'               => $a['_id'],
            'newsletter_id'    => $a['newsletter_id']    ?? '',
            'title'            => $a['title']            ?? '',
            'author'           => $a['author']           ?? '',
            'content'          => $a['content']          ?? '',
            'image'            => $raw,       // raw value kept for backward compat
            'images'           => $images,    // always an array
            'status'           => $a['status']           ?? 'draft',
            'rejection_reason' => $a['rejection_reason'] ?? '',
            'created_by'       => $a['created_by']       ?? '',
            'created_at'       => $a['created_at']       ?? null
        ];
    }
}