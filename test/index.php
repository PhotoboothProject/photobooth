<?php
$fileRoot = '../';

require_once($fileRoot . 'lib/config.php');

$pageTitle = 'Tests';
include($fileRoot . 'admin/components/head.admin.php');
include($fileRoot . 'admin/helper/index.php');

$labelClass = 'w-full flex flex-col mb-1';
$inputClass = 'w-full h-10 border border-solid border-gray-300 focus:border-brand-1 rounded-md px-3 mt-auto';
$btnClass = 'w-full h-12 rounded-full bg-brand-1 text-white flex items-center justify-center relative ml-auto border-2 border-solid border-brand-1 hover:bg-white hover:text-brand-1 transition font-bold px-4';
?>

<body>
	<div class="w-full h-screen grid place-items-center absolute bg-brand-2 px-6 py-12 overflow-x-hidden overflow-y-auto">
		<div class="w-full flex items-center justify-center flex-col">
			<div class="w-full max-w-xl rounded-lg py-8 bg-white flex flex-col shadow-xl relative">
				<div class="px-4">
					<h1 class="text-2xl font-bold text-center mb-6 border-solid border-b border-gray-200 pb-4 text-brand-1">
						<span data-i18n="testMenu"></span>
					</h1>
				</div>

				<div class="grid grid-cols-1 md:grid-cols-2 gap-4 px-4 ">
				<?php 
					echo getMenuBtn('phpinfo.php', 'phpinfo', '');
					echo getMenuBtn('photo.php', 'pictureTest', '');
					echo getMenuBtn('collage.php', 'collageTest', '');
					echo getMenuBtn('preview.php', 'previewTest', '');
					echo getMenuBtn('chroma.php', 'chromaPreviewTest', '');
					echo getMenuBtn('trigger.php', 'remotebuzzerGetTrigger', '');
				?>
				</div>

			</div>
		
		</div>
	</div>

<?php
include($fileRoot . 'admin/components/footer.admin.php');
?>
