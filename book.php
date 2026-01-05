<?php
include __DIR__ . '/db_connect.php';
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: SignIn.php");
    exit();
}

$book_id = isset($_GET['book_id']) ? (int) $_GET['book_id'] : 0;

if ($book_id <= 0) {
    header("Location: Homepage.php");
    exit();
}

// --- 🗑️ DELETE LOGIC ---
if (isset($_POST['delete_book'])) {
    // 1. Delete category link first
    $delCat = $const->prepare("DELETE FROM book_category WHERE book_id = ?");
    $delCat->bind_param("i", $book_id);
    $delCat->execute();

    // 2. Delete from books table
    $delBook = $const->prepare("DELETE FROM books WHERE book_id = ?");
    $delBook->bind_param("i", $book_id);
    if ($delBook->execute()) {
        header("Location: Homepage.php?deleted=1");
        exit();
    }
}

// --- 📝 UPDATE LOGIC ---
if (isset($_POST['update_book'])) {
    $title = $_POST['book_title'];
    $author = $_POST['book_author'];
    $desc = $_POST['book_description'];
    $price = $_POST['book_price'];
    $cat_id = $_POST['category_id'];

    $update = $const->prepare("UPDATE books SET book_title=?, book_author=?, book_description=?, book_price=? WHERE book_id=?");
    $update->bind_param("sssdi", $title, $author, $desc, $price, $book_id);
    $update->execute();

    $updateCat = $const->prepare("UPDATE book_category SET category_id=? WHERE book_id=?");
    $updateCat->bind_param("ii", $cat_id, $book_id);
    $updateCat->execute();
    
    header("Location: book.php?book_id=$book_id&updated=1");
    exit();
}

