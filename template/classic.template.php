		<!-- Start Page -->
		<div class="stages" id="start">
			<?php if ($config['gallery']['enabled']): ?>
			<a class="gallery-button btn" href="#"><i class="fa fa-th"></i> <span data-i18n="gallery"></span></a>
			<?php endif; ?>

			<div class="startInner">
				<?php if ($config['is_event']): ?>
				<div class="names">
					<hr class="small" />
					<hr>
					<div>
						<h1><?=$config['event']['textLeft']?>
							<i class="fa <?=$config['event']['symbol']?>" aria-hidden="true"></i>
							<?=$config['event']['textRight']?>
							<br>
							<?=$config['start_screen']['title']?>
						</h1>
						<h2><?=$config['start_screen']['subtitle']?></h2>
					</div>
					<hr>
					<hr class="small" />
				</div>
				<?php else: ?>
				<div class="names">
					<hr class="small" />
					<hr>
					<div>
						<h1><?=$config['start_screen']['title']?></h1>
						<h2><?=$config['start_screen']['subtitle']?></h2>
					</div>
					<hr>
					<hr class="small" />
				</div>
				<?php endif; ?>

				<?php if ($config['button']['force_buzzer']): ?>
				<div id="useBuzzer">
						<span data-i18n="use_button"></span>
				</div>
				<?php else: ?>
					<?php if ($config['collage']['enabled']): ?>
					<a href="#" class="btn takeCollage"><i class="fa fa-th-large"></i> <span
							data-i18n="takeCollage"></span></a>
					<?php endif; ?>

					<a href="#" class="btn takePic"><i class="fa fa-camera"></i> <span data-i18n="takePhoto"></span></a>
				<?php endif; ?>
				<button hidden class="closeGallery"></button>
				<button hidden class="triggerPic"></button>
				<button hidden class="triggerCollage"></button>
			</div>

			<?php if ($config['show_fork']): ?>
			<a href="https://github.com/andi34/photobooth" class="github-fork-ribbon" data-ribbon="Fork me on GitHub">Fork me on GitHub</a>
			<?php endif; ?>

			<?php if($config['button']['show_cups']): ?>
				<a id="cups-button" class="btn cups-button" href="#" target="newwin"><span>CUPS</span></a>
			<?php endif; ?>
			<?php if($config['button']['show_fs']): ?>
				<a href="#" id="fs-button" class="btn btn--small fs-button"><i class="fa fa-arrows-alt"></i> <span data-i18n="toggleFullscreen"></span></a>
			<?php endif; ?>
		</div>