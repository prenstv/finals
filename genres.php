<?php
    // Fetch genres from dedicated table
    $genres = $conn->query("SELECT genre FROM genres ORDER BY genre ASC");
    while ($g = $genres->fetch_assoc()) {
        echo "<option value='" . htmlspecialchars($g['genre']) . "'>" . htmlspecialchars($g['genre']) . "</option>";
    }
    ?>