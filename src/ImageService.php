<?php
declare(strict_types=1);

final class ImageService {
 private const MAX_BYTES=12582912; private const MAX_IMAGES=10;
 public function __construct(private mysqli $db,private string $root) {}

 public function addJpeg(int $listingId,int $userId,string $bytes): array {
  if(strlen($bytes)>self::MAX_BYTES)throw new InvalidArgumentException('Image exceeds 12 MB.');
  $own=$this->db->prepare("SELECT id FROM listings WHERE id=? AND user_id=? AND status IN ('draft','active')");$own->bind_param('ii',$listingId,$userId);$own->execute();
  if(!$own->get_result()->fetch_assoc())throw new RuntimeException('not_found');
  $count=$this->db->prepare('SELECT COUNT(*) n FROM listing_images WHERE listing_id=?');$count->bind_param('i',$listingId);$count->execute();$n=(int)$count->get_result()->fetch_assoc()['n'];
  if($n>=self::MAX_IMAGES)throw new InvalidArgumentException('Maximum 10 images per advert.');
  $src=@imagecreatefromstring($bytes);if(!$src)throw new InvalidArgumentException('Invalid image.');
  $w=imagesx($src);$h=imagesy($src);if($w<200||$h<200||$w>12000||$h>12000){imagedestroy($src);throw new InvalidArgumentException('Unsupported image dimensions.');}
  $key=bin2hex(random_bytes(24));$dir=rtrim($this->root,'/').'/'.$listingId;if(!is_dir($dir)&&!mkdir($dir,0750,true))throw new RuntimeException('storage');
  [$full,$fw,$fh]=$this->resize($src,2000);[$thumb,$tw,$th]=$this->resize($src,480);imagedestroy($src);
  imagejpeg($full,$dir.'/'.$key.'.jpg',85);imagejpeg($thumb,$dir.'/'.$key.'-thumb.jpg',80);imagedestroy($full);imagedestroy($thumb);
  $sort=$n;$s=$this->db->prepare('INSERT INTO listing_images(listing_id,storage_key,width,height,sort_order) VALUES(?,?,?,?,?)');$s->bind_param('isiii',$listingId,$key,$fw,$fh,$sort);$s->execute();
  return ['id'=>(int)$s->insert_id,'url'=>"/media/listings/$listingId/$key.jpg",'thumbnail_url'=>"/media/listings/$listingId/$key-thumb.jpg",'width'=>$fw,'height'=>$fh,'sort_order'=>$sort];
 }
 public function delete(int $imageId,int $listingId,int $userId): bool {
  $s=$this->db->prepare('SELECT i.storage_key FROM listing_images i JOIN listings l ON l.id=i.listing_id WHERE i.id=? AND i.listing_id=? AND l.user_id=?');$s->bind_param('iii',$imageId,$listingId,$userId);$s->execute();$r=$s->get_result()->fetch_assoc();if(!$r)return false;
  $d=$this->db->prepare('DELETE FROM listing_images WHERE id=?');$d->bind_param('i',$imageId);$d->execute();$dir=rtrim($this->root,'/').'/'.$listingId;@unlink($dir.'/'.$r['storage_key'].'.jpg');@unlink($dir.'/'.$r['storage_key'].'-thumb.jpg');return true;
 }
 public function reorder(int $listingId,int $userId,array $ids): void {
  $own=$this->db->prepare("SELECT id FROM listings WHERE id=? AND user_id=? AND status IN ('draft','active')");$own->bind_param('ii',$listingId,$userId);$own->execute();
  if(!$own->get_result()->fetch_assoc())throw new RuntimeException('not_found');
  $stmt=$this->db->prepare('SELECT id FROM listing_images WHERE listing_id=? ORDER BY sort_order,id');$stmt->bind_param('i',$listingId);$stmt->execute();
  $existing=array_map('intval',array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC),'id'));
  if(count($ids)!==count($existing)||count(array_unique($ids))!==count($ids)||array_diff($ids,$existing)||array_diff($existing,$ids))throw new InvalidArgumentException('Provide every image ID exactly once.');
  $this->db->begin_transaction();
  try {
   $update=$this->db->prepare('UPDATE listing_images SET sort_order=? WHERE id=? AND listing_id=?');
   foreach($ids as $order=>$id){$update->bind_param('iii',$order,$id,$listingId);$update->execute();}
   $this->db->commit();
  }catch(Throwable $e){$this->db->rollback();throw $e;}
 }
 private function resize(GdImage $src,int $max): array {
  $w=imagesx($src);$h=imagesy($src);$scale=min(1,$max/max($w,$h));$nw=max(1,(int)round($w*$scale));$nh=max(1,(int)round($h*$scale));
  $dst=imagecreatetruecolor($nw,$nh);imagecopyresampled($dst,$src,0,0,0,0,$nw,$nh,$w,$h);return[$dst,$nw,$nh];
 }
}
