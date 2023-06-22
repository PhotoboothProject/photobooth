<?php
$fileRoot = '../';

require_once $fileRoot . 'lib/config.php';
require_once $fileRoot . 'lib/photobooth.php';

if (!is_file('.skip_welcome')) {
    touch('.skip_welcome');
}

$photobooth = new Photobooth();
$URL = $photobooth->getUrl();

$PHOTOBOOTH_HOME = realpath($fileRoot);
$PHOTOBOOTH_HOME = rtrim($PHOTOBOOTH_HOME, DIRECTORY_SEPARATOR);
$pageTitle = 'Welcome to '. $config['ui']['branding'];
$remoteBuzzer = false;
$photoswipe = false;
$chromaKeying = false;

include($fileRoot . 'admin/components/head.admin.php');
include($fileRoot . 'admin/helper/index.php');
include($fileRoot . 'admin/inputs/index.php');
?>

<div class="w-full h-full grid place-items-center fixed bg-brand-1 overflow-x-hidden overflow-y-auto">
		<div class="w-full flex items-center justify-center flex-col px-6 py-12"> 

			<div class="w-full max-w-4xl h-144 rounded-lg bg-white flex flex-col shadow-xl">

				<div class="p-4 md:p-8">
					<div class="w-full flex items-center pb-3 mb-4 border-b border-solid border-gray-200 justify-center">
						<h2 class="text-brand-1 text-xl text-center font-bold">
							Welcome to your own Photobooth
						</h2>
					</div>

					<div>
						<p>OpenSource Photobooth web interface for Linux and Windows.</p>
						<p></p>
						<p>Photobooth was initally developped by Andre Rinas especially to run on a Raspberry Pi.<br>
						In 2019 Andreas Blaesius picked up the work and continued to work on the source.</p>
						<p class="mt-2">
							With the help of the community Photobooth grew to a powerfull Photobooth software with a lot of features and possibilities.
							By a lot of features, we mean a lot (!!!) and you might have some questions - now or later. You can find a lot of useful information
							<a class="underline hover:text-brand-1" href="https://photoboothproject.github.io" target="_blank" rel="noopener noreferrer">https://photoboothproject.github.io</a> 
							or at the 
							<a class="underline hover:text-brand-1" href="https://t.me/PhotoboothGroup" target="_blank" rel="noopener noreferrer">Telegram group</a>.
						</p>
						</p>
						<h3 class="text-brand-1 font-bold mb-2 mt-4">Here are some basic information for you:</h3>
						<p class="mb-2">
							<b class="flex mb-1">Location of your Photobooth installation:</b>
							<code class="break-all"><?=$PHOTOBOOTH_HOME?></code><br>
							<i class="text-xs text-gray-500">All files and folders inside this path belong to the Webserver user "www-data".</i>
						</p>
						<p class="mb-3">
							<b class="flex mb-1">Images can be found at:</b> 
							<code class="break-all"><?=$config['foldersAbs']['images']?></code>
						</p>
						<p class="mb-3">
							<b class="flex mb-1">Databases are placed at:</b> 
							<code class="break-all"><?=$config['foldersAbs']['data']?></code>
						</p>
						<p>
							<b class="flex mb-1">Add your own files (e.g. background images, frames, overrides.css) inside:</b>
							<code class="break-all"><?=$PHOTOBOOTH_HOME . DIRECTORY_SEPARATOR . "private"?></code><br>
							<i class="text-xs text-gray-500">All files and folders inside this path will be ignored on git and won't cause trouble while updating Photobooth.</i>
						</p>
						<p class="mt-4">
							You can change the settings and look of Photobooth using the Admin panel at 
							<code class="break-all"><a class="hover:text-brand-1" href="../admin" target="_blank" rel="noopener noreferrer"><?=$URL;?>/admin</a></code>.
						</p>
						<p class="mt-1">
							A standalone gallery can be found at 
							<code class="break-all"><a class="hover:text-brand-1" href="../gallery" target="_blank" rel="noopener noreferrer"><?=$URL;?>/gallery</a></code>.
						</p>
						<p class="mt-1">
							A standalone slideshow can be found at 
							<code class="break-all"><a class="hover:text-brand-1" href="../slideshow" target="_blank" rel="noopener noreferrer"><?=$URL;?>/slideshow</a></code>.
						</p>
						<p class="mt-1">
							An integrated FAQ to answer a lot of questions can be found at 
							<code class="break-all"><a class="hover:text-brand-1" href="../faq" target="_blank" rel="noopener noreferrer"><?=$URL;?>/faq</a></code>.
						</p>
						<p class="mt-4">
							You are missing some translation or your language isn't supported yet? 
							Don't worry! You can request new language support at <a class="underline hover:text-brand-1" href="https://github.com/PhotoboothProject/photobooth/issues" target="_blank" rel="noopener noreferrer">GitHub</a>,
							you can translate Photobooth at <a class="underline hover:text-brand-1" href="https://crowdin.com/project/photobooth" target="_blank" rel="noopener noreferrer">Crowdin</a>.
						</p>
					</div>

				</div>

				<div class="p-4 md:px-8 md:py-4 bg-yellow-300 text-sm">
					<b class="flex mb-2 text-red-500">Security advice</b>
					Photobooth is not hardened against any kind of <i>targeted</i> attacks.<br>
					It uses user defined commands for tasks like taking photos and is allowed to replace its own files for easy updating.<br>
					Because of this it's not advised to operate Photobooth in an untrusted network and<br>
					<b class="text-red-500">you should absolutely not make Photobooth accessible through the internet without heavy modifications!</b></p>
					<p></p>
				</div>


				<div class="p-4 md:p-8">
					<p class="text-center">Thanks for the reading! Enjoy your Photobooth!</p>

					<div class="w-full max-w-md p-5 mx-auto mt-2">
						<?=getMenuBtn($fileRoot, 'Start Photobooth', '')?>
					</div>
				</div>

			</div>

		</div>
	</div>


    <?php include($fileRoot . 'template/components/main.footer.php'); ?>
</body>
</html>
