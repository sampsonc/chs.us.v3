Links that I've found interesting at one point or another by date -

<?php
$directory = '.'; // Replace with the actual directory path
// Extracts files and directories that match a pattern
$items = glob($directory . '/*');
foreach ($items as $item) {
    if (is_file($item)) {
        echo "File: {$item}\n";
    }
    if (is_dir($item)) {
        echo "Directory: {$item}\n";
    }
}
// Iterate over directories
$directories = glob($directory . '/*', GLOB_ONLYDIR);
foreach ($directories as $dir) {
    echo "Directory: $dir\n";
}
