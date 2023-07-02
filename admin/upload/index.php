<?php
$fileRoot = '../../';

require_once $fileRoot . 'lib/config.php';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Photobooth - Image uploader</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f2f2f2;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            color: #333;
        }

        form {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 10px;
            color: #333;
        }

        input[type="text"],
        input[type="file"] {
            display: block;
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        input[type="submit"] {
            background-color: #1B3FAA;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        input[type="submit"]:hover {
            background-color: #45a049;
        }

        p.success {
            color: #1B3FAA;
            font-weight: bold;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Images to upload</h2>
        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" enctype="multipart/form-data">
            <label for="folder_name">Folder Name:</label>
            <input type="text" name="folder_name" id="folder_name" required><br><br>
            <label for="images">Select images:</label>
            <input type="file" name="images[]" id="images" multiple accept="image/*" required><br><br>
            <input type="submit" name="submit" value="Upload">
        </form>

        <?php if (isset($_POST['submit'])) {
            $folderName = $_POST['folder_name'];
            $parentDirectory = $config['foldersAbs']['private'] . DIRECTORY_SEPARATOR . 'images';
            // Check if the parent directory exists
            if (!is_dir($parentDirectory)) {
                mkdir($parentDirectory, 0777, true);
            } else {
                chmod($parentDirectory, 0777); // Set parent directory permissions to 777
            }

            // Check if the folder already exists
            $folderPath = $parentDirectory . '/' . $folderName;
            if (!is_dir($folderPath)) {
                // Create the folder if it doesn't exist
                mkdir($folderPath, 0777, true);
            }

            // Process uploaded images
            $uploadedImages = $_FILES['images'];

            // Array of allowed image file types
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];

            for ($i = 0; $i < count($uploadedImages['name']); $i++) {
                $imageName = $uploadedImages['name'][$i];
                $imageTmpName = $uploadedImages['tmp_name'][$i];
                $imageType = $uploadedImages['type'][$i];
                $imagePath = $folderPath . '/' . $imageName;

                // Check if the file type is allowed
                if (in_array($imageType, $allowedTypes)) {
                    // Move the uploaded image to the custom folder
                    move_uploaded_file($imageTmpName, $imagePath);
                    chmod($imagePath, 0777); // Set parent directory permissions to 777
                } else {
                    echo '<p class="error">Error : Please select only image files.</p>';
                }
            }

            echo '<p class="success">Images uploaded successfully!</p>';
        } ?>
    </div>
</body>
</html>
