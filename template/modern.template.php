		<!-- Start Page -->
		<div class="stages <?php echo $uiShape; ?> noborder" id="start">
			<div class="startInner <?php echo $uiShape; ?> noborder">
				<div class="divaussen">
					<div class="divinnen">
						<?php if ($config['logo']['enabled']): ?>
                        <div id="pblogo" class="logo--<?=$config['logo']['position']?> pblogo--div">
                            <img class="pblogo--img" src=<?=$config['logo']['path']?> alt="logo">
                        </div>
						<?php endif; ?>
						<div class="divinnen2">
							<?php if ($config['event']['enabled']): ?>
							<div class="names">
								<?php if ($config['ui']['decore_lines']): ?>
								<hr class="small" />
								<hr>
								<?php endif; ?>
								<div>
									<h1>
									<?=$config['event']['textLeft']?>
									<i class="fa <?=$config['event']['symbol']?>" aria-hidden="true"></i>
									<?=$config['event']['textRight']?>
									<?php if ($config['start_screen']['title_visible']): ?>
									<br>
									<?=$config['start_screen']['title']?>
									<?php endif; ?>
									</h1>
									<?php if ($config['start_screen']['subtitle_visible']): ?>
									<h2><?=$config['start_screen']['subtitle']?></h2>
									<?php endif; ?>
								</div>
								<?php if ($config['ui']['decore_lines']): ?>
								<hr>
								<hr class="small" />
								<?php endif; ?>
							</div>
							<?php else: ?>
							<div class="names">
								<?php if ($config['ui']['decore_lines']): ?>
								<hr class="small" />
								<hr>
								<?php endif; ?>
								<div>
									<?php if ($config['start_screen']['title_visible']): ?>
									<h1><?=$config['start_screen']['title']?></h1>
									<?php endif; ?>
									<?php if ($config['start_screen']['subtitle_visible']): ?>
									<h2><?=$config['start_screen']['subtitle']?></h2>
									<?php endif; ?>
								</div>
								<?php if ($config['ui']['decore_lines']): ?>
								<hr>
								<hr class="small" />
								<?php endif; ?>
							</div>
							<?php endif; ?>
							<div class="rotarygroup">
							<?php if($config['button']['show_cups']): ?>
							<a id="cups-button" class="<?php echo $btnClass; ?> cups-button rotaryfocus" href="#" target="newwin"><i class="<?php echo $config['icons']['cups']; ?>"></i> <span>CUPS</span></a>
							<?php endif; ?>

							<?php include('components/actionBtn.php'); ?>

							<?php if ($config['gallery']['enabled']): ?>
							<a href="#" class="<?php echo $btnClass; ?> gallery-button rotaryfocus"><i class="<?php echo $config['icons']['gallery']; ?>"></i> <span data-i18n="gallery"></span></a>
							<?php endif; ?>
							<?php if($config['button']['show_fs']): ?>
							<a href="#" id="fs-button" class="<?php echo $btnClass; ?> fs-button"><i class="<?php echo $config['icons']['fullscreen']; ?>"></i> <span data-i18n="toggleFullscreen"></span></a>
							<?php endif; ?>
							</div>
						</div>
					</div>
				</div>
			</div>

			<?php if ($config['ui']['show_fork']): ?>
			<a href="https://github.com/<?=$config['ui']['github']?>/photobooth" class="github-fork-ribbon" data-ribbon="Fork me on GitHub">Fork me on GitHub</a>
			<?php endif; ?>
		</div>
