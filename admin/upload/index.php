<?php
session_start();

$fileRoot = '../../';

require_once $fileRoot . 'lib/config.php';

// Login / Authentication check
if (
    !$config['login']['enabled'] ||
    (!$config['protect']['localhost_admin'] && isset($_SERVER['SERVER_ADDR']) && $_SERVER['REMOTE_ADDR'] === $_SERVER['SERVER_ADDR']) ||
    (isset($_SESSION['auth']) && $_SESSION['auth'] === true) ||
    !$config['protect']['admin']
) {
    // nothing for now
} else {
    header('location: ' . $fileRoot . 'login');
    exit();
}

$pageTitle = 'Image uploader';
include '../components/head.admin.php';
include '../helper/index.php';

$error = false;
$success = false;
$labelClass = 'w-full flex flex-col mb-1';
$inputClass = 'w-full h-10 border border-solid border-gray-300 focus:border-brand-1 rounded-md px-3 mt-auto';
$btnClass = 'w-full h-12 rounded-full bg-brand-1 text-white flex items-center justify-center relative ml-auto border-2 border-solid border-brand-1 hover:bg-white hover:text-brand-1 transition font-bold px-4';

if (isset($_POST['submit'])) {
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
            $error = true;
        }
    }
    if (!$error) {
        $success = true;
    }
}
?>

<body>
    <div class="w-full h-screen grid place-items-center absolute bg-brand-2 px-6 py-12 overflow-x-hidden overflow-y-auto">
        <div class="w-full flex items-center justify-center flex-col">
            <div class="w-full max-w-xl h-144 rounded-lg p-8 bg-white flex flex-col shadow-xl">
                <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" enctype="multipart/form-data">
                    <div class="w-full flex flex-col items-center justify-center text-2xl font-bold text-brand-1 mb-2">
                        <?= $config['ui']['branding'] ?> - Image uploader
                    </div>

                    <div class="relative">
                        <label class="<?= $labelClass ?>" for="folder_name"><span data-i18n="upload_folder"></span></label>
                        <input class="<?= $inputClass ?>" type="text" name="folder_name" id="folder_name" required><br><br>
                        <label class="<?= $labelClass ?>" for="images"><span data-i18n="upload_selection"></label>
                        <input class="<?= $labelClass ?>" type="file" name="images[]" id="images" multiple accept="image/*" required><br><br>
                    </div>

                    <div class="mt-6">
                        <input class="<?= $btnClass ?>" type="submit" name="submit" value="Upload">
                    </div>
                    <?php if ($error !== false) {
                        echo '<span class="w-full flex mt-6 text-red-500" data-i18n="upload_error"></span>';
                    } ?>

                    <?php if ($success) {
                        echo '<span class="w-full flex mt-6 text-green" data-i18n="upload_success"></span>';
                    } ?>
                </form>
            </div>
        <div class="w-full max-w-xl my-12 border-b border-solid border-white border-opacity-20">
        </div>
		<div class="w-full max-w-xl rounded-lg py-8 bg-white flex flex-col shadow-xl relative">
			<div class="grid grid-cols-1 md:grid-cols-2 gap-4 px-4 ">
				<?php
                    echo getMenuBtn($fileRoot . 'admin', 'admin_panel', $config['icons']['admin']);

                    if (isset($_SESSION['auth']) && $_SESSION['auth'] === true) {
                        echo getMenuBtn($fileRoot . 'login/logout.php', 'logout', $config['icons']['logout']);
                    }
                ?>
			</div>
        </div>
    </div>

<?php include '../components/footer.admin.php'; ?>

