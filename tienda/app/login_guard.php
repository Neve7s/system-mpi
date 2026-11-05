<?php
declare(strict_types=1);
/** V88: bloqueo compartido entre sesiones. Solo usa REMOTE_ADDR (no cabeceras falsificables). */
function loginGuardKeys(string $identifier, string $ip): array {
    $ip=substr($ip,0,64);
    $identifier=strtolower(trim($identifier));
    return [
        ['key'=>hash('sha256','v88:ip:'.$ip),'limit'=>20],
        ['key'=>hash('sha256','v88:pair:'.$ip.'|'.$identifier),'limit'=>6],
    ];
}
function loginGuardAssert(PDO $pdo, string $identifier, string $ip): void {
    $q=$pdo->prepare('SELECT attempts,window_started,locked_until FROM admin_login_limits WHERE key_hash=?');
    $now=time();
    foreach(loginGuardKeys($identifier,$ip) as $item){
        $q->execute([$item['key']]); $row=$q->fetch();
        if($row && !empty($row['locked_until']) && strtotime($row['locked_until'].' UTC')>$now)
            throw new RuntimeException('Demasiados intentos. Espera 15 minutos antes de volver a intentarlo.');
    }
}
function loginGuardFailure(PDO $pdo, string $identifier, string $ip): void {
    $pdo->beginTransaction();
    try {
        $insert=$pdo->prepare('INSERT IGNORE INTO admin_login_limits(key_hash,attempts,window_started) VALUES(?,0,UTC_TIMESTAMP())');
        $select=$pdo->prepare('SELECT attempts,window_started FROM admin_login_limits WHERE key_hash=? FOR UPDATE');
        $update=$pdo->prepare('UPDATE admin_login_limits SET attempts=?,window_started=?,locked_until=? WHERE key_hash=?');
        $now=time();
        foreach(loginGuardKeys($identifier,$ip) as $item){
            $insert->execute([$item['key']]); $select->execute([$item['key']]); $row=$select->fetch();
            $start=strtotime((string)($row['window_started']??'').' UTC');
            $fresh=($start===false || ($now-$start)>=900);
            $attempts=$fresh?1:((int)$row['attempts']+1);
            $window=$fresh?gmdate('Y-m-d H:i:s',$now):$row['window_started'];
            $lock=$attempts >= $item['limit']?gmdate('Y-m-d H:i:s',$now+900):null;
            $update->execute([$attempts,$window,$lock,$item['key']]);
        }
        $pdo->commit();
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
function loginGuardSuccess(PDO $pdo, string $identifier, string $ip): void {
    // No borramos el contador por IP: protege frente a ataques distribuidos por identidades.
    $keys=loginGuardKeys($identifier,$ip);
    $q=$pdo->prepare('DELETE FROM admin_login_limits WHERE key_hash=?');
    $q->execute([$keys[1]['key']]);
}
