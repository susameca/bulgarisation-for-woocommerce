export const setCookie = (cname, cvalue, exdays) => {
	var d = new Date();
	d.setTime(d.getTime() + (exdays*24*60*60*1000));
	var expires = "expires="+ d.toUTCString();
	document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

export const getCookie = (cname) => {
	var name = cname + "=";
	var decodedCookie = decodeURIComponent(document.cookie);
	var ca = decodedCookie.split(';');

	for(var i = 0; i <ca.length; i++) {
		var c = ca[i];

		while (c.charAt(0) == ' ') {
			c = c.substring(1);
		}
		
		if (c.indexOf(name) == 0) {
			return c.substring(name.length, c.length);
		}
	}
	return "";
}

export const resizeBase64 = (base64String, maxWidth, maxHeight, format, compression, ratioFunction, successCallback) => {
	// Create and initialize two canvas
	let canvas = document.createElement("canvas");
	let ctx = canvas.getContext("2d");
	let canvasCopy = document.createElement("canvas");
	let copyContext = canvasCopy.getContext("2d");

	// Create original image
	let img = new Image();
	img.src = base64String;

	img.onload = function() {
		let ratioResult = ratioFunction(img.width, img.height, maxWidth, maxHeight);
		let widthRatio = ratioResult.width;
		let heightRatio = ratioResult.height;

		// Draw original image in second canvas
		canvasCopy.width = img.width;
		canvasCopy.height = img.height;
		copyContext.drawImage(img, 0, 0);

		// Copy and resize second canvas to first canvas
		canvas.width = img.width * widthRatio;
		canvas.height = img.height * heightRatio;

		ctx.imageSmoothingEnabled       = true;
	    ctx.mozImageSmoothingEnabled    = true;
	    ctx.oImageSmoothingEnabled      = true;
	    ctx.webkitImageSmoothingEnabled = true;
	    ctx.imageSmoothingQuality = 'high';

		copyContext.imageSmoothingEnabled       = true;
	    copyContext.mozImageSmoothingEnabled    = true;
	    copyContext.oImageSmoothingEnabled      = true;
	    copyContext.webkitImageSmoothingEnabled = true;
	    copyContext.imageSmoothingQuality = 'high';

		ctx.drawImage(canvasCopy, 0, 0, canvasCopy.width, canvasCopy.height, 0, 0, canvas.width, canvas.height);

		successCallback(canvas.toDataURL(format, compression));
	};

	img.onerror = function() {
		console.log('Error while loading image.');
	};
};

export const ratioFunction = (imageWidth, imageHeight, targetWidth, targetHeight) => {
	let ratio = 1;
	let heightRatio = 1;

	if(imageWidth > targetWidth) {
		ratio = targetWidth / imageWidth;
	}

	if(imageHeight > targetHeight) {
		heightRatio = targetHeight / imageHeight;
	}

	if ( heightRatio < ratio ) {
		ratio = heightRatio;
	}

	return {
		width: ratio,
		height: ratio
	};
};