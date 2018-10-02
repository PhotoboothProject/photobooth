<?php

require_once('config.inc.php');
require_once('db.php');

?>
<html>
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0 user-scalable=no">
	<title>Photobooth</title>


	<!-- Favicon + Android/iPhone Icons -->
	<link rel="apple-touch-icon" sizes="180x180" href="/resources/img/apple-touch-icon.png">
	<link rel="icon" type="image/png" sizes="32x32" href="/resources/img/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="/resources/img/favicon-16x16.png">
	<link rel="manifest" href="/resources/img/site.webmanifest">
	<link rel="mask-icon" href="/resources/img/safari-pinned-tab.svg" color="#5bbad5">
	<meta name="msapplication-TileColor" content="#da532c">
	<meta name="theme-color" content="#ffffff">

	<link rel="stylesheet" href="/resources/css/normalize.css" />
	<link rel="stylesheet" href="/resources/css/font-awesome.min.css" />
	<link rel="stylesheet" href="/resources/css/photoswipe.css">
	<link rel="stylesheet" href="/resources/css/default-skin/default-skin.css">
	<link rel="stylesheet" href="/resources/css/style.css" />
	<script type="text/javascript">
		var isdev = true;
		var gallery_newest_first = <?php echo ($config['gallery']['newest_first']) ? 'true' : 'false'; ?>;
	</script>
