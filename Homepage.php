<?php
include __DIR__ . '/db_connect.php';
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: SignIn.php");
    exit();
}

$stmt = $const->prepare("SELECT t1.book_id, t1.book_title, t1.book_author, t1.book_pubdate, t1.book_description, t1.book_price, t1.book_img_path, t2.category_id FROM books AS t1 JOIN book_category AS t2 ON t1.book_id = t2.book_id");
$stmt->execute();
$result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$books = ['fiction' => [], 'educational' => [], 'classic' => []];

if ($result) {
    foreach ($result as $resBook) {
        switch ((int) ($resBook['category_id'])) {
            case 1: $books['fiction'][] = $resBook; break;
            case 2: $books['educational'][] = $resBook; break;
            case 3: $books['classic'][] = $resBook; break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>eLibrary - Homepage</title>
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

        <nav class="main-nav">
            <ul>
                <li><a href="Homepage.php" class="active">Homepage</a></li>
                <li><a href="Homepage.php#categories">Categories</a></li>
                <li><a href="saved_books.php">Saved Books</a></li>
                <li><a href="create_book.php">Create Book</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="Settings.php">Settings</a></li>
                <?php if (isset($_SESSION['logged_in'])): ?>
                    <li><a href="signout.php">Logout</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main>
        <h2 class="section-heading">Discover Your Next Read</h2>
        <section id="categories" class="book-categories">
            <?php foreach (['fiction' => 'Fiction Thrillers 🔪', 'educational' => 'Educational Resources 📚', 'classic' => 'Classic Literature 🏛️'] as $key => $title): ?>
            <div class="content-section">
                <h3 class="category-title"><?php echo $title; ?></h3>
                <div class="book-cards-container">
                    <?php foreach ($books[$key] as $book): ?>
                        <article class="book-card">
                            <img src="<?php echo htmlspecialchars($book['book_img_path']); ?>" alt="Book" class="book-image">
                            <h3 class="book-name"><?php echo htmlspecialchars($book['book_title']); ?></h3>
                            <p class="book-description"><?php echo htmlspecialchars($book['book_description']); ?></p>
                            <a href="book.php?book_id=<?php echo $book['book_id']; ?>" class="details-button">View Details</a>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </section>
    </main>

    <footer>
        <p>&copy; 2025 eLibrary. All rights reserved.</p>
    </footer>

    <div id="notif"></div>
</body>
</html>