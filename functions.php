<?php

// Definisikan konstanta nama file data
define('DATA_FILE', 'todos.json');

/**
 * Mengambil daftar semua tugas dari file JSON.
 *
 * @return array Daftar tugas dalam bentuk array asosiatif
 * @throws Exception Jika gagal membaca atau mengubah format JSON
 */
function getTodos() {
    // Cek apakah file todos.json ada
    if (!file_exists(DATA_FILE)) {
        return []; // Jika tidak ada, kembalikan array kosong
    }

    // Baca isi file JSON
    $json = file_get_contents(DATA_FILE);
    if ($json === false) {
        throw new Exception('Gagal membaca data dari file.'); // Jika gagal membaca, lempar exception
    }

    // Ubah JSON menjadi array asosiatif
    $todos = json_decode($json, true);
    if ($todos === null) {
        throw new Exception('Gagal mengubah data dari format JSON.'); // Jika gagal mengubah, lempar exception
    }

    return $todos; // Kembalikan daftar tugas
}

/**
 * Menyimpan daftar tugas ke file JSON.
 *
 * @param array $todos Daftar tugas dalam bentuk array asosiatif
 * @throws Exception Jika gagal mengubah data menjadi format JSON atau menyimpan ke file
 */
function saveTodos(array $todos) {
    // Ubah array tugas menjadi JSON dengan format yang mudah dibaca
    $json = json_encode($todos, JSON_PRETTY_PRINT);
    if ($json === false) {
        throw new Exception('Gagal mengubah data menjadi format JSON.'); // Jika gagal mengubah, lempar exception
    }

    // Simpan JSON ke file
    if (file_put_contents(DATA_FILE, $json) === false) {
        throw new Exception('Gagal menyimpan data ke file.'); // Jika gagal menyimpan, lempar exception
    }
}

/**
 * Menambahkan tugas baru ke daftar.
 *
 * @param string $task Nama tugas yang akan ditambahkan
 * @param string|null $due_date Tanggal jatuh tempo tugas (opsional)
 * @throws Exception Jika gagal menyimpan tugas ke file
 */
function addTodo(string $task, ?string $due_date = null) {
    $task = htmlspecialchars(trim($task)); // Membersihkan input nama tugas
    $todos = getTodos(); // Mendapatkan daftar tugas yang sudah ada
    $id = !empty($todos) ? max(array_column($todos, 'id')) + 1 : 1; // Menghitung ID baru
    $todos[] = [ // Menambahkan tugas baru ke daftar
        'id' => $id,
        'task' => $task,
        'is_done' => false,
        'due_date' => $due_date
    ];
    saveTodos($todos); // Menyimpan perubahan ke file
}

/**
 * Menghapus tugas dari daftar berdasarkan ID.
 *
 * @param int $id ID tugas yang akan dihapus
 * @throws Exception Jika gagal menyimpan perubahan ke file
 */
function deleteTodo(int $id) {
    $todos = getTodos(); // Mendapatkan daftar tugas yang sudah ada
    $todos = array_filter($todos, fn($todo) => $todo['id'] !== $id); // Menghapus tugas berdasarkan ID
    saveTodos(array_values($todos)); // Menyimpan perubahan ke file
}

/**
 * Mengubah status selesai atau belum selesai dari suatu tugas.
 *
 * @param int $id ID tugas yang statusnya akan diubah
 * @throws Exception Jika gagal menyimpan perubahan ke file
 */
function toggleTodoStatus(int $id) {
    $todos = getTodos(); // Mendapatkan daftar tugas yang sudah ada
    foreach ($todos as &$todo) {
        if ($todo['id'] === $id) {
            $todo['is_done'] = !$todo['is_done']; // Toggle status is_done
            break;
        }
    }
    saveTodos($todos); // Menyimpan perubahan ke file
}

/**
 * Memperbarui nama atau tanggal jatuh tempo tugas berdasarkan ID.
 *
 * @param int $id ID tugas yang akan diperbarui
 * @param string $task Nama tugas baru
 * @param string|null $due_date Tanggal jatuh tempo baru (opsional)
 * @throws Exception Jika gagal menyimpan perubahan ke file
 */
function updateTodo(int $id, string $task, ?string $due_date = null) {
    $task = htmlspecialchars(trim($task)); // Membersihkan input nama tugas
    $todos = getTodos(); // Mendapatkan daftar tugas yang sudah ada
    foreach ($todos as &$todo) {
        if ($todo['id'] === $id) {
            $todo['task'] = $task; // Mengupdate nama tugas
            $todo['due_date'] = $due_date; // Mengupdate tanggal jatuh tempo
            break;
        }
    }
    saveTodos($todos); // Menyimpan perubahan ke file
}