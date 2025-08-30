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