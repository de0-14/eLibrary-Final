<?php
include __DIR__ . '/db_connect.php';
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: SignIn.php");
    exit();
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title       = $_POST['book_title'];
    $author      = $_POST['book_author'];
    $pubdate     = $_POST['book_pubdate'];
    $description = $_POST['book_description'];
    $price       = $_POST['book_price'];
    $category_id = $_POST['category_id'];

    $target_dir = "uploads/";
    if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); } 
    
    $file_name = time() . "_" . basename($_FILES["book_image"]["name"]);
    $target_file = $target_dir . $file_name;
    
    if (move_uploaded_file($_FILES["book_image"]["tmp_name"], $target_file)) {
        
        $sqlBook = "INSERT INTO books (book_title, book_author, book_pubdate, book_description, book_price, book_img_path) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $const->prepare($sqlBook);
        $stmt->bind_param("ssssds", $title, $author, $pubdate, $description, $price, $target_file);

        if ($stmt->execute()) {
            $new_book_id = $stmt->insert_id;

            $sqlCat = "INSERT INTO book_category (book_id, category_id) VALUES (?, ?)";
            $stmtCat = $const->prepare($sqlCat);
            $stmtCat->bind_param("ii", $new_book_id, $category_id);
            $stmtCat->execute();

            $message = "Book added successfully!";
        } else {
            $message = "Error adding book: " . $const->error;
        }
    } else {
        $message = "Error uploading image.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>eLibrary - Create Book</title>
    <link rel="stylesheet" href="style.css"> 
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

    <main style="max-width: 600px; margin: 40px auto; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <h2 class="section-heading">Add a New Book</h2>

        <?php if($message): ?>
            <div style="padding: 10px; background: #e7f3fe; color: #31708f; margin-bottom: 20px; border-radius: 5px;">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form action="create_book.php" method="POST" enctype="multipart/form-data">
            <div style="margin-bottom: 10px;">
                <label>Book Title</label><br>
                <input type="text" name="book_title" required style="width: 100%; padding: 8px;">
            </div>

            <div style="margin-bottom: 10px;">
                <label>Author</label><br>
                <input type="text" name="book_author" required style="width: 100%; padding: 8px;">
            </div>

            <div style="margin-bottom: 10px;">
                <label>Category</label><br>
                <select name="category_id" required style="width: 100%; padding: 8px;">
                    <option value="1">Fiction Thrillers</option>
                    <option value="2">Educational Resources</option>
                    <option value="3">Classic Literature</option>
                </select>
            </div>

            <div style="margin-bottom: 10px;">
                <label>Publication Date</label><br>
                <input type="date" name="book_pubdate" required style="width: 100%; padding: 8px;">
            </div>

            <div style="margin-bottom: 10px;">
                <label>Price ($)</label><br>
                <input type="number" step="0.01" name="book_price" required style="width: 100%; padding: 8px;">
            </div>

            <div style="margin-bottom: 10px;">
                <label>Description</label><br>
                <textarea name="book_description" rows="3" style="width: 100%; padding: 8px;"></textarea>
            </div>

            <div style="margin-bottom: 15px;">
                <label>Book Cover Image</label><br>
                <input type="file" name="book_image" accept="image/*" required>
            </div>

            <button type="submit" style="background: #4CAF50; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">Save Book</button>
        </form>
    </main>

</body>
</html>