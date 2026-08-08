<?php
session_start();
$host=getenv('DB_HOST')?:'127.0.0.1'; $db=getenv('DB_NAME')?:'attendance';
$user=getenv('DB_USER')?:'root'; $pass=getenv('DB_PASS')?:'';
$au=getenv('ADMIN_USER')?:'admin'; $ap=getenv('ADMIN_PASS')?:'change-me';
try{$pdo=new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4",$user,$pass,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);}
catch(Throwable $e){http_response_code(500);exit("Database connection failed. Check hosting settings.");}
function e($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function ph($p){return hash('sha256',preg_replace('/\D/','',$p));}
if($_SERVER['REQUEST_METHOD']==='POST'){
 $a=$_POST['action']??'';
 if($a==='mark'){
  $p=preg_replace('/\D/','',$_POST['phone']??'');
  if(strlen($p)!==10){$_SESSION['m']=['error','કૃપા કરીને 10 અંકનો મોબાઇલ નંબર નાખો.'];}
  else{$s=$pdo->prepare("SELECT * FROM members WHERE phone_hash=? LIMIT 1");$s->execute([ph($p)]);$m=$s->fetch();
   if(!$m)$_SESSION['m']=['error','આ મોબાઇલ નંબર સભ્ય યાદીમાં મળ્યો નથી.'];
   else{$d=date('Y-m-d');$s=$pdo->prepare("SELECT id FROM attendance WHERE member_id=? AND attendance_date=?");$s->execute([$m['id'],$d]);
    if($s->fetch())$_SESSION['m']=['info',"આજની હાજરી પહેલેથી નોંધાઈ ગઈ છે — ".$m['name']."."];
    else{$s=$pdo->prepare("INSERT INTO attendance(member_id,attendance_date,marked_at) VALUES(?,?,NOW())");$s->execute([$m['id'],$d]);$_SESSION['m']=['success',"હાજરી સફળતાપૂર્વક નોંધાઈ — ".$m['name']."."];}
   }
  } header('Location: ./');exit;
 }
 if($a==='login'){if(hash_equals($au,$_POST['username']??'')&&hash_equals($ap,$_POST['password']??'')){$_SESSION['admin']=1;header('Location:?admin=1');exit;}$_SESSION['m']=['error','Admin username અથવા password ખોટો છે.'];header('Location:?login=1');exit;}
 if($a==='logout'){session_destroy();header('Location:./');exit;}
}
if(isset($_GET['export'])&&isset($_SESSION['admin'])){
 $d=$_GET['date']??date('Y-m-d');$s=$pdo->prepare("SELECT m.name,m.mandal,m.role,a.attendance_date,a.marked_at FROM attendance a JOIN members m ON m.id=a.member_id WHERE a.attendance_date=? ORDER BY a.marked_at");$s->execute([$d]);
 header('Content-Type:text/csv;charset=utf-8');header("Content-Disposition:attachment; filename=attendance-$d.csv");echo "\xEF\xBB\xBF";$o=fopen('php://output','w');fputcsv($o,['Name','Mandal','Role','Date','Time']);while($r=$s->fetch())fputcsv($o,[$r['name'],$r['mandal'],$r['role'],$r['attendance_date'],$r['marked_at']]);fclose($o);exit;
}
$m=$_SESSION['m']??null;unset($_SESSION['m']);
?>
<!doctype html><html lang="gu"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>ગાંધીનગર જિલ્લા હાજરી</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#f1f5f9;color:#0f172a;font-family:system-ui,-apple-system,"Noto Sans Gujarati",sans-serif}
header{background:#0f172a;color:#fff;padding:22px;text-align:center}main{max-width:820px;margin:auto;padding:18px}.card{background:#fff;border-radius:18px;padding:20px;margin-bottom:16px;box-shadow:0 8px 30px #0001}
input{width:100%;padding:14px;border:2px solid #cbd5e1;border-radius:12px;font-size:19px;margin:7px 0 14px}button,.btn{border:0;border-radius:11px;padding:13px 17px;background:#2563eb;color:#fff;font-weight:800;text-decoration:none;cursor:pointer}.secondary{background:#e2e8f0;color:#0f172a}
.success,.error,.info{padding:13px;border-radius:11px;margin-bottom:14px}.success{background:#dcfce7}.error{background:#fee2e2}.info{background:#dbeafe}
.stats{display:grid;grid-template-columns:1fr 1fr;gap:12px}.stat{text-align:center;background:#f8fafc;padding:14px;border-radius:12px}.stat b{display:block;font-size:28px}table{width:100%;border-collapse:collapse}th,td{padding:10px;border-bottom:1px solid #e2e8f0;text-align:left}th{background:#f8fafc}
</style></head><body><header><h1>ગાંધીનગર જિલ્લા હાજરી</h1><div>મોબાઇલ નંબર દ્વારા હાજરી</div></header><main>
<?php if($m): ?><div class="<?=e($m[0])?>"><?=e($m[1])?></div><?php endif; ?>
<?php if(isset($_GET['login'])): ?><div class="card"><h2>Admin Login</h2><form method="post"><input type="hidden" name="action" value="login"><label>Username</label><input name="username" required><label>Password</label><input type="password" name="password" required><button>Login</button></form></div>
<?php elseif(isset($_GET['admin'])&&isset($_SESSION['admin'])):
$d=$_GET['date']??date('Y-m-d');$total=(int)$pdo->query("SELECT COUNT(*) FROM members")->fetchColumn();$s=$pdo->prepare("SELECT COUNT(*) FROM attendance WHERE attendance_date=?");$s->execute([$d]);$present=(int)$s->fetchColumn();$s=$pdo->prepare("SELECT m.name,m.mandal,m.role,a.marked_at FROM attendance a JOIN members m ON m.id=a.member_id WHERE a.attendance_date=? ORDER BY a.marked_at");$s->execute([$d]);$rows=$s->fetchAll();?>
<div class="card"><h2>Admin Dashboard</h2><div class="stats"><div class="stat">કુલ સભ્યો<b><?=$total?></b></div><div class="stat">હાજર<b><?=$present?></b></div></div>
<form method="get" style="margin-top:15px"><input type="hidden" name="admin" value="1"><input type="date" name="date" value="<?=e($d)?>"><button>Report</button></form>
<p><a class="btn" href="?export=1&date=<?=e($d)?>">CSV Export</a> <form method="post" style="display:inline"><input type="hidden" name="action" value="logout"><button class="secondary">Logout</button></form></p></div>
<div class="card"><h2><?=e($d)?> Attendance</h2><div style="overflow:auto"><table><tr><th>નામ</th><th>મંડળ</th><th>હોદ્દો</th><th>સમય</th></tr><?php foreach($rows as $r): ?><tr><td><?=e($r['name'])?></td><td><?=e($r['mandal'])?></td><td><?=e($r['role'])?></td><td><?=e($r['marked_at'])?></td></tr><?php endforeach; ?></table></div></div>
<?php else: ?><div class="card"><h2>હાજરી નોંધાવો</h2><form method="post"><input type="hidden" name="action" value="mark"><label>તમારો મોબાઇલ નંબર</label><input name="phone" inputmode="numeric" maxlength="10" placeholder="10 અંકનો મોબાઇલ નંબર" required><button>હાજરી નોંધાવો</button></form></div><div class="card"><a class="btn secondary" href="?login=1">Admin Login</a></div><?php endif; ?>
</main></body></html>