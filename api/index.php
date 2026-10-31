<?php
// 1. เชื่อมต่อฐานข้อมูล MySQL ด้วย Environment Variables บน Vercel
$host = getenv('DB_HOST');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');
$dbname = getenv('DB_NAME');
$port = getenv('DB_PORT') ?: 3306;

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("<div style='color:red; padding:20px;'>Database Connection Failed: " . $e->getMessage() . "</div>");
}

// 2. จัดการข้อมูลเมื่อมีการส่ง Form (Create / Update / Delete)
$action = $_POST['action'] ?? '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create') {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $position = trim($_POST['position']);
        if (!empty($name) && !empty($email)) {
            $stmt = $pdo->prepare("INSERT INTO employees (name, email, position) VALUES (?, ?, ?)");
            $stmt->execute([$name, $email, $position]);
            header("Location: /?success=created");
            exit;
        }
    } elseif ($action === 'update') {
        $id = $_POST['id'];
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $position = trim($_POST['position']);
        if (!empty($id) && !empty($name)) {
            $stmt = $pdo->prepare("UPDATE employees SET name = ?, email = ?, position = ? WHERE id = ?");
            $stmt->execute([$name, $email, $position, $id]);
            header("Location: /?success=updated");
            exit;
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'];
        if (!empty($id)) {
            $stmt = $pdo->prepare("DELETE FROM employees WHERE id = ?");
            $stmt->execute([$id]);
            header("Location: /?success=deleted");
            exit;
        }
    }
}

// โหมดแก้ไข (ถ้ามีการกดปุ่ม Edit)
$editData = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editData = $stmt->fetch();
}

// ดึงข้อมูลทั้งหมดมาแสดง
$stmt = $pdo->query("SELECT * FROM employees ORDER BY id DESC");
$employees = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>PHP CRUD on Vercel</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="bg-slate-100 min-h-screen p-6">
    <div class="max-w-4xl mx-auto bg-white rounded-xl shadow-md overflow-hidden p-6">
        <h1 class="text-2xl font-bold text-slate-800 mb-6">📌 PHP CRUD on Vercel & MySQL</h1>

        <!-- แจ้งเตือนสถานะ -->
        <?php if (isset($_GET['success'])): ?>
            <div class="mb-4 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-lg">
                ดำเนินการสำเร็จเรียบร้อย! 🎉
            </div>
        <?php endif; ?>

        <!-- ฟอร์มสำหรับเพิ่ม / แก้ไขข้อมูล -->
        <form action="/" method="POST" class="bg-slate-50 p-4 rounded-lg border border-slate-200 mb-8 grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <input type="hidden" name="action" value="<?= $editData ? 'update' : 'create' ?>">
            <?php if ($editData): ?>
                <input type="hidden" name="id" value="<?= $editData['id'] ?>">
            <?php endif; ?>

            <div>
                <label class="block text-sm font-medium text-slate-600 mb-1">ชื่อ-นามสกุล</label>
                <input type="text" name="name" required value="<?= $editData['name'] ?? '' ?>" class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-600 mb-1">อีเมล</label>
                <input type="email" name="email" required value="<?= $editData['email'] ?? '' ?>" class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-600 mb-1">ตำแหน่ง</label>
                <input type="text" name="position" value="<?= $editData['position'] ?? '' ?>" class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-medium py-2 px-4 rounded-md transition">
                    <?= $editData ? 'บันทึกการแก้ไข' : 'เพิ่มข้อมูล' ?>
                </button>
                <?php if ($editData): ?>
                    <a href="/" class="bg-gray-300 hover:bg-gray-400 text-gray-700 py-2 px-4 rounded-md text-center">ยกเลิก</a>
                <?php endif; ?>
            </div>
        </form>

        <!-- ตารางแสดงข้อมูล (Read) -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-200 text-slate-700 uppercase text-xs">
                        <th class="p-3">ID</th>
                        <th class="p-3">ชื่อ</th>
                        <th class="p-3">อีเมล</th>
                        <th class="p-3">ตำแหน่ง</th>
                        <th class="p-3 text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (count($employees) > 0): ?>
                        <?php foreach ($employees as $row): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="p-3 text-slate-500"><?= $row['id'] ?></td>
                                <td class="p-3 font-medium text-slate-800"><?= htmlspecialchars($row['name']) ?></td>
                                <td class="p-3 text-slate-600"><?= htmlspecialchars($row['email']) ?></td>
                                <td class="p-3 text-slate-600"><?= htmlspecialchars($row['position']) ?></td>
                                <td class="p-3 text-center flex justify-center gap-2">
                                    <a href="/?edit=<?= $row['id'] ?>" class="text-blue-600 hover:underline text-sm font-medium">แก้ไข</a>
                                    <form action="/" method="POST" onsubmit="return confirm('ยืนยันการลบข้อมูลนี้?');" class="inline">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                        <button type="submit" class="text-red-600 hover:underline text-sm font-medium">ลบ</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="p-4 text-center text-slate-400">ยังไม่มีข้อมูลในระบบ</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
