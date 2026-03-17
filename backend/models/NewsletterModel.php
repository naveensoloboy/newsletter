<?php
// ─── models/NewsletterModel.php ──────────────────────────────
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

class NewsletterModel {
    private \MongoDB\Collection $col;

    public function __construct() {
        $this->col = Database::getInstance()->getCollection('newsletters');
    }

    public function create(array $data, string $createdBy): string {
        $result = $this->col->insertOne([
            'organization_name'   => $data['organization_name']   ?? '',
            'department'          => $data['department']          ?? '',
            'newsletter_title'    => $data['newsletter_title']    ?? '',
            'edition'             => $data['edition']             ?? '',
            'publish_date'        => $data['publish_date']        ?? '',
            'editor_name'         => $data['editor_name']         ?? '',
            'headline'            => $data['headline']            ?? '',
            'intro_content'       => $data['intro_content']       ?? '',
            'faculty_achievements'=> $data['faculty_achievements']?? '',
            'student_achievements'=> $data['student_achievements']?? '',
            'upcoming_events'     => $data['upcoming_events']     ?? '',
            'hod_message'         => $data['hod_message']         ?? '',
            'contact_email'       => $data['contact_email']       ?? '',
            'website'             => $data['website']             ?? '',
            'status'              => 'draft',
            'created_by'          => $createdBy,
            'created_at'          => new UTCDateTime(),
            'updated_at'          => new UTCDateTime()
        ]);
        return (string) $result->getInsertedId();
    }

    public function findById(string $id): ?array {
        try {
            $doc = $this->col->findOne(['_id' => new ObjectId($id)]);
            return $doc ? $this->toArray($doc) : null;
        } catch (\Exception $e) { return null; }
    }

    public function getAll(array $filter = []): array {
        $cursor = $this->col->find($filter, ['sort' => ['created_at' => -1]]);
        return array_map([$this, 'toArray'], iterator_to_array($cursor, false));
    }

    public function getPublished(?string $department = null, ?string $search = null): array {
        $filter = ['status' => 'published'];
        if ($department) $filter['department'] = $department;
        if ($search) {
            $filter['$or'] = [
                ['newsletter_title' => new \MongoDB\BSON\Regex($search, 'i')],
                ['headline'         => new \MongoDB\BSON\Regex($search, 'i')],
                ['organization_name'=> new \MongoDB\BSON\Regex($search, 'i')]
            ];
        }
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

    public function serialize(array $n): array {
        return [
            'id'                   => $n['_id'],
            'organization_name'    => $n['organization_name']    ?? '',
            'department'           => $n['department']           ?? '',
            'newsletter_title'     => $n['newsletter_title']     ?? '',
            'edition'              => $n['edition']              ?? '',
            'publish_date'         => $n['publish_date']         ?? '',
            'editor_name'          => $n['editor_name']          ?? '',
            'headline'             => $n['headline']             ?? '',
            'intro_content'        => $n['intro_content']        ?? '',
            'faculty_achievements' => $n['faculty_achievements'] ?? '',
            'student_achievements' => $n['student_achievements'] ?? '',
            'upcoming_events'      => $n['upcoming_events']      ?? '',
            'hod_message'          => $n['hod_message']          ?? '',
            'contact_email'        => $n['contact_email']        ?? '',
            'website'              => $n['website']              ?? '',
            'status'               => $n['status']               ?? 'draft',
            'created_by'           => $n['created_by']           ?? '',
            'created_at'           => $n['created_at']           ?? null,
            'updated_at'           => $n['updated_at']           ?? null
        ];
    }

    private function toArray($doc): array {
        $arr = json_decode(json_encode($doc), true);
        $arr['_id'] = (string) $doc->_id;
        foreach (['created_at','updated_at'] as $f) {
            if (isset($doc->$f)) $arr[$f] = date('Y-m-d\TH:i:s\Z', $doc->$f->toDateTime()->getTimestamp());
        }
        return $arr;
    }
}
