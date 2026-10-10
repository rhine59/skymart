<?php
declare(strict_types=1);

final class ListingService
{
    public function __construct(private mysqli $db) {}

    public function categories(): array
    {
        $result = $this->db->query('SELECT id, name, slug FROM categories ORDER BY FIELD(slug,'flexwing','avionics','services','parts','miscellaneous','aircraft','instruments','pilot-equipment'), name');
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

    public function create(int $userId, array $input): array
    {
        $categoryId=$this->categoryId($input['category']);
        $input+=['contact_name'=>'','contact_email'=>'','contact_phone'=>''];
        $duration=(int)($input['duration_days']??30);
        if(!in_array($duration,[30,60,90],true))throw new InvalidArgumentException('Invalid duration.');
        $stmt=$this->db->prepare("INSERT INTO listings (user_id,category_id,title,description,price_gbp,location,status,duration_days,contact_name,contact_email,contact_phone,payment_status,published_at,expires_at) VALUES (?,?,?,?,?,?,'active',?,?,?,?,'waived',UTC_TIMESTAMP(),DATE_ADD(UTC_TIMESTAMP(),INTERVAL ? DAY))");
        $stmt->bind_param('iissdsisssi',$userId,$categoryId,$input['title'],$input['description'],$input['price_gbp'],$input['location'],$duration,$input['contact_name'],$input['contact_email'],$input['contact_phone'],$duration);
        $stmt->execute();
        return $this->findOwned((int)$stmt->insert_id,$userId);
    }

    public function updateOwned(int $id,int $userId,array $input): ?array
    {
        if ($this->findOwned($id,$userId) === null) return null;
        $categoryId=$this->categoryId($input['category']);
        $input+=['contact_name'=>'','contact_email'=>'','contact_phone'=>''];
        $duration=(int)($input['duration_days']??30);
        if(!in_array($duration,[30,60,90],true))throw new InvalidArgumentException('Invalid duration.');
        $stmt=$this->db->prepare("UPDATE listings SET category_id=?,title=?,description=?,price_gbp=?,location=?,contact_name=?,contact_email=?,contact_phone=? WHERE id=? AND user_id=? AND status IN ('draft','active')");
        $stmt->bind_param('issdssssii',$categoryId,$input['title'],$input['description'],$input['price_gbp'],$input['location'],$input['contact_name'],$input['contact_email'],$input['contact_phone'],$id,$userId);
        $stmt->execute();
        return $this->findOwned($id,$userId);
    }

    public function withdrawOwned(int $id,int $userId): bool
    {
        $stmt=$this->db->prepare("UPDATE listings SET status='withdrawn' WHERE id=? AND user_id=? AND status IN ('draft','active')");
        $stmt->bind_param('ii',$id,$userId);$stmt->execute();
        return $stmt->affected_rows === 1;
    }

    public function relistOwned(int $id,int $userId,int $duration): bool {
        if(!in_array($duration,[30,60,90],true))throw new InvalidArgumentException('Invalid duration.');
        $stmt=$this->db->prepare("UPDATE listings SET status='active',duration_days=?,payment_status='waived',published_at=UTC_TIMESTAMP(),expires_at=DATE_ADD(UTC_TIMESTAMP(),INTERVAL ? DAY) WHERE id=? AND user_id=? AND (status IN ('expired','withdrawn') OR (status='active' AND expires_at<=UTC_TIMESTAMP()))");
        $stmt->bind_param('iiii',$duration,$duration,$id,$userId);$stmt->execute();return $stmt->affected_rows===1;
    }

    public function mine(int $userId): array
    {
        $stmt=$this->db->prepare("SELECT l.id,l.title,l.description,l.price_gbp,l.location,l.status,l.created_at,l.expires_at,l.duration_days,l.contact_name,l.contact_email,l.contact_phone,
            c.id category_id,c.name category_name,c.slug category_slug,u.id seller_id,u.name seller_name
            FROM listings l JOIN categories c ON c.id=l.category_id JOIN users u ON u.id=l.user_id
            WHERE l.user_id=? ORDER BY l.created_at DESC,l.id DESC");
        $stmt->bind_param('i',$userId);$stmt->execute();
        return array_map(fn($row)=>$this->shape($row),$stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }

    private function findOwned(int $id,int $userId): ?array
    {
        $stmt=$this->db->prepare("SELECT l.id,l.title,l.description,l.price_gbp,l.location,l.status,l.created_at,l.expires_at,l.duration_days,l.contact_name,l.contact_email,l.contact_phone,
            c.id category_id,c.name category_name,c.slug category_slug,u.id seller_id,u.name seller_name
            FROM listings l JOIN categories c ON c.id=l.category_id JOIN users u ON u.id=l.user_id
            WHERE l.id=? AND l.user_id=? LIMIT 1");
        $stmt->bind_param('ii',$id,$userId);$stmt->execute();$row=$stmt->get_result()->fetch_assoc();
        return $row ? $this->shape($row):null;
    }

    private function categoryId(string $slug): int
    {
        $stmt=$this->db->prepare('SELECT id FROM categories WHERE slug=? LIMIT 1');
        $stmt->bind_param('s',$slug);$stmt->execute();$row=$stmt->get_result()->fetch_assoc();
        if (!$row) throw new InvalidArgumentException('Unknown category.');
        return (int)$row['id'];
    }

    private function images(int $listingId): array
    {
        $s=$this->db->prepare('SELECT id,storage_key,width,height,sort_order FROM listing_images WHERE listing_id=? ORDER BY sort_order,id');$s->bind_param('i',$listingId);$s->execute();$out=[];
        foreach($s->get_result()->fetch_all(MYSQLI_ASSOC) as $i)$out[]=['id'=>(int)$i['id'],'url'=>"/media/listings/$listingId/{$i['storage_key']}.jpg",'thumbnail_url'=>"/media/listings/$listingId/{$i['storage_key']}-thumb.jpg",'width'=>(int)$i['width'],'height'=>(int)$i['height'],'sort_order'=>(int)$i['sort_order']];return $out;
    }

    private function shape(array $r): array
    {
        return [
            'id'=>(int)$r['id'],'title'=>$r['title'],
            'category'=>['id'=>(int)$r['category_id'],'name'=>$r['category_name'],'slug'=>$r['category_slug']],
            'price_gbp'=>$r['price_gbp'] === null ? null : (float)$r['price_gbp'],
            'location'=>$r['location'],'description'=>$r['description'],'status'=>$r['status'],
            'seller'=>['id'=>(int)$r['seller_id'],'name'=>$r['seller_name']],
            'duration_days'=>isset($r['duration_days'])?(int)$r['duration_days']:30,'contact_name'=>$r['contact_name']??null,'contact_email'=>$r['contact_email']??null,'contact_phone'=>$r['contact_phone']??null,
            'images'=>$this->images((int)$r['id']),'created_at'=>$r['created_at'],'expires_at'=>$r['expires_at']
        ];
    }
}