</head>
<body class="deselect">
	<div id="wrapper">

		<!-- Start Page -->
		<div class="stages" id="start">
			<a class="gallery btn" href="#"><i class="fa fa-th"></i> <span data-l10n="gallery"></span></a>
			<div class="blurred">
			</div>
			<div class="inner">
				<div class="names"><hr class="small" /><hr><div data-l10n="startScreen"></div><hr><hr class="small" /></div>
				<a href="#" class="btn takePic"><i class="fa fa-camera"></i> <span data-l10n="takePhoto"></span></a>
			</div>
		</div>

		<!-- Loader -->
		<div class="stages" id="loader">
			<div class="loaderInner">
			<div class="spinner">
				<i class="fa fa-cog fa-spin"></i>
			</div>

			<div id="counter"></div>
			<div class="loading"></div>
			</div>
		</div>

		<!-- Result Page -->
		<div class="stages" id="result">
			<a href="#" class="btn homebtn"><i class="fa fa-home"></i> <span data-l10n="home"></span></a>
			<div class="resultInner hidden">
			<a href="#" class="btn gallery"><i class="fa fa-th"></i> <span data-l10n="gallery"></span></a>
			<?php if($config['use_qr']){ echo '<a href="#" class="btn qrbtn"><span class="qrbtnlabel"><i class="fa fa-qrcode"></i> <span data-l10n="qr"></span></span></a>'; } ?>
			<?php if($config['use_print']){ echo '<a href="#" class="btn printbtn"><i class="fa fa-print"></i> <span data-l10n="print"></span></a>'; } ?>
			<a href="#" class="btn newpic"><i class="fa fa-camera"></i> <span data-l10n="newPhoto"></span></a>
			</div>
			<?php if($config['use_qr']){ echo '<div class="qr"></div>';} ?>
		</div>

		<!-- Gallery -->
		<div id="gallery">
			<div class="galInner">
				<div class="galHeader">
					<h1><span data-l10n="gallery"></span></h1>
					<a href="#" class="close_gal"><i class="fa fa-times"></i></a>
				</div>
				<div class="images" id="galimages">
					<?php
					$imagelist = ($config['gallery']['newest_first'] === true) ? array_reverse($images) : $images;
					foreach($imagelist as $image) {
						echo '<a href="/images/'.$image.'" data-size="1920x1280">
								<img src="/thumbs/'.$image.'" />
								<figure>Caption</figure>
							</a>';
					}
					?>
				</div>
			</div>
		</div>
		<?php if($config['show_fork']){ ?>
			<a target="_blank" href="https://github.com/andreknieriem/photobooth"><img style="position: absolute; top: 0; right: 0; border: 0;" src="https://camo.githubusercontent.com/652c5b9acfaddf3a9c326fa6bde407b87f7be0f4/68747470733a2f2f73332e616d617a6f6e6177732e636f6d2f6769746875622f726962626f6e732f666f726b6d655f72696768745f6f72616e67655f6666373630302e706e67" alt="Fork me on GitHub" data-canonical-src="https://s3.amazonaws.com/github/ribbons/forkme_right_orange_ff7600.png"></a>
		<?php } ?>
	</div>


	<!-- Root element of PhotoSwipe. Must have class pswp. -->
	<div class="pswp" tabindex="-1" role="dialog" aria-hidden="true">

		<!-- Background of PhotoSwipe.
	 	It's a separate element, as animating opacity is faster than rgba(). -->
		<div class="pswp__bg"></div>

		<!-- Slides wrapper with overflow:hidden. -->
		<div class="pswp__scroll-wrap">

			<!-- Container that holds slides.
			PhotoSwipe keeps only 3 of them in DOM to save memory.
			Don't modify these 3 pswp__item elements, data is added later on. -->
			<div class="pswp__container">
				<div class="pswp__item"></div>
				<div class="pswp__item"></div>
				<div class="pswp__item"></div>
			</div>

			<!-- Default (PhotoSwipeUI_Default) interface on top of sliding area. Can be changed. -->
			<div class="pswp__ui pswp__ui--hidden">
				<div class="pswp__top-bar">
					<!--  Controls are self-explanatory. Order can be changed. -->

					<div class="pswp__counter"></div>
					<button class="pswp__button pswp__button--close" title="Close (Esc)"></button>
					<button class="pswp__button pswp__button--share" title="Share"></button>
					<button class="pswp__button pswp__button--fs" title="Toggle fullscreen"></button>
					<button class="pswp__button pswp__button--zoom" title="Zoom in/out"></button>
					<?php if($config['use_print']){ echo '<button class="gal-print" title="Drucken"><i class="fa fa-print"></i></button>'; } ?>
					<?php if($config['use_qr']){ echo '<button class="gal-qr-code" title="Qr Code öffnen"><i class="fa fa-qrcode"></i></button>'; } ?>
					<!-- Preloader demo http://codepen.io/dimsemenov/pen/yyBWoR -->
					<!-- element will get class pswp__preloader--active when preloader is running -->
					<div class="pswp__preloader">
						<div class="pswp__preloader__icn">
	  				<div class="pswp__preloader__cut">
							<div class="pswp__preloader__donut"></div>
	  			</div>
				</div>
			</div>
		</div>

		<div class="pswp__share-modal pswp__share-modal--hidden pswp__single-tap">
			<div class="pswp__share-tooltip"></div>
		</div>

		<button class="pswp__button pswp__button--arrow--left" title="Previous (arrow left)">
		</button>

		<button class="pswp__button pswp__button--arrow--right" title="Next (arrow right)">
		</button>

		<div class="pswp__caption">
			<div class="pswp__caption__center"></div>
		</div>
		</div>
		</div>
			<div class="pswp__qr">

			</div>
	</div>

	<script type="text/javascript" src="/resources/js/jquery.js"></script>
	<script type="text/javascript" src="/resources/js/jquery.easing.1.3.js"></script>
	<script type="text/javascript" src="/resources/js/TweenLite.min.js"></script>
	<script type="text/javascript" src="/resources/js/EasePack.min.js"></script>
	<script type="text/javascript" src="/resources/js/jquery.gsap.min.js"></script>
	<script type="text/javascript" src="/resources/js/CSSPlugin.min.js"></script>
	<script type="text/javascript" src="/resources/js/photoswipe.min.js"></script>
	<script type="text/javascript" src="/resources/js/photoswipe-ui-default.min.js"></script>
	<script type="text/javascript" src="/resources/js/photoinit.js"></script>
	<script type="text/javascript" src="/resources/js/core.js"></script>
	<script type="text/javascript" src="/lang/<?php echo $config['language']; ?>.js"></script>
</body>
</html>
