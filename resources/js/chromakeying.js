function greenToTransparency(imageIn, imageOut){
	for(var y=0; y<imageIn.getHeight(); y++){
		for(var x=0; x<imageIn.getWidth(); x++){

			var color = imageIn.getIntColor(x, y);
			var r = imageIn.getIntComponent0(x, y);
			var g = imageIn.getIntComponent1(x, y);
			var b = imageIn.getIntComponent2(x, y);

			var hsv = MarvinColorModelConverter.rgbToHsv([color]);
			//if(hsv[0] >= 60 && hsv[0] <= 130 && hsv[1] >= 0.4 && hsv[2] >= 0.3){
			if(hsv[0] >= 60 && hsv[0] <= 200 && hsv[1] >= 0.2 && hsv[2] >= 0.2){
				imageOut.setIntColor(x, y, 0, 127, 127, 127);
			}
			else{
				imageOut.setIntColor(x, y, color);
			}
		}
	}
}

function reduceGreen(image){
	for(var y=0; y<image.getHeight(); y++){
		for(var x=0; x<image.getWidth(); x++){
			var r = image.getIntComponent0(x, y);
			var g = image.getIntComponent1(x, y);
			var b = image.getIntComponent2(x, y);
			var color = image.getIntColor(x, y);
			var hsv = MarvinColorModelConverter.rgbToHsv([color]);

			if(hsv[0] >= 60 && hsv[0] <= 130 && hsv[1] >= 0.15 && hsv[2] >= 0.15){
				if((r*b) !=0 && (g*g) / (r*b) > 1.5){
					image.setIntColor(x, y, 255, (r*1.4), g, (b*1.4));
				} else{
					image.setIntColor(x, y, 255, (r*1.2), g, (b*1.2));
				}
			}
		}
	}
}

function alphaBoundary(imageOut, radius)
{
	var ab = new AlphaBoundary();
	for(var y=0; y<imageOut.getHeight(); y++){
		for(var x=0; x<imageOut.getWidth(); x++){
			ab.alphaRadius(imageOut, x, y, radius);
		}
	}
}

var mainImage;
var mainImageSrc;
var mainImageWidth;
var mainImageHeight;
function setMainImage(imgSrc) {
	var image = new MarvinImage();
	image.load(imgSrc, function(){
		mainImageSrc = imgSrc;
		mainImageWidth = image.getWidth();
		mainImageHeight = image.getHeight();

		var imageOut = new MarvinImage(image.getWidth(), image.getHeight());

		//1. Convert green to transparency
		greenToTransparency(image, imageOut);

		// 2. Reduce remaining green pixels
		reduceGreen(imageOut);

		// 3. Apply alpha to the boundary
		alphaBoundary(imageOut, 6);

		var tmpCanvas = document.createElement('canvas');
		tmpCanvas.width  = mainImageWidth;
		tmpCanvas.height = mainImageHeight;
		imageOut.draw(tmpCanvas);

		mainImage = new Image();
		mainImage.src = tmpCanvas.toDataURL("image/png");
		mainImage.onload = function(){
			drawCanvas();
		}
	});
}


var backgroundImage;
function setBackgroundImage(url) {
	backgroundImage = new Image();
	backgroundImage.src = url;
	backgroundImage.onload = function(){
		drawCanvas();
	}
}

function drawCanvas() {
	var canvas = document.getElementById("mainCanvas");
	if (typeof mainImage !== "undefined" && mainImage !== null) {
		canvas.width  = mainImage.width;
		canvas.height = mainImage.height;
	} else if (typeof backgroundImage !== "undefined" && backgroundImage !== null) {
		canvas.width  = backgroundImage.width;
		canvas.height = backgroundImage.height;
	}

	var ctx = canvas.getContext ? canvas.getContext('2d') : null;
	ctx.clearRect(0, 0, canvas.width, canvas.height);

	if (typeof backgroundImage !== "undefined" && backgroundImage !== null) {
		if (typeof mainImage !== "undefined" && mainImage !== null) {
			var size = calculateAspectRatioFit(backgroundImage.width, backgroundImage.height, mainImage.width, mainImage.height);
			ctx.drawImage(backgroundImage,0,0, size.width, size.height);
		} else {
			ctx.drawImage(backgroundImage,0,0, backgroundImage.width, backgroundImage.height);
		}
	}

	if (typeof mainImage !== "undefined" && mainImage !== null) {
		ctx.drawImage(mainImage,0,0);
	}
}

function calculateAspectRatioFit(srcWidth, srcHeight, maxWidth, maxHeight) {
    var ratio = Math.max(maxWidth / srcWidth, maxHeight / srcHeight);
    return { width: srcWidth*ratio, height: srcHeight*ratio };
 }

function printImage() {
	if (typeof mainImageSrc === "undefined" || mainImageSrc === null) {
		return;
	}
	var canvas = document.getElementById("mainCanvas");
	var dataURL = canvas.toDataURL("image/png");
	$.post( "lib_php/print.php", { imgData: dataURL, mainImageSrc: mainImageSrc }, function( status ) {
	}, "json");
}

function saveImage() {
	if (typeof mainImageSrc === "undefined" || mainImageSrc === null) {
		return;
	}
	var canvas = document.getElementById("mainCanvas");
	var dataURL = canvas.toDataURL("image/png");
	$.post( "lib_php/save.php", { imgData: dataURL, mainImageSrc: mainImageSrc }, function( url ) {

	});
}
