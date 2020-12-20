		<!-- Start Page -->
		<div class="stages" id="start">
			<div class="startInner">
				<div class="divaussen">
					<div class="divinnen">
            					<div class="divinnen2">
							<?php if ($config['is_event']): ?>
							<div class="names">
								<hr class="small" />
								<hr>
								<div>
									<h1>
									<?=$config['event']['textLeft']?>
									<i class="fa <?=$config['event']['symbol']?>" aria-hidden="true"></i>
									<?=$config['event']['textRight']?>
									<br>
									<?=$config['start_screen_title']?>
									</h1>
									<h2><?=$config['start_screen_subtitle']?></h2>
								</div>

								<hr>
								<hr class="small" />

							</div>
							<?php else: ?>
							<div class="names">

								<hr class="small" />
								<hr>

								<div>
									<h1><?=$config['start_screen_title']?></h1>
									<h2><?=$config['start_screen_subtitle']?></h2>
								</div>

								<hr>
								<hr class="small" />

							</div>
							<?php endif; ?>

							<?php if ($config['force_buzzer']): ?>
							<div id="useBuzzer">
								<span data-i18n="use_button"></span>
							</div>
							<?php if($config['cups_button']): ?>
							<a id="cups-button" class="round-btn cups-button" href="#" target="newwin"><i class="fa fa-cog" aria-hidden="true"></i> <span>CUPS</span></a>
							<?php endif; ?>
							<?php if ($config['show_gallery']): ?>
							<a href="#" class="round-btn gallery-button"><i class="fa fa-th"></i> <span data-i18n="gallery"></span></a>
							<?php endif; ?>
							<?php if($config['toggle_fs_button']): ?>
							<a href="#" id="fs-button" class="round-btn fs-button"><i class="fa fa-arrows-alt"></i> <span data-i18n="toggleFullscreen"></span></a>
							<?php endif; ?>
							<?php else: ?>
							<?php if($config['cups_button']): ?>
							<a id="cups-button" class="round-btn cups-button" href="#" target="newwin"><i class="fa fa-cog" aria-hidden="true"></i> <span>CUPS</span></a>
							<?php endif; ?>
							<?php if ($config['use_collage']): ?>
							<a href="#" class="round-btn takeCollage"><i class="fa fa-th-large"></i> <span data-i18n="takeCollage"></span></a>
							<?php endif; ?>

							<a href="#" class="round-btn takePic"><i class="fa fa-camera"></i> <span data-i18n="takePhoto"></span></a>
							<?php endif; ?>

							<?php if ($config['show_gallery']): ?>
							<a href="#" class="round-btn gallery-button"><i class="fa fa-th"></i> <span data-i18n="gallery"></span></a>
							<?php endif; ?>
							<?php if($config['toggle_fs_button']): ?>
							<a href="#" id="fs-button" class="round-btn fs-button"><i class="fa fa-arrows-alt"></i> <span data-i18n="toggleFullscreen"></span></a>
							<?php endif; ?>
						</div>
						<button hidden class="closeGallery"></button>
						<button hidden class="triggerPic"></button>
						<button hidden class="triggerCollage"></button>
					</div>
				</div>
			</div>

			<?php if ($config['show_fork']): ?>
			<a href="https://github.com/andi34/photobooth" class="github-fork-ribbon" data-ribbon="Fork me on GitHub">Fork me on GitHub</a>
			<?php endif; ?>
		</div>