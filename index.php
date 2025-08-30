<?php
include "db.php";

$check_column = $conn->query("SHOW COLUMNS FROM `books` LIKE 'availability'");
if ($check_column->num_rows == 0) {
    $conn->query("ALTER TABLE `books` ADD `availability` TINYINT(1) NOT NULL DEFAULT 1");
}
//ADD (Add Books)
if (isset($_POST['add'])) {
    $title = $_POST['title'];
    $author = $_POST['author'];
    $availability = isset($_POST['availability']) ? 1 : 0;
    $genres = isset($_POST['genres']) ? $_POST['genres'] : '';
    
    $sql = "INSERT INTO books (title, author, availability, created_at) 
            VALUES ('$title', '$author', $availability, NOW())";
    $conn->query($sql);
    $book_id = $conn->insert_id;
    
    if (!empty($genres)) {
        $genre_list = explode(",", $genres);
        foreach ($genre_list as $genre) {
            $genre = trim($genre);
            if (!empty($genre)) {
                $sql = "INSERT INTO genres (book_id, genre) VALUES ($book_id, '$genre')";
                $conn->query($sql);
            }
        }
    }
}

// UPDATE (Edit book)
if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $title = $_POST['title'];
    $author = $_POST['author'];
    $availability = isset($_POST['availability']) ? 1 : 0;
    $genres = isset($_POST['genres']) ? $_POST['genres'] : '';
    
    // Update book
    $sql = "UPDATE books SET title='$title', author='$author', availability=$availability 
            WHERE book_id=$id";
    $conn->query($sql);
    
    // Delete existing genres
    $sql = "DELETE FROM genres WHERE book_id=$id";
    $conn->query($sql);
    
    // Insert updated genres
    if (!empty($genres)) {
        $genre_list = explode(",", $genres);
        foreach ($genre_list as $genre) {
            $genre = trim($genre);
            if (!empty($genre)) {
                $sql = "INSERT INTO genres (book_id, genre) VALUES ($id, '$genre')";
                $conn->query($sql);
            }
        }
    }
}

// DELETE (Remove book)
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    // Delete genre first (beacuse of foreign key)
    $sql = "DELETE FROM genres WHERE book_id=$id";
    $conn->query($sql);
    // agter that delete the book
    $sql = "DELETE FROM books WHERE book_id=$id";
    $conn->query($sql);
}
//filter
$filter_sql = "SELECT DISTINCT b.book_id, b.title, b.author, b.availability, b.created_at 
               FROM books b LEFT JOIN genres g ON b.book_id = g.book_id";

if (isset($_GET['genre']) && !empty($_GET['genre'])) {
    $genre = $_GET['genre'];
    $filter_sql = "SELECT DISTINCT b.book_id, b.title, b.author, b.availability, b.created_at 
                  FROM books b JOIN genres g ON b.book_id = g.book_id 
                  WHERE g.genre = '$genre'";
}
?>