$stmt = $const->prepare("
    SELECT 
        b.book_id,
        b.book_title,
        b.book_author,
        b.book_description,
        b.book_price,
        b.book_img_path,
        c.category_id
    FROM books b
    JOIN book_category c ON b.book_id = c.book_id
    WHERE b.book_id = ?
");
$stmt->bind_param("i", $book_id);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();

if (!$book) {
    die("Book not found.");
}

/* 🏷 Category mapping */
$categories = [
    1 => "Fiction",
    2 => "Educational",
    3 => "Classic"
];

$categoryName = $categories[$book['category_id']] ?? "Unknown";

// 🔍 Check if book is already saved
$is_saved = false;
$check = $const->prepare("SELECT 1 FROM save_books WHERE user_id = ? AND book_id = ? LIMIT 1");
$check->bind_param("ii", $_SESSION['user_id'], $book_id);
$check->execute();
$check->store_result();
if ($check->num_rows > 0) {
    $is_saved = true;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($book['book_title']); ?> - Book Details</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-controls { margin-top: 20px; padding: 15px 0; border-top: 2px solid #eee; display: flex; gap: 10px; }
        .edit-btn { background: #2196F3; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; }
        .delete-btn { background: #f44336; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; }
        #editFormContainer { display: none; background: #fdfdfd; padding: 20px; border-radius: 8px; border: 1px solid #ddd; margin-top: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
    </style>
</head>

<body>
<header class="main-header">
        <h1>eLibrary</h1>

        <input type="checkbox" id="menu-toggle" class="menu-toggle">
        <label for="menu-toggle" class="burger-icon">
            <span></span>
            <span></span>
            <span></span>
        </label>
        <nav class="main-nav" aria-label="Main Navigation">
            <ul>
                <li><a href="Homepage.php" class="active">Homepage</a></li>
                <li><a href="Homepage.php#categories">Categories</a></li>
                <li><a href="saved_books.php">Saved Books</a></li>
                <li><a href="create_book.php">Create Book</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="Settings.php">Settings</a></li>
                <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
                    <li><a href="signout.php">Logout</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main>

        <section class="product-detail-section">

            <div class="product-image-box">
                <img src="<?php echo htmlspecialchars($book['book_img_path']); ?>"
                    alt="<?php echo htmlspecialchars($book['book_title']); ?>"
                    class="item-image">
            </div>

            <div class="product-info-box">
                <h2 class="item-name"><?php echo htmlspecialchars($book['book_title']); ?></h2>
                <p class="item-category">Category: <?php echo htmlspecialchars($categoryName); ?></p>

                <h3>Description</h3>
                <p class="item-description"><?php echo htmlspecialchars($book['book_description']); ?></p>

                <p class="item-price">Price: ₱<?php echo number_format($book['book_price'], 2); ?></p>

                <div class="interaction-area">
                    <button class="save-button <?php echo $is_saved ? 'unsave' : 'save'; ?>"
                        data-book="<?php echo $book['book_id']; ?>"
                        data-saved="<?php echo $is_saved ? '1' : '0'; ?>">
                        <?php echo $is_saved ? 'Unsave Book' : 'Save Book'; ?>
                    </button>
                </div>

                <div class="admin-controls">
                    <button class="edit-btn" onclick="toggleEditForm()">Edit Details</button>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this book forever?');">
                        <button type="submit" name="delete_book" class="delete-btn">Delete Book</button>
                    </form>
                </div>

                <div id="editFormContainer">
                    <h3 style="margin-top:0;">Edit Book Information</h3>
                    <form method="POST">
                        <div class="form-group">
                            <label>Book Title</label>
                            <input type="text" name="book_title" value="<?php echo htmlspecialchars($book['book_title']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Author</label>
                            <input type="text" name="book_author" value="<?php echo htmlspecialchars($book['book_author']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Category</label>
                            <select name="category_id">
                                <option value="1" <?php if($book['category_id'] == 1) echo 'selected'; ?>>Fiction</option>
                                <option value="2" <?php if($book['category_id'] == 2) echo 'selected'; ?>>Educational</option>
                                <option value="3" <?php if($book['category_id'] == 3) echo 'selected'; ?>>Classic</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Price (₱)</label>
                            <input type="number" step="0.01" name="book_price" value="<?php echo $book['book_price']; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="book_description" rows="4"><?php echo htmlspecialchars($book['book_description']); ?></textarea>
                        </div>
                        <button type="submit" name="update_book" class="edit-btn">Save Changes</button>
                        <button type="button" onclick="toggleEditForm()" style="padding:10px; border:none; cursor:pointer;">Cancel</button>
                    </form>
                </div>
            </div>

        </section>

    </main>

    <footer>
        <p>&copy; 2025 eLibrary</p>
    </footer>

    <div id="notif" style="display:none; position:fixed; top:20px; right:20px; background:#4CAF50; color:white; padding:12px 18px; border-radius:10px; z-index:1000; font-size:14px;"></div>

    <script>
        function toggleEditForm() {
            const container = document.getElementById('editFormContainer');
            container.style.display = (container.style.display === 'block') ? 'none' : 'block';
        }

        function showNotif(message) {
            const notif = document.getElementById("notif");
            notif.textContent = message;
            notif.style.display = "block";
            setTimeout(() => { notif.style.display = "none"; }, 2000);
        }

        const params = new URLSearchParams(window.location.search);
        if (params.get("updated") === "1") showNotif("Book updated successfully!");

        // Existing Save/Unsave Logic
        const saveBtn = document.querySelector(".save-button");
        saveBtn.addEventListener("click", async () => {
            const book_id = saveBtn.dataset.book;
            const isSaved = saveBtn.dataset.saved === "1";
            const url = isSaved ? 'bookHandler/remove_book.php' : 'bookHandler/save_book.php';

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ book_id })
                });
                const data = await response.json();
                if (data.success) {
                    location.reload(); 
                } else {
                    showNotif("Action failed.");
                }
            } catch {
                showNotif("Server error.");
            }
        });
    </script>

</body>
</html>