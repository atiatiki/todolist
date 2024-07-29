<?php
// Mengimpor file functions.php yang berisi fungsi-fungsi terkait manajemen todo
require 'functions.php';

// Proses form jika terdapat request POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        // Mendapatkan nilai task dan due_date dari $_POST
        $task = $_POST['task'] ?? ''; // Jika tidak ada, set default ke string kosong
        $due_date = $_POST['due_date'] ?? null; // Jika tidak ada, set default ke null

        // Memproses berdasarkan tombol yang ditekan
        if (isset($_POST['add'])) {
            addTodo($task, $due_date); // Memanggil fungsi addTodo untuk menambahkan todo baru
        } elseif (isset($_POST['delete'])) {
            deleteTodo((int)($_POST['id'] ?? 0)); // Memanggil fungsi deleteTodo untuk menghapus todo
        } elseif (isset($_POST['toggle'])) {
            toggleTodoStatus((int)($_POST['id'] ?? 0)); // Memanggil fungsi toggleTodoStatus untuk mengubah status todo
        } elseif (isset($_POST['update'])) {
            updateTodo((int)($_POST['id'] ?? 0), $task, $due_date); // Memanggil fungsi updateTodo untuk memperbarui todo
        }
    } catch (Exception $e) {
        error_log($e->getMessage()); // Log pesan error ke file error log server
        echo '<p class="error">Terjadi kesalahan. Silakan coba lagi.</p>'; // Menampilkan pesan error kepada pengguna
    }
}

// Ambil semua data todo dari file todos.json
$todos = getTodos();

// Filter dan pencarian berdasarkan query string search dan filter
$search = $_GET['search'] ?? ''; // Mendapatkan nilai dari parameter search atau default string kosong
$filter = $_GET['filter'] ?? ''; // Mendapatkan nilai dari parameter filter atau default string kosong

// Filter todos berdasarkan search
if ($search) {
    $todos = array_filter($todos, function($todo) use ($search) {
        return stripos($todo['task'], $search) !== false; // Mencocokkan task dengan search, mengabaikan case sensitive
    });
}

// Filter todos berdasarkan status (done atau pending)
if ($filter === 'done') {
    $todos = array_filter($todos, fn($todo) => $todo['is_done']); // Mengambil todos yang sudah selesai
} elseif ($filter === 'pending') {
    $todos = array_filter($todos, fn($todo) => !$todo['is_done']); // Mengambil todos yang belum selesai
}

$editTask = null; // Inisialisasi variabel untuk task yang diedit
$editDueDate = null; // Inisialisasi variabel untuk due date yang diedit
$editId = null; // Inisialisasi variabel untuk id dari todo yang diedit

// Cek jika ada permintaan untuk mengedit todo
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit']; // Mendapatkan id todo yang akan diedit
    foreach ($todos as $todo) {
        if ($todo['id'] === $editId) {
            // Jika ditemukan todo yang sesuai id, ambil task dan due_date untuk diedit
            $editTask = htmlspecialchars($todo['task']); // Menghindari XSS dengan meng-encode task
            $editDueDate = is_string($todo['due_date']) ? htmlspecialchars($todo['due_date']) : ''; // Menghindari XSS dengan meng-encode due_date jika tipe string
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Tugas</title>
    <link rel="stylesheet" href="style.css"> <!-- Mengimpor stylesheet CSS -->
</head>
<body>
    <div class="container">
        <h1>Daftar Tugas</h1>

        <!-- Form untuk pencarian -->
        <form method="GET" class="search-form">
            <input type="text" name="search" placeholder="Cari tugas..." value="<?= htmlspecialchars($search) ?>"> <!-- Input untuk search dengan nilai yang di-encode -->
            <select name="filter">
                <option value="">Semua</option>
                <option value="done" <?= $filter === 'done' ? 'selected' : '' ?>>Selesai</option> <!-- Option untuk filter done -->
                <option value="pending" <?= $filter === 'pending' ? 'selected' : '' ?>>Belum Selesai</option> <!-- Option untuk filter pending -->
            </select>
            <button type="submit" class="btn btn-search">Cari</button> <!-- Tombol untuk submit form pencarian -->
        </form>

        <!-- Form untuk menambah atau mengedit todo -->
        <form method="POST" class="task-form">
            <?php if ($editTask !== null): ?>
                <!-- Jika sedang mengedit, tampilkan input hidden dengan id todo yang diedit -->
                <input type="hidden" name="id" value="<?= htmlspecialchars($editId) ?>">
                <input type="text" name="task" value="<?= htmlspecialchars($editTask) ?>" required> <!-- Input untuk task yang diedit -->
                <input type="date" name="due_date" value="<?= htmlspecialchars($editDueDate) ?>" min="1900-01-01"> <!-- Input untuk due_date yang diedit -->
                <div class="form-actions">
                    <a href="index.php" class="btn btn-cancel">Batal</a> <!-- Tombol untuk membatalkan edit -->
                    <button type="submit" name="update" class="btn btn-update">Perbarui Tugas</button> <!-- Tombol untuk memperbarui todo -->
                </div>
            <?php else: ?>
                <!-- Jika tidak sedang mengedit, tampilkan input untuk menambah todo baru -->
                <input type="text" name="task" placeholder="Tugas baru" required>
                <input type="date" name="due_date">
                <button type="submit" name="add" class="btn btn-add">Tambah Tugas</button> <!-- Tombol untuk menambah todo baru -->
            <?php endif; ?>
        </form>

        <!-- Daftar todos yang ada -->
        <ul>
            <?php foreach ($todos as $todo): ?>
                <li class="<?= $todo['is_done'] ? 'done' : '' ?>">
                    <input type="checkbox" disabled <?= $todo['is_done'] ? 'checked' : '' ?> /> <!-- Checkbox untuk menandai status done atau tidak -->
                    <?= htmlspecialchars($todo['task']) ?> <!-- Menampilkan task dengan menghindari XSS -->
                    <span class="due-date"><?= htmlspecialchars($todo['due_date']) ?></span> <!-- Menampilkan due_date dengan menghindari XSS -->
                    <div class="actions">
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($todo['id']) ?>">
                            <button type="submit" name="toggle" class="btn btn-toggle"><?= $todo['is_done'] ? 'Batal' : 'Selesaikan' ?></button> <!-- Tombol untuk menyelesaikan atau membatalkan todo -->
                        </form>
                        <a href="index.php?edit=<?= htmlspecialchars($todo['id']) ?>" class="btn btn-edit">Edit</a> <!-- Tombol untuk mengedit todo -->
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($todo['id']) ?>">
                            <button type="submit" name="delete" class="btn btn-delete">Hapus</button> <!-- Tombol untuk menghapus todo -->
                        </form>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</body>
</html>
