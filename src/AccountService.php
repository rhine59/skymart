<?php
declare(strict_types=1);

final class AccountService {
 public function __construct(private mysqli $db) {}

 public function profile(int $id): ?array {
  $s=$this->db->prepare('SELECT id,name,email,phone,role,status,created_at FROM users WHERE id=? LIMIT 1');
  $s->bind_param('i',$id);$s->execute();$r=$s->get_result()->fetch_assoc();return $r ?: null;
 }
 public function updateProfile(int $id,string $name,string $phone): array {
  $s=$this->db->prepare("UPDATE users SET name=?,phone=? WHERE id=? AND status='active'");
  $s->bind_param('ssi',$name,$phone,$id);$s->execute();return $this->profile($id);
 }
 public function changePassword(int $id,string $current,string $new): bool {
  $s=$this->db->prepare("SELECT password FROM users WHERE id=? AND status='active' LIMIT 1");$s->bind_param('i',$id);$s->execute();$r=$s->get_result()->fetch_assoc();
  if(!$r || !password_verify($current,$r['password'])) return false;
  $this->setPassword($id,$new);return true;
 }
 public function issueReset(string $email): ?string {
  $s=$this->db->prepare("SELECT id FROM users WHERE email=? AND status='active' LIMIT 1");$s->bind_param('s',$email);$s->execute();$r=$s->get_result()->fetch_assoc();
  if(!$r) return null;
  $this->db->query("DELETE FROM password_reset_tokens WHERE expires_at<UTC_TIMESTAMP() OR used_at IS NOT NULL");
  $token=bin2hex(random_bytes(32));$hash=hash('sha256',$token,true);$expires=(new DateTimeImmutable('+30 minutes'))->format('Y-m-d H:i:s');
  $i=$this->db->prepare('INSERT INTO password_reset_tokens(user_id,token_hash,expires_at) VALUES(?,?,?)');$i->bind_param('ibs',$r['id'],$hash,$expires);$i->send_long_data(1,$hash);$i->execute();
  return $token;
 }
 public function resetPassword(string $token,string $new): bool {
  if(!preg_match('/^[a-f0-9]{64}$/',$token)) return false;$hash=hash('sha256',$token,true);
  $s=$this->db->prepare('SELECT id,user_id FROM password_reset_tokens WHERE token_hash=? AND used_at IS NULL AND expires_at>UTC_TIMESTAMP() LIMIT 1');
  $s->bind_param('b',$hash);$s->send_long_data(0,$hash);$s->execute();$r=$s->get_result()->fetch_assoc();if(!$r)return false;
  $this->db->begin_transaction();
  try{$this->setPassword((int)$r['user_id'],$new);$u=$this->db->prepare('UPDATE password_reset_tokens SET used_at=UTC_TIMESTAMP() WHERE id=?');$u->bind_param('i',$r['id']);$u->execute();$this->db->commit();return true;}
  catch(Throwable $e){$this->db->rollback();throw $e;}
 }
 public function deactivate(int $id,string $password): bool {
  $s=$this->db->prepare("SELECT password FROM users WHERE id=? AND status='active' LIMIT 1");$s->bind_param('i',$id);$s->execute();$r=$s->get_result()->fetch_assoc();
  if(!$r || !password_verify($password,$r['password'])) return false;
  $u=$this->db->prepare("UPDATE users SET status='deactivated',deactivated_at=UTC_TIMESTAMP() WHERE id=?");$u->bind_param('i',$id);$u->execute();
  $this->revokeAll($id);return true;
 }
 private function setPassword(int $id,string $password=''): void {
  if($password==='') throw new InvalidArgumentException('Password required.');
  $hash=password_hash($password,PASSWORD_DEFAULT);$u=$this->db->prepare('UPDATE users SET password=? WHERE id=?');$u->bind_param('si',$hash,$id);$u->execute();$this->revokeAll($id);
 }
 private function revokeAll(int $id): void {
  $s=$this->db->prepare('UPDATE api_tokens SET revoked_at=COALESCE(revoked_at,UTC_TIMESTAMP()) WHERE user_id=?');$s->bind_param('i',$id);$s->execute();
 }
}
