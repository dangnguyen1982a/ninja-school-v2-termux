<?php
session_start();

/* ================= CONFIG ================= */
$DB_HOST = '127.0.0.1';
$DB_USER = 'root';
$DB_PASS = '';

$DBS = ['acc', 'aov'];
$ADMIN_USER = 'admin';
$ADMIN_PASS = 'admin123';

/* ================= LOGIN ================= */
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ./');
    exit;
}

if (!isset($_SESSION['admin'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (($_POST['username'] ?? '') === $ADMIN_USER && ($_POST['password'] ?? '') === $ADMIN_PASS) {
            $_SESSION['admin'] = true;
            header('Location: ./');
            exit;
        }
        $error = 'Tài khoản hoặc mật khẩu không chính xác!';
    }
    ?>
    <!doctype html>
    <html lang="vi">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Đăng nhập Admin</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-[#0a0814] flex items-center justify-center min-h-screen p-4">
        <form method="post" class="w-full max-w-sm bg-[#140f26] border border-purple-500/30 p-6 rounded-2xl">
            <h1 class="text-white text-xl font-bold mb-4 text-center">NINJA SCHOOL V2</h1>
            <?php if(isset($error)): ?><p class="text-red-400 text-xs mb-4 text-center"><?=htmlspecialchars($error)?></p><?php endif; ?>
            <input type="text" name="username" placeholder="Tài khoản" class="w-full mb-3 bg-[#0a0814] text-white text-sm p-3 border border-purple-500/30 rounded-xl" required>
            <input type="password" name="password" placeholder="Mật khẩu" class="w-full mb-4 bg-[#0a0814] text-white text-sm p-3 border border-purple-500/30 rounded-xl" required>
            <button type="submit" class="w-full bg-purple-600 text-white font-bold py-3 rounded-xl">ĐĂNG NHẬP</button>
        </form>
    </body>
    </html>
    <?php
    exit;
}

/* ================= DATABASE ================= */
$dbname = $_GET['db'] ?? 'acc';
if (!in_array($dbname, $DBS, true)) $dbname = 'acc';

try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$dbname;charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch(Exception $e) {
    die("<div style='color:red;padding:20px'>Lỗi kết nối CSDL: ".htmlspecialchars($e->getMessage())."</div>");
}

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

/* ================= ACTIONS ================= */
if (isset($_GET['delete'], $_GET['table'], $_GET['id'])) {
    $table = $_GET['table'];
    if (in_array($table, ['player', 'ninja', 'item', 'itemsell', 'gift_code', 'clan'], true) && ctype_digit($_GET['id'])) {
        $pdo->prepare("DELETE FROM `$table` WHERE id=?")->execute([$_GET['id']]);
    }
    header("Location: ?db=".urlencode($dbname)."&table=".urlencode($table));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_table'])) {
    $table = $_POST['save_table'];
    $id = $_POST['id'] ?? '';
    if (in_array($table, ['player', 'ninja', 'item', 'itemsell', 'gift_code', 'clan'], true)) {
        $cols = $pdo->query("DESCRIBE `$table`")->fetchAll();
        $fields = [];
        foreach ($cols as $col) {
            $name = $col['Field'];
            if ($name !== 'id' && array_key_exists($name, $_POST)) {
                $value = $_POST[$name];

                // Các cột NOT NULL có giá trị mặc định
                if ($value === '') {
                    $notNullDefaults = [
                        'lock' => 0,
                        'ban' => 0,
                        'luong' => 0,
                        'coin' => 0,
                        'status' => 0,
                        'role' => 0,
                        'online' => 0,
                        'vip' => -1,
                        'admin_web' => 0,
            'ninja' => '[]',
                        'XacThuc' => 1
                    ];

                    $fields[$name] = array_key_exists($name, $notNullDefaults)
                        ? $notNullDefaults[$name]
                        : null;
                } else {
                    $fields[$name] = $value;
                }
            }
        }
        if ($id !== '') {
            $sets = []; $vals = [];
            foreach ($fields as $k => $v) { $sets[] = "`$k`=?"; $vals[] = $v; }
            if ($sets) { $vals[] = $id; $pdo->prepare("UPDATE `$table` SET ".implode(',', $sets)." WHERE id=?")->execute($vals); }
        } else {
            if ($fields) {
                $names = array_keys($fields);
                $marks = array_fill(0, count($names), '?');
                $pdo->prepare("INSERT INTO `$table` (`".implode('`,`', $names)."`) VALUES (".implode(',', $marks).")")->execute(array_values($fields));
            }
        }
        header("Location: ?db=".urlencode($dbname)."&table=".urlencode($table));
        exit;
    }
}

/* ================= DATA ================= */
$table = $_GET['table'] ?? '';
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20; $offset = ($page - 1) * $limit;
$rows = []; $total = 0; $columns = [];

if ($table && in_array($table, $tables, true)) {
    $columns = $pdo->query("DESCRIBE `$table`")->fetchAll();
    $where = ''; $params = [];
    if ($search !== '') {
        $sc = [];
        foreach ($columns as $c) {
            if (strpos(strtolower($c['Type']), 'char') !== false || strpos(strtolower($c['Type']), 'text') !== false) {
                $sc[] = "`".$c['Field']."` LIKE ?"; $params[] = "%$search%";
            }
        }
        if ($sc) $where = "WHERE ".implode(" OR ", $sc);
    }
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `$table` $where"); $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT * FROM `$table` $where ORDER BY id DESC LIMIT $limit OFFSET $offset"); $stmt->execute($params);
    $rows = $stmt->fetchAll();
}

$edit = null;
$isAdd = false;
if ($table && isset($_GET['add']) && in_array($table, $tables, true)) {
    $isAdd = true;
    $edit = [];
    $columns = $pdo->query("DESCRIBE `$table`")->fetchAll();
    foreach($columns as $c) {
        $edit[$c['Field']] = '';
    }
} elseif ($table && isset($_GET['edit']) && ctype_digit($_GET['edit']) && in_array($table, $tables, true)) {
    $columns = $pdo->query("DESCRIBE `$table`")->fetchAll();
    $stmt = $pdo->prepare("SELECT * FROM `$table` WHERE id=?"); $stmt->execute([$_GET['edit']]);
    $edit = $stmt->fetch();
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Ninja School Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        ::-webkit-scrollbar { width: 4px; height: 4px; }
        ::-webkit-scrollbar-track { background: #0a0814; }
        ::-webkit-scrollbar-thumb { background: #6b21a8; border-radius: 4px; }
    </style>
</head>
<body class="bg-[#0a0814] text-slate-200 h-screen overflow-hidden flex">

    <div id="overlay" class="fixed inset-0 bg-black/70 z-40 hidden md:hidden" onclick="toggleMenu()"></div>

    <aside id="sidebar" class="fixed inset-y-0 left-0 w-64 bg-[#140f26] border-r border-purple-500/20 z-50 transform -translate-x-full md:relative md:translate-x-0 transition-transform duration-300 flex flex-col">
        <div class="p-4 flex items-center justify-between border-b border-purple-500/20">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-user-ninja text-purple-500 text-xl"></i>
                <div>
                    <h1 class="font-bold text-white text-sm">NINJA SCHOOL</h1>
                    <p class="text-[10px] text-cyan-400">Database Panel</p>
                </div>
            </div>
            <button class="md:hidden text-slate-400 p-2" onclick="toggleMenu()"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>
        
        <div class="flex-1 overflow-y-auto p-3 space-y-1">
            <a href="?db=<?=h($dbname)?>" class="block px-3 py-2 rounded-lg text-xs font-semibold <?=!$table?'bg-purple-600 text-white':'text-slate-400 hover:bg-[#1a1438]'?>">
                <i class="fa-solid fa-chart-pie w-5"></i> Tổng Quan
            </a>
            <div class="pt-3 pb-1 text-[10px] uppercase font-bold text-slate-500">Quản lý Bảng</div>
            <?php 
            $menu = ['player'=>'Tài khoản', 'ninja'=>'Nhân vật', 'item'=>'Hành trang', 'itemsell'=>'Shop', 'gift_code'=>'Giftcode', 'clan'=>'Gia tộc'];
            foreach($menu as $t => $name): if(in_array($t, $tables, true)): 
            ?>
                <a href="?db=<?=h($dbname)?>&table=<?=h($t)?>" class="block px-3 py-2 rounded-lg text-xs font-semibold <?=$table===$t?'bg-purple-600/30 text-cyan-300 border border-purple-500/30':'text-slate-400 hover:bg-[#1a1438]'?>">
                    <i class="fa-solid fa-table w-5 text-purple-400"></i> <?=h($name)?>
                </a>
            <?php endif; endforeach; ?>
            
            <div class="pt-3 pb-1 text-[10px] uppercase font-bold text-slate-500">Tất cả Bảng (Raw)</div>
            <?php foreach($tables as $t): ?>
                <a href="?db=<?=h($dbname)?>&table=<?=h($t)?>" class="block px-3 py-1.5 rounded-lg text-xs <?=$table===$t?'text-cyan-300 font-bold':'text-slate-500 hover:text-slate-300'?>">
                    <i class="fa-solid fa-database w-5 text-[10px]"></i> <?=h($t)?>
                </a>
            <?php endforeach; ?>
        </div>
    </aside>

    <div class="flex-1 flex flex-col min-w-0 bg-[#0a0814]">
        
        <header class="bg-[#140f26] border-b border-purple-500/20 px-3 py-2 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-3">
                <button class="md:hidden bg-purple-600/20 border border-purple-500/30 p-1.5 rounded-lg text-purple-300" onclick="toggleMenu()">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="flex bg-[#0a0814] rounded-lg border border-purple-500/20 overflow-hidden text-xs">
                    <?php foreach($DBS as $d): ?>
                        <a href="?db=<?=h($d)?>" class="px-3 py-1.5 <?=$dbname===$d?'bg-purple-600 text-white font-bold':'text-slate-400'?>"><?=h($d)?></a>
                    <?php endforeach; ?>
                </div>
            </div>
            <a href="?logout=1" class="p-2 text-red-400 bg-red-500/10 rounded-lg text-xs"><i class="fa-solid fa-power-off"></i></a>
        </header>

        <main class="flex-1 overflow-y-auto p-3">
            <?php if(!$table): ?>
                <h2 class="text-white text-lg font-bold mb-4">Dashboard: <?=h($dbname)?></h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                    <?php foreach($menu as $t => $name): if(in_array($t, $tables, true)): ?>
                        <div class="bg-[#140f26] border border-purple-500/20 p-4 rounded-xl text-center">
                            <h3 class="text-[11px] text-slate-400 font-bold mb-1 uppercase"><?=h($name)?></h3>
                            <div class="text-2xl font-black text-white"><?=number_format((int)$pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn())?></div>
                        </div>
                    <?php endif; endforeach; ?>
                </div>
            <?php else: ?>
                
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
                    <div>
                        <h2 class="text-white font-bold text-sm sm:text-base">Bảng: <?=h($table)?></h2>
                        <p class="text-[10px] text-cyan-400"><?=number_format($total)?> bản ghi</p>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <?php if(in_array($table, ['player','ninja','item','itemsell','gift_code','clan'])): ?>
                            <a href="?db=<?=h($dbname)?>&table=<?=h($table)?>&add=1" class="bg-emerald-600 hover:bg-emerald-500 text-white px-3 py-2 rounded-lg text-xs font-bold shrink-0 flex items-center gap-1">
                                <i class="fa-solid fa-plus"></i> Thêm mới
                            </a>
                        <?php endif; ?>

                        <form method="get" class="flex items-center gap-2 flex-1 sm:flex-none sm:w-64">
                            <input type="hidden" name="db" value="<?=h($dbname)?>">
                            <input type="hidden" name="table" value="<?=h($table)?>">
                            <input type="text" name="search" value="<?=h($search)?>" placeholder="Tìm kiếm..." class="flex-1 bg-[#140f26] text-white text-xs px-3 py-2 border border-purple-500/30 rounded-lg outline-none focus:border-cyan-400">
                            <button type="submit" class="bg-purple-600 px-3 py-2 rounded-lg text-xs font-bold text-white shrink-0">Tìm</button>
                        </form>
                    </div>
                </div>

                <div class="bg-[#140f26] border border-purple-500/20 rounded-xl overflow-x-auto w-full">
                    <table class="w-full text-left text-xs min-w-[800px]">
                        <thead class="bg-[#0a0814] text-slate-400 text-[10px] uppercase border-b border-purple-500/20">
                            <tr>
                                <?php foreach($columns as $c): ?>
                                    <th class="px-3 py-2 whitespace-nowrap"><?=h($c['Field'])?></th>
                                <?php endforeach; ?>
                                <th class="px-3 py-2 text-right sticky right-0 bg-[#0a0814] shadow-[-5px_0_10px_rgba(0,0,0,0.5)]">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-purple-500/10">
                            <?php foreach($rows as $row): ?>
                                <tr class="hover:bg-purple-600/10 text-slate-300">
                                    <?php foreach($columns as $c): $name=$c['Field']; $val=$row[$name]??''; ?>
                                        <td class="px-3 py-2 whitespace-nowrap max-w-[150px] truncate" title="<?=h($val)?>">
                                            <?= $name==='id' ? '<b class="text-cyan-400">#'.h($val).'</b>' : h($val) ?>
                                        </td>
                                    <?php endforeach; ?>
                                    <td class="px-3 py-2 text-right sticky right-0 bg-[#140f26] shadow-[-5px_0_10px_rgba(0,0,0,0.2)]">
                                        <a href="?db=<?=h($dbname)?>&table=<?=h($table)?>&edit=<?=h($row['id'])?>" class="text-purple-400 bg-purple-400/10 px-2 py-1 rounded text-[10px] mr-1">Sửa</a>
                                        <?php if(in_array($table, ['player','ninja','item','itemsell','gift_code','clan'])): ?>
                                            <a href="?db=<?=h($dbname)?>&table=<?=h($table)?>&delete=1&id=<?=h($row['id'])?>" onclick="return confirm('Xóa ID #<?=h($row['id'])?>?')" class="text-red-400 bg-red-400/10 px-2 py-1 rounded text-[10px]">Xóa</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php $pages = max(1, ceil($total / $limit)); if($pages > 1): ?>
                    <div class="flex gap-1 overflow-x-auto mt-3 pb-2">
                        <?php for($i = 1; $i <= $pages; $i++): if($i > 1 && $i < $pages && abs($i - $page) > 2) continue; ?>
                            <a href="?db=<?=h($dbname)?>&table=<?=h($table)?>&search=<?=urlencode($search)?>&page=<?=$i?>" class="px-3 py-1.5 rounded-lg border text-xs font-bold <?=$i==$page?'bg-purple-600 text-white border-purple-500':'bg-[#140f26] border-purple-500/20 text-slate-400'?>"><?=$i?></a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>

            <?php endif; ?>
        </main>
    </div>

    <?php if ($edit !== null): ?>
    <div class="fixed inset-0 bg-black/80 z-[60] flex items-center justify-center p-3">
        <form method="post" class="bg-[#140f26] border border-purple-500/40 rounded-xl w-full max-w-lg p-4 max-h-[90vh] flex flex-col">
            <div class="flex justify-between items-center mb-4 shrink-0">
                <h3 class="text-cyan-400 font-bold text-sm">
                    <?= $isAdd ? 'Thêm bản ghi mới vào ' . h($table) : 'Sửa ID: #' . h($edit['id']) ?>
                </h3>
                <a href="?db=<?=h($dbname)?>&table=<?=h($table)?>" class="text-slate-400 hover:text-white px-2">✕</a>
            </div>
            
            <input type="hidden" name="save_table" value="<?=h($table)?>">
            <?php if(!$isAdd): ?>
                <input type="hidden" name="id" value="<?=h($edit['id'])?>">
            <?php endif; ?>
            
            <div class="flex-1 overflow-y-auto space-y-3 pr-2">
                <?php foreach($columns as $c): 
                    $name=$c['Field']; 
                    if($name==='id') continue; 
                ?>
                    <div>
                        <label class="block text-[10px] text-slate-400 uppercase font-bold mb-1"><?=h($name)?></label>
                        <input type="text" name="<?=h($name)?>" value="<?=h($edit[$name]??'')?>" class="w-full bg-[#0a0814] text-white text-xs border border-purple-500/30 p-2 rounded-lg outline-none focus:border-cyan-400">
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="pt-4 border-t border-purple-500/20 flex justify-end gap-2 shrink-0">
                <a href="?db=<?=h($dbname)?>&table=<?=h($table)?>" class="px-4 py-2 bg-[#0a0814] border border-purple-500/20 text-slate-300 rounded-lg text-xs font-bold">Hủy</a>
                <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg text-xs font-bold">
                    <?= $isAdd ? 'Thêm mới' : 'Lưu' ?>
                </button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <script>
        function toggleMenu() {
            document.getElementById('sidebar').classList.toggle('-translate-x-full');
            document.getElementById('overlay').classList.toggle('hidden');
        }
    </script>
</body>
</html>
