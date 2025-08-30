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

<!DOCTYPE html>
<html>
<head>
    <title>Library Book Tracker</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        h2 { color: #333; }
        form { margin-bottom: 20px; padding: 15px; background: #f8f8f8; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; }
        table th, table td { padding: 8px; text-align: left; border: 1px solid #ddd; }
        table th { background-color: #f2f2f2; }
        .form-row { margin-bottom: 10px; }
        label { display: inline-block; width: 100px; }
        input[type="text"], input[type="checkbox"] { padding: 5px; width: 250px; }
        input[type="checkbox"] { width: auto; }
        button { padding: 8px 15px; background: #4CAF50; color: white; border: none; cursor: pointer; }
        button:hover { background: #45a049; }
        .filter-form { display: inline-block; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Library Book Tracker</h1>
        <h2>Add New Book</h2>
        <form method="POST">
            <div class="form-row">
                <label>Title:</label>
                <input type="text" name="title" required>
            </div>
            <div class="form-row">
                <label>Author:</label>
                <input type="text" name="author" required>
            </div>
            <div class="form-row">
                <label>Available:</label>
                <input type="checkbox" name="availability" checked>
            </div>
            <div class="form-row">
                <label>Genres:</label>
                <input type="text" name="genres" placeholder="fiction, mystery, etc. (comma-separated)">
            </div>
            <button type="submit" name="add">Add Book</button>
        </form>
        <h2>Book List</h2>
        <form method="GET" class="filter-form">
            <label>Filter by Genre:</label>
            <input type="text" name="genre" placeholder="Enter genre">
            <button type="submit">Filter</button>
            <a href="index.php"><button type="button">Clear Filter</button></a>
        </form>
        <table>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Author</th>
                <th>Available</th>
                <th>Genres</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
            <?php
            $result = $conn->query($filter_sql);
            while ($row = $result->fetch_assoc()) {
                $book_id = $row['book_id'];
                $genres_result = $conn->query("SELECT genre FROM genres WHERE book_id = $book_id");
                $book_genres = [];
                while ($genre_row = $genres_result->fetch_assoc()) {
                    $book_genres[] = $genre_row['genre'];
                }
                $genres_str = implode(", ", $book_genres);
                
                echo "<tr>
                        <td>".$row['book_id']."</td>
                        <td>".$row['title']."</td>
                        <td>".$row['author']."</td>
                        <td>".($row['availability'] ? 'Yes' : 'No')."</td>
                        <td>".$genres_str."</td>
                        <td>".$row['created_at']."</td>
                        <td>
                            <a href='?delete=".$row['book_id']."' onclick='return confirm(\"Are you sure?\")'>Delete</a>
                            <form method='POST' style='margin-top:5px;'>
                                <input type='hidden' name='id' value='".$row['book_id']."'>
                                <input type='text' name='title' value='".$row['title']."' required style='width:120px;'>
                                <input type='text' name='author' value='".$row['author']."' required style='width:120px;'>
                                <label><input type='checkbox' name='availability' ".($row['availability'] ? 'checked' : '')."> Available</label>
                                <input type='text' name='genres' value='".$genres_str."' style='width:120px;'>
                                <button type='submit' name='update'>Update</button>
                            </form>
                        </td>
                      </tr>";
            }
            ?>
        </table>
    </div>
</body>
</html>