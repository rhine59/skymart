<?php
declare(strict_types=1);

final class ListingService
{
    public function __construct(private mysqli $db) {}

    public function categories(): array
    {
        $result = $this->db->query('SELECT id, name, slug FROM categories ORDER BY name');
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function search(?string $query, ?string $category, int $page, int $perPage): array
    {
        $where = ["l.status = 'active'", "(l.expires_at IS NULL OR l.expires_at > UTC_TIMESTAMP())"];
        $types = '';
        $params = [];

        if ($query !== null && $query !== '') {
            $where[] = '(l.title LIKE ? OR l.description LIKE ? OR l.location LIKE ?)';
            $term = '%' . $query . '%';
            $types .= 'sss';
            array_push($params, $term, $term, $term);
        }
        if ($category !== null && $category !== '') {
            $where[] = 'c.slug = ?';
            $types .= 's';
            $params[] = $category;
        }

        $whereSql = implode(' AND ', $where);
        $countSql = "SELECT COUNT(*) total FROM listings l JOIN categories c ON c.id=l.category_id WHERE $whereSql";
        $count = $this->db->prepare($countSql);
        if ($types !== '') $count->bind_param($types, ...$params);
        $count->execute();
        $total = (int)$count->get_result()->fetch_assoc()['total'];

        $offset = ($page - 1) * $perPage;
        $sql = "SELECT l.id,l.title,l.description,l.price_gbp,l.location,l.status,l.created_at,l.expires_at,
                       c.id category_id,c.name category_name,c.slug category_slug,
                       u.id seller_id,u.name seller_name
                FROM listings l
                JOIN categories c ON c.id=l.category_id
                JOIN users u ON u.id=l.user_id
                WHERE $whereSql
                ORDER BY l.created_at DESC,l.id DESC LIMIT ? OFFSET ?";
        $stmt = $this->db->prepare($sql);
        $listTypes = $types . 'ii';
        $listParams = [...$params, $perPage, $offset];
        $stmt->bind_param($listTypes, ...$listParams);
        $stmt->execute();

        $data = [];
        foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) $data[] = $this->shape($row);
        return ['data'=>$data,'meta'=>['page'=>$page,'per_page'=>$perPage,'total'=>$total]];
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT l.id,l.title,l.description,l.price_gbp,l.location,l.status,l.created_at,l.expires_at,
                                            c.id category_id,c.name category_name,c.slug category_slug,
                                            u.id seller_id,u.name seller_name
                                     FROM listings l JOIN categories c ON c.id=l.category_id JOIN users u ON u.id=l.user_id
                                     WHERE l.id=? AND l.status='active' AND (l.expires_at IS NULL OR l.expires_at > UTC_TIMESTAMP()) LIMIT 1");
        $stmt->bind_param('i',$id); $stmt->execute();
        $row=$stmt->get_result()->fetch_assoc();
        return $row ? $this->shape($row) : null;
    }

    private function shape(array $r): array
    {
        return [
            'id'=>(int)$r['id'],'title'=>$r['title'],
            'category'=>['id'=>(int)$r['category_id'],'name'=>$r['category_name'],'slug'=>$r['category_slug']],
            'price_gbp'=>$r['price_gbp'] === null ? null : (float)$r['price_gbp'],
            'location'=>$r['location'],'description'=>$r['description'],'status'=>$r['status'],
            'seller'=>['id'=>(int)$r['seller_id'],'name'=>$r['seller_name']],
            'images'=>[],'created_at'=>$r['created_at'],'expires_at'=>$r['expires_at']
        ];
    }
}
