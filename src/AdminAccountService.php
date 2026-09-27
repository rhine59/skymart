<?php
declare(strict_types=1);

final class AdminAccountService {
 public function __construct(private mysqli $db) {}
 public function list(string $q=''): array {
  $term='%'.$q.'%';$s=$this->db->prepare("SELECT id,name,email,phone,role,status,created_at FROM users WHERE (?='' OR name LIKE ? OR email LIKE ?) ORDER BY created_at DESC LIMIT 100");
  $s->bind_param('sss',$q,$term,$term);$s->execute();return $s->get_result()->fetch_all(MYSQLI_ASSOC);
 }
 public function setStatus(int $id,string $status): bool {
  if(!in_array($status,['active','disabled'],true))return false;
  $s=$this->db->prepare('UPDATE users SET status=?,deactivated_at=NULL WHERE id=? AND status<>\'deactivated\'');$s->bind_param('si',$status,$id);$s->execute();
  if($status==='disabled'){$r=$this->db->prepare('UPDATE api_tokens SET revoked_at=COALESCE(revoked_at,UTC_TIMESTAMP()) WHERE user_id=?');$r->bind_param('i',$id);$r->execute();}
  return $s->affected_rows===1;
 }
 public function revokeDevices(int $id): void {$s=$this->db->prepare('UPDATE api_tokens SET revoked_at=COALESCE(revoked_at,UTC_TIMESTAMP()) WHERE user_id=?');$s->bind_param('i',$id);$s->execute();}
}
