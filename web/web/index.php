cat > index.php <<'PHP'
<?php
session_start();

/* ================= CONFIG ================= */
$DB_HOST = '127.0.0.1';
$DB_USER = 'root';
$DB_PASS = '';

$DBS = ['acc', 'aov'];

/* Đổi mật khẩu panel tại đây */
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
        if (
            ($_POST['username'] ?? '') === $ADMIN_USER &&
            ($_POST['password'] ?? '') === $ADMIN_PASS
        ) {
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
        <title>Ninja School Admin — Đăng nhập</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
    </head>
    <body class="bg-[#0a0814] text-slate-200 min-h-screen flex items-center justify-center p-4">
        <div class="fixed top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-purple-600/20 rounded-full blur-[120px] pointer-events-none"></div>
        <div class="w-full max-w-md bg-[#140f26]/90 backdrop-blur-xl border border-purple-500/30 rounded-2xl p-6 sm:p-8 shadow-2xl shadow-purple-950/50 relative z-10">
            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center w-14 h-14 bg-gradient-to-tr from-purple-600 to-cyan-500 rounded-2xl mb-3 shadow-lg shadow-purple-500/30">
                    <i class="fa-solid fa-user-ninja text-2xl text-white"></i>
                </div>
                <h1 class="text-xl font-extrabold tracking-tight text-white">NINJA SCHOOL</h1>
                <p class="text-[11px] font-semibold text-cyan-400 tracking-wider uppercase mt-0.5">Management Portal v2.0</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="mb-4 p-3 rounded-xl bg-red-500/10 border border-red-500/30 text-red-400 text-xs flex items-center gap-2">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="post" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Tài khoản Admin</label>
                    <div class="relative">
                        <i class="fa-solid fa-user absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 text-xs"></i>
                        <input type="text" name="username" required autofocus placeholder="Nhập tài khoản" 
                            class="w-full bg-[#0a0814] border border-purple-500/30 focus:border-cyan-400 text-white text-xs rounded-xl pl-9 pr-3 py-2.5 outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Mật khẩu</label>
                    <div class="relative">
                        <i class="fa-solid fa-lock absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 text-xs"></i>
                        <input type="password" name="password" required placeholder="••••••••" 
                            class="w-full bg-[#0a0814] border border-purple-500/30 focus:border-cyan-400 text-white text-xs rounded-xl pl-9 pr-3 py-2.5 outline-none">
                    </div>
                </div>

                <button type="submit" 
                    class="w-full mt-2 py-3 px-4 bg-gradient-to-r from-purple-600 via-indigo-600 to-cyan-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-purple-600/30 hover:opacity-95 transition-all flex items-center justify-center gap-2">
                    <span>ĐĂNG NHẬP HỆ THỐNG</span>
                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

/* ================= DATABASE ================= */
$dbname = $_GET['db'] ?? 'acc';
if (!in_array($dbname, $DBS, true)) $dbname = 'acc';

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$dbname;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch(Exception $e) {
    die("<div style='background:#0a0814;color:#ef4444;padding:20px;font-family:sans-serif;'>
        <h3>⚠️ Lỗi kết nối CSDL MySQL</h3>
        <p>Thất bại khi kết nối tới database <b>".htmlspecialchars($dbname)."</b>: ".htmlspecialchars($e->getMessage())."</p>
        </div>");
}

/* ================= HELPERS ================= */
function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function countTable($pdo, $table) {
    try {
        return (int)$pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
    } catch(Exception $e) {
        return 0;
    }
}

function tables($pdo) {
    return $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
}

$tables = tables($pdo);

/* ================= DELETE ================= */
if (isset($_GET['delete'], $_GET['table'], $_GET['id'])) {
    $table = $_GET['table'];
    $allowed = ['player', 'ninja', 'item', 'itemsell', 'gift_code', 'clan'];

    if (in_array($table, $allowed, true) && ctype_digit($_GET['id'])) {
        $stmt = $pdo->prepare("DELETE FROM `$table` WHERE id=?");
        $stmt->execute([$_GET['id']]);
    }

    header("Location: ?db=".urlencode($dbname)."&table=".urlencode($table));
    exit;
}

/* ================= SAVE ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_table'])) {
    $table = $_POST['save_table'];
    $id = $_POST['id'] ?? '';
    $allowed = ['player', 'ninja', 'item', 'itemsell', 'gift_code', 'clan'];

    if (in_array($table, $allowed, true)) {
        $columns = $pdo->query("DESCRIBE `$table`")->fetchAll();
        $fields = [];

        foreach ($columns as $col) {
            $name = $col['Field'];
            if ($name === 'id') continue;
            if (array_key_exists($name, $_POST)) {
                $fields[$name] = $_POST[$name];
            }
        }

        if ($id !== '') {
            $sets = [];
            $values = [];
            foreach ($fields as $key => $value) {
                $sets[] = "`$key`=?";
                $values[] = $value;
            }
            if ($sets) {
                $values[] = $id;
                $sql = "UPDATE `$table` SET ".implode(',', $sets)." WHERE id=?";
                $pdo->prepare($sql)->execute($values);
            }
        } else {
            if ($fields) {
                $names = array_keys($fields);
                $marks = array_fill(0, count($names), '?');
                $sql = "INSERT INTO `$table` (`".implode('`,`', $names)."`) VALUES (".implode(',', $marks).")";
                $pdo->prepare($sql)->execute(array_values($fields));
            }
        }

        header("Location: ?db=".urlencode($dbname)."&table=".urlencode($table));
        exit;
    }
}

/* ================= SEARCH & PAGINATION ================= */
$table = $_GET['table'] ?? '';
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$rows = [];
$total = 0;
$columns = [];

if ($table && in_array($table, $tables, true)) {
    $columns = $pdo->query("DESCRIBE `$table`")->fetchAll();
    $where = '';
    $params = [];

    if ($search !== '') {
        $searchCols = [];
        foreach ($columns as $c) {
            $type = strtolower($c['Type']);
            if (strpos($type, 'char') !== false || strpos($type, 'text') !== false) {
                $searchCols[] = "`".$c['Field']."` LIKE ?";
                $params[] = "%$search%";
            }
        }
        if ($searchCols) {
            $where = "WHERE ".implode(" OR ", $searchCols);
        }
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `$table` $where");
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT * FROM `$table` $where ORDER BY id DESC LIMIT $limit OFFSET $offset");
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
}

/* ================= EDIT MODAL DATA ================= */
$edit = null;
if ($table && isset($_GET['edit']) && ctype_digit($_GET['edit'])) {
    if (in_array($table, $tables, true)) {
        $stmt = $pdo->prepare("SELECT * FROM `$table` WHERE id=?");
        $stmt->execute([$_GET['edit']]);
        $edit = $stmt->fetch();
    }
}

$menu_icons = [
    'player' => 'fa-user-shield',
    'ninja' => 'fa-user-ninja',
    'item' => 'fa-box-archive',
    'itemsell' => 'fa-store',
    'gift_code' => 'fa-gift',
    'clan' => 'fa-users',
    'map' => 'fa-map-location-dot',
    'mob' => 'fa-dragon',
    'npc' => 'fa-user-tie',
    'skill' => 'fa-wand-magic-sparkles',
    'tasktemplate' => 'fa-scroll',
    'news_posts' => 'fa-newspaper',
    'xep_hang_level' => 'fa-trophy',
    'xep_hang_phan_than' => 'fa-award'
];
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Ninja School Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        ::-webkit-scrollbar { width: 4px; height: 4px; }
        ::-webkit-scrollbar-track { background: #0a0814; }
        ::-webkit-scrollbar-thumb { background: #2e234d; border-radius: 4px; }
    </style>
</head>
<body class="bg-[#0a0814] text-slate-200 min-h-screen flex flex-col antialiased">

    <!-- HEADER TOPBAR -->
    <header class="bg-[#140f26]/90 backdrop-blur-md border-b border-purple-500/20 px-4 py-3 sticky top-0 z-30 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <!-- Nút Toggle Menu trên Mobile -->
            <button onclick="toggleSidebar()" class="md:hidden w-9 h-9 rounded-xl bg-purple-600/20 border border-purple-500/30 text-purple-300 flex items-center justify-center hover:bg-purple-600 hover:text-white transition-all">
                <i class="fa-solid fa-bars text-sm"></i>
            </button>

            <div class="w-8 h-8 md:w-9 md:h-9 rounded-xl bg-gradient-to-tr from-purple-600 to-cyan-500 flex items-center justify-center shadow-md shadow-purple-500/20 shrink-0">
                <i class="fa-solid fa-user-ninja text-white text-sm md:text-base"></i>
            </div>
            <div class="hidden sm:block">
                <h1 class="font-bold text-white text-sm tracking-wide flex items-center gap-2">
                    NINJA SCHOOL V2
                </h1>
                <p class="text-[10px] text-slate-400">Hệ thống quản trị CSDL Game</p>
            </div>
        </div>

        <div class="flex items-center gap-2 sm:gap-4 text-xs">
            <div class="flex items-center bg-[#0a0814] p-1 rounded-xl border border-purple-500/20">
                <span class="px-2 text-slate-400 text-[11px] hidden sm:inline"><i class="fa-solid fa-database text-purple-400"></i> DB:</span>
                <?php foreach($DBS as $d): ?>
                    <a href="?db=<?=h($d)?>" 
                       class="px-2.5 py-1 rounded-lg text-xs transition-all <?= $dbname === $d ? 'bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-bold' : 'text-slate-400' ?>">
                        <?=h($d)?>
                    </a>
                <?php endforeach; ?>
            </div>

            <a href="?logout=1" class="p-2 text-slate-400 hover:text-red-400 bg-[#140f26] border border-purple-500/20 rounded-xl" title="Đăng xuất">
                <i class="fa-solid fa-power-off text-xs"></i>
            </a>
        </div>
    </header>

    <!-- MAIN WRAPPER -->
    <div class="flex flex-1 relative overflow-hidden">

        <!-- OVERLAY MOBILE -->
        <div id="sidebar-overlay" onclick="toggleSidebar()" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-40 hidden md:hidden"></div>

        <!-- SIDEBAR (MENU SLIDE-OVER CHO MOBILE) -->
        <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-[#140f26] border-r border-purple-500/20 p-4 shrink-0 flex flex-col justify-between transform -translate-x-full md:translate-x-0 md:static transition-transform duration-300 ease-in-out">
            <div class="space-y-5 overflow-y-auto pr-1">
                <div class="flex items-center justify-between md:hidden pb-2 border-b border-purple-500/20">
                    <span class="font-bold text-white text-xs">MENU CHỨC NĂNG</span>
                    <button onclick="toggleSidebar()" class="text-slate-400 p-1"><i class="fa-solid fa-xmark text-base"></i></button>
                </div>

                <div>
                    <p class="text-[10px] font-extrabold uppercase tracking-wider text-purple-400/80 mb-2 px-2">Quản lý chung</p>
                    <nav class="space-y-1">
                        <a href="?db=<?=h($dbname)?>" 
                           class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= !$table ? 'bg-purple-600/20 text-cyan-400 border border-purple-500/40' : 'text-slate-400 hover:bg-purple-500/10' ?>">
                            <i class="fa-solid fa-chart-pie w-4 text-center"></i>
                            <span>Dashboard Tổng Quan</span>
                        </a>

                        <?php
                        $menu = [
                            'player'=>'Player / Tài khoản',
                            'ninja'=>'Nhân vật Ninja',
                            'item'=>'Hành trang / Item',
                            'itemsell'=>'Shop Item Sell',
                            'gift_code'=>'Mã Gift Code',
                            'clan'=>'Gia tộc / Clan',
                            'map'=>'Bản đồ / Map',
                            'mob'=>'Quái vật / Mob',
                            'npc'=>'Nhân vật NPC',
                            'skill'=>'Kỹ năng / Skill',
                            'tasktemplate'=>'Nhiệm vụ / Task',
                            'news_posts'=>'Tin tức / News',
                            'xep_hang_level'=>'BXH Cấp độ',
                            'xep_hang_phan_than'=>'BXH Phân thân'
                        ];

                        foreach($menu as $t => $name):
                            if(in_array($t, $tables, true)):
                                $icon = $menu_icons[$t] ?? 'fa-table';
                                $isActive = ($table === $t);
                        ?>
                        <a href="?db=<?=h($dbname)?>&table=<?=h($t)?>" 
                           class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-medium transition-all <?= $isActive ? 'bg-gradient-to-r from-purple-600/30 to-cyan-500/20 text-cyan-300 border border-purple-500/40 font-bold' : 'text-slate-400 hover:bg-purple-500/10' ?>">
                            <i class="fa-solid <?=$icon?> w-4 text-center text-purple-400"></i>
                            <span class="truncate"><?=h($name)?></span>
                        </a>
                        <?php endif; endforeach; ?>
                    </nav>
                </div>

                <div>
                    <p class="text-[10px] font-extrabold uppercase tracking-wider text-purple-400/80 mb-2 px-2">Bảng Raw Database</p>
                    <div class="space-y-1 max-h-40 overflow-y-auto">
                        <?php foreach($tables as $t): 
                            $isActive = ($table === $t);
                        ?>
                        <a href="?db=<?=h($dbname)?>&table=<?=h($t)?>" 
                           class="flex items-center justify-between px-3 py-1.5 rounded-lg text-xs transition-all <?= $isActive ? 'bg-purple-600/20 text-cyan-300 font-bold' : 'text-slate-500 hover:text-slate-300' ?>">
                            <span class="truncate flex items-center gap-2">
                                <i class="fa-solid fa-database text-[10px] text-purple-500"></i>
                                <?=h($t)?>
                            </span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </aside>

        <!-- MAIN CONTENT AREA -->
        <main class="flex-1 p-3 sm:p-6 w-full overflow-x-hidden">

            <?php if(!$table): ?>
                <div class="space-y-4 sm:space-y-6">
                    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-purple-900/40 via-[#140f26] to-cyan-950/30 border border-purple-500/30 p-4 sm:p-6">
                        <h2 class="text-base sm:text-xl font-extrabold text-white flex items-center gap-2">
                            Tổng quan Hệ thống Ninja School
                        </h2>
                        <p class="text-xs text-slate-400 mt-1">CSDL Active: <span class="text-cyan-400 font-bold"><?=h($dbname)?></span></p>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                        <?php
                        $stats = [
                            ['player', 'Tài khoản', 'fa-user-shield', 'text-purple-400'],
                            ['ninja', 'Ninja', 'fa-user-ninja', 'text-cyan-400'],
                            ['item', 'Vật phẩm', 'fa-box-archive', 'text-amber-400'],
                            ['gift_code', 'Giftcode', 'fa-gift', 'text-emerald-400'],
                            ['clan', 'Gia tộc', 'fa-users', 'text-rose-400']
                        ];

                        foreach($stats as [$t, $name, $icon, $iconColor]):
                            if(in_array($t, $tables, true)):
                                $count = countTable($pdo, $t);
                        ?>
                        <div class="bg-[#140f26] border border-purple-500/20 rounded-xl p-3 shadow-lg">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[11px] font-semibold text-slate-400"><?=h($name)?></span>
                                <i class="fa-solid <?=$icon?> text-xs <?=$iconColor?>"></i>
                            </div>
                            <div class="text-xl font-black text-white">
                                <?=number_format($count)?>
                            </div>
                            <a href="?db=<?=h($dbname)?>&table=<?=h($t)?>" class="mt-2 block text-[10px] text-cyan-400 hover:underline">Xem bảng &rarr;</a>
                        </div>
                        <?php endif; endforeach; ?>
                    </div>
                </div>

            <?php else: ?>

                <div class="bg-[#140f26] border border-purple-500/20 rounded-2xl p-3 sm:p-5 shadow-xl space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-purple-500/20 pb-3">
                        <div>
                            <h2 class="text-base sm:text-lg font-extrabold text-white flex items-center gap-2">
                                <i class="fa-solid fa-table-list text-purple-400"></i>
                                Bảng: <span class="text-cyan-400"><?=h($table)?></span>
                            </h2>
                            <p class="text-[11px] text-slate-400">Tổng: <?=number_format($total)?> bản ghi</p>
                        </div>

                        <form method="get" class="flex items-center gap-2 w-full sm:w-auto">
                            <input type="hidden" name="db" value="<?=h($dbname)?>">
                            <input type="hidden" name="table" value="<?=h($table)?>">
                            <div class="relative flex-1 sm:w-60">
                                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-xs"></i>
                                <input type="text" name="search" value="<?=h($search)?>" placeholder="Tìm kiếm..." 
                                    class="w-full bg-[#0a0814] border border-purple-500/30 text-white text-xs rounded-xl pl-8 pr-3 py-2 outline-none">
                            </div>
                            <button type="submit" class="px-3 py-2 bg-purple-600 text-white text-xs font-bold rounded-xl shrink-0">
                                Tìm
                            </button>
                        </form>
                    </div>

                    <!-- BẢNG DỮ LIỆU RỘNG TỐI ĐA TRÊN MOBILE -->
                    <div class="overflow-x-auto rounded-xl border border-purple-500/20">
                        <table class="w-full text-left text-xs text-slate-300 border-collapse min-w-max">
                            <thead class="bg-[#0a0814] text-slate-400 uppercase font-bold text-[10px] tracking-wider border-b border-purple-500/20">
                                <tr>
                                    <?php foreach($columns as $c): ?>
                                        <th class="px-3 py-2.5 whitespace-nowrap min-w-[90px]"><?=h($c['Field'])?></th>
                                    <?php endforeach; ?>
                                    <th class="px-3 py-2.5 text-right whitespace-nowrap sticky right-0 bg-[#0a0814]">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-purple-500/10">
                                <?php if(empty($rows)): ?>
                                    <tr>
                                        <td colspan="<?=count($columns)+1?>" class="text-center py-6 text-slate-500">
                                            Không có dữ liệu.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach($rows as $row): ?>
                                        <tr class="hover:bg-purple-500/5">
                                            <?php foreach($columns as $c): 
                                                $name = $c['Field'];
                                                $val = $row[$name] ?? '';
                                            ?>
                                                <td class="px-3 py-2.5 whitespace-nowrap max-w-xs truncate" title="<?=h($val)?>">
                                                    <?php if($name === 'id'): ?>
                                                        <span class="font-bold text-cyan-400">#<?=h($val)?></span>
                                                    <?php else: ?>
                                                        <?=h($val)?>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endforeach; ?>
                                            <td class="px-3 py-2.5 text-right whitespace-nowrap space-x-1 sticky right-0 bg-[#140f26]">
                                                <a href="?db=<?=h($dbname)?>&table=<?=h($table)?>&edit=<?=h($row['id'])?>" 
                                                   class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-purple-600/30 text-purple-300 border border-purple-500/30 text-[11px] font-semibold">
                                                    <i class="fa-solid fa-pen-to-square"></i> Sửa
                                                </a>

                                                <?php if(in_array($table, ['player','ninja','item','itemsell','gift_code','clan'], true)): ?>
                                                    <a href="?db=<?=h($dbname)?>&table=<?=h($table)?>&delete=1&id=<?=h($row['id'])?>" 
                                                       onclick="return confirm('Bạn chắc chắn xóa ID #<?=h($row['id'])?>?')"
                                                       class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-red-500/20 text-red-400 border border-red-500/30 text-[11px] font-semibold">
                                                        <i class="fa-solid fa-trash-can"></i> Xóa
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php
                    $pages = max(1, ceil($total / $limit));
                    if($pages > 1):
                    ?>
                    <div class="flex items-center justify-between pt-2 text-xs">
                        <span class="text-slate-400">Trang <b class="text-white"><?=$page?></b> / <?=$pages?></span>
                        <div class="flex items-center gap-1 overflow-x-auto">
                            <?php for($i = 1; $i <= $pages; $i++): 
                                if($i > 1 && $i < $pages && abs($i - $page) > 2) continue;
                            ?>
                                <a href="?db=<?=h($dbname)?>&table=<?=h($table)?>&search=<?=urlencode($search)?>&page=<?=$i?>" 
                                   class="px-2.5 py-1 rounded-lg border text-xs font-bold <?= $i == $page ? 'bg-purple-600 text-white border-purple-500' : 'bg-[#0a0814] border-purple-500/20 text-slate-400' ?>">
                                    <?=$i?>
                                </a>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>

            <?php endif; ?>

        </main>
    </div>

    <!-- POPUP MODAL SỬA DỮ LIỆU -->
    <?php if ($edit !== null): ?>
    <div class="fixed inset-0 bg-black/80 backdrop-blur-md flex items-center justify-center p-3 z-50">
        <div class="bg-[#140f26] border border-purple-500/40 rounded-2xl w-full max-w-xl p-4 sm:p-6 shadow-2xl relative max-h-[90vh] flex flex-col">
            
            <div class="flex justify-between items-center border-b border-purple-500/20 pb-3 shrink-0">
                <div>
                    <h3 class="text-sm font-bold text-cyan-400">Chỉnh Sửa <?=h($table)?></h3>
                    <p class="text-[11px] text-slate-400">ID: <span class="text-purple-300 font-bold">#<?=h($edit['id'])?></span></p>
                </div>
                <a href="?db=<?=h($dbname)?>&table=<?=h($table)?>" class="w-7 h-7 rounded-lg bg-slate-800 text-slate-400 flex items-center justify-center">✕</a>
            </div>

            <form method="post" class="overflow-y-auto my-3 pr-1 space-y-3 flex-1">
                <input type="hidden" name="save_table" value="<?=h($table)?>">
                <input type="hidden" name="id" value="<?=h($edit['id'])?>">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <?php foreach($columns as $c):
                        $name = $c['Field'];
                        if($name === 'id') continue;
                        $value = $edit[$name] ?? '';
                        $isLong = strlen((string)$value) > 80;
                    ?>
                        <div class="<?=$isLong ? 'sm:col-span-2' : ''?>">
                            <label class="block text-[11px] font-semibold text-slate-400 mb-1 uppercase">
                                <?=h($name)?>
                            </label>

                            <?php if($isLong): ?>
                                <textarea name="<?=h($name)?>" rows="3" 
                                    class="w-full bg-[#0a0814] border border-purple-500/30 rounded-xl px-3 py-1.5 text-xs text-white outline-none font-mono"><?=h($value)?></textarea>
                            <?php else: ?>
                                <input type="text" name="<?=h($name)?>" value="<?=h($value)?>" 
                                    class="w-full bg-[#0a0814] border border-purple-500/30 rounded-xl px-3 py-1.5 text-xs text-white outline-none">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-purple-500/20 shrink-0">
                    <a href="?db=<?=h($dbname)?>&table=<?=h($table)?>" class="px-3 py-2 rounded-xl bg-slate-800 text-slate-300 text-xs">Hủy</a>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-gradient-to-r from-purple-600 to-cyan-500 text-white font-bold text-xs">
                        Lưu Thay Đổi
                    </button>
                </div>
            </form>

        </div>
    </div>
    <?php endif; ?>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        }
    </script>
</body>
</html>
PHP

cp index.php web/index.php 2>/dev/null || true
