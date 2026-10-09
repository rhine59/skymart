<?php
declare(strict_types=1);

final class PersonalMarketplaceService {
    public function __construct(private mysqli $db, private ListingService $listings) {}

    public function favourites(int $userId): array {
        $s=$this->db->prepare("SELECT listing_id FROM favourites WHERE user_id=? ORDER BY created_at DESC");
        $s->bind_param('i',$userId);$s->execute();$result=[];
        foreach($s->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
            $listing=$this->listings->find((int)$row['listing_id']);
            if($listing!==null) $result[]=$listing;
        }
        return $result;
    }
    public function favourite(int $userId,int $listingId): bool {
        if($this->listings->find($listingId)===null) return false;
        $s=$this->db->prepare("INSERT IGNORE INTO favourites(user_id,listing_id) VALUES(?,?)");
        $s->bind_param('ii',$userId,$listingId);$s->execute();return true;
    }
    public function unfavourite(int $userId,int $listingId): void {
        $s=$this->db->prepare("DELETE FROM favourites WHERE user_id=? AND listing_id=?");
        $s->bind_param('ii',$userId,$listingId);$s->execute();
    }
    public function searches(int $userId): array {
        $s=$this->db->prepare("SELECT id,name,query_text,category_slug,enabled,frequency,notify_email,notify_sms,notify_whatsapp,created_at,updated_at FROM saved_searches WHERE user_id=? ORDER BY updated_at DESC,id DESC");
        $s->bind_param('i',$userId);$s->execute();return $s->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    public function getSearch(int $userId,int $id): ?array {
        $s=$this->db->prepare("SELECT id,name,query_text,category_slug,enabled,frequency,notify_email,notify_sms,notify_whatsapp,created_at,updated_at FROM saved_searches WHERE user_id=? AND id=?");
        $s->bind_param('ii',$userId,$id);$s->execute();return $s->get_result()->fetch_assoc() ?: null;
    }
    public function saveSearch(int $userId,?int $id,array $input): ?array {
        $name=trim((string)($input['name']??''));
        $query=trim((string)($input['query_text']??''));
        $category=trim((string)($input['category_slug']??''));
        $frequency=(string)($input['frequency']??'daily');
        if(mb_strlen($name)<1||mb_strlen($name)>120||mb_strlen($query)>120||mb_strlen($category)>80||!in_array($frequency,['immediate','daily','weekly'],true)) throw new InvalidArgumentException('Invalid search details.');
        if($category!=='') {
            $valid=array_column($this->listings->categories(),'slug');
            if(!in_array($category,$valid,true)) throw new InvalidArgumentException('Unknown category.');
        }
        $enabled=!empty($input['enabled'])?1:0;
        $email=!empty($input['notify_email'])?1:0;
        $sms=!empty($input['notify_sms'])?1:0;
        $whatsapp=!empty($input['notify_whatsapp'])?1:0;
        // Delivery is deliberately disabled until verified destinations and consent are implemented.
        if($email||$sms||$whatsapp) throw new InvalidArgumentException('Notifications are not yet available; save with delivery channels disabled.');
        if($id===null) {
            $count=count($this->searches($userId));
            if($count>=50) throw new InvalidArgumentException('Maximum 50 saved searches.');
            $s=$this->db->prepare("INSERT INTO saved_searches(user_id,name,query_text,category_slug,enabled,frequency,notify_email,notify_sms,notify_whatsapp) VALUES(?,?,?,?,?,?,?,?,?)");
            $s->bind_param('issssiiii',$userId,$name,$query,$category,$enabled,$frequency,$email,$sms,$whatsapp);
            $s->execute();$id=(int)$s->insert_id;
        } else {
            if($this->getSearch($userId,$id)===null) return null;
            $s=$this->db->prepare("UPDATE saved_searches SET name=?,query_text=?,category_slug=?,enabled=?,frequency=?,notify_email=?,notify_sms=?,notify_whatsapp=? WHERE id=? AND user_id=?");
            $s->bind_param('sssisiiiii',$name,$query,$category,$enabled,$frequency,$email,$sms,$whatsapp,$id,$userId);$s->execute();
        }
        return $this->getSearch($userId,$id);
    }
    public function deleteSearch(int $userId,int $id): void {
        $s=$this->db->prepare("DELETE FROM saved_searches WHERE user_id=? AND id=?");
        $s->bind_param('ii',$userId,$id);$s->execute();
    }
    public function results(int $userId,int $id): ?array {
        $search=$this->getSearch($userId,$id);
        if($search===null) return null;
        return $this->listings->search($search['query_text']?:null,$search['category_slug']?:null,1,50);
    }
}
