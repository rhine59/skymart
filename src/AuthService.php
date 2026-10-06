<?php
declare(strict_types=1);

final class AuthService {
 public function __construct(private mysqli $db) {}

 public function login(string $email,string $password,string $label='iPhone'): ?array {
  $stmt=$this->db->prepare("SELECT id,name,email,password FROM users WHERE email=? AND status='active' LIMIT 1");
  $stmt->bind_param('s',$email);$stmt->execute();$user=$stmt->get_result()->fetch_assoc();
  if (!$user || !password_verify($password,$user['password'])) return null;
  if (password_needs_rehash($user['password'],PASSWORD_DEFAULT)) {
   $hash=password_hash($password,PASSWORD_DEFAULT);$u=$this->db->prepare('UPDATE users SET password=? WHERE id=?');
   $u->bind_param('si',$hash,$user['id']);$u->execute();
  }
  $token=bin2hex(random_bytes(32));$tokenHash=hash('sha256',$token,true);$expires=(new DateTimeImmutable('+30 days'))->format('Y-m-d H:i:s');
  $insert=$this->db->prepare('INSERT INTO api_tokens(user_id,token_hash,label,expires_at) VALUES(?,?,?,?)');
  $insert->bind_param('ibss',$user['id'],$tokenHash,$label,$expires);
  // mysqli blob binding requires send_long_data for binary token hash.
  $insert->send_long_data(1,$tokenHash);$insert->execute();
  return ['token'=>$token,'expires_at'=>$expires,'user'=>['id'=>(int)$user['id'],'name'=>$user['name'],'email'=>$user['id']]];
 }

 public function authenticate(string $token): ?array {
  if (!preg_match('/^[a-f0-9]{64}$/',$token)) return null;
  $hash=hash('sha256',$token,true);
  $stmt=$this->db->prepare("SELECT t.id token_id,u.id,u.name,u.email FROM api_tokens t JOIN users u ON u.id=t.user_id WHERE t.token_hash=? AND u.status='active' AND t.revoked_at IS NULL AND t.expires_at>UTC_TIMESTAMP() LIMIT 1");
  $stmt->bind_param('b',$hash);$stmt->send_long_data(0,$hash);$stmt->execute();$row=$stmt->get_result()->fetch_assoc();
  if (!$row) return null;
  $touch=$this->db->prepare('UPDATE api_tokens SET last_used_at=UTC_TIMESTAMP() WHERE id=? AND (last_used_at IS NULL OR last_used_at<UTC_TIMESTAMP()-INTERVAL 1 HOUR)');
  $touch->bind_param('i',$row['token_id']);$touch->execute();
  return ['token_id'=>(int)$row['token_id'],'id'=>(int)$row['id'],'name'=>$row['name'],'email'=>$row['email']];
 }

 public function revoke(int $tokenId): void {
  $stmt=$this->db->prepare('UPDATE api_tokens SET revoked_at=UTC_TIMESTAMP() WHERE id=?');$stmt->bind_param('i',$tokenId);$stmt->execute();
 }
}