<?php
// ─── models/UserModel.php ────────────────────────────────────
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

class UserModel {
    private \MongoDB\Collection $col;

    public function __construct() {
        $this->col = Database::getInstance()->getCollection('users');
        // Unique index on email
        $this->col->createIndex(['email' => 1], ['unique' => true]);
    }

    public function create(string $name, string $email, string $password, string $role = 'faculty', string $department = ''): string {
        $result = $this->col->insertOne([
            'name'       => $name,
            'email'      => strtolower(trim($email)),
            'password'   => Auth::hashPassword($password),
            'role'       => $role,
            'department' => $department,
            'is_active'  => true,
            'created_at' => new UTCDateTime()
        ]);
        return (string) $result->getInsertedId();
    }

    public function findByEmail(string $email): ?array {
        $doc = $this->col->findOne(['email' => strtolower(trim($email))]);
        return $doc ? $this->toArray($doc) : null;
    }

    public function findById(string $id): ?array {
        try {
            $doc = $this->col->findOne(['_id' => new ObjectId($id)]);
            return $doc ? $this->toArray($doc) : null;
        } catch (\Exception $e) { return null; }
    }

    public function getAll(?string $role = null): array {
        $filter = $role ? ['role' => $role] : [];
        $cursor = $this->col->find($filter, ['projection' => ['password' => 0]]);
        return array_map([$this, 'toArray'], iterator_to_array($cursor, false));
    }

    public function update(string $id, array $data): void {
        if (isset($data['password'])) {
            $data['password'] = Auth::hashPassword($data['password']);
        }
        $this->col->updateOne(['_id' => new ObjectId($id)], ['$set' => $data]);
    }

    public function delete(string $id): void {
        $this->col->deleteOne(['_id' => new ObjectId($id)]);
    }

    public function serialize(array $u): array {
        return [
            'id'         => $u['_id'],
            'name'       => $u['name'],
            'email'      => $u['email'],
            'role'       => $u['role'],
            'department' => $u['department'] ?? '',
            'is_active'  => $u['is_active'] ?? true,
            'created_at' => $u['created_at'] ?? null
        ];
    }

    private function toArray($doc): array {
        $arr = (array) $doc->jsonSerialize();
        $arr['_id'] = (string) $doc->_id;
        if (isset($arr['created_at'])) {
            $arr['created_at'] = date('Y-m-d\TH:i:s\Z', $arr['created_at']->toDateTime()->getTimestamp());
        }
        return $arr;
    }
}
