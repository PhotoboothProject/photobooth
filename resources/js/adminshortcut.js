var admincount = 0;

function countreset() {
	admincount = 0;
}

function adminsettings() {
	if (admincount == 3) {
		window.location.href="admin/index.php";
	}
	console.log(admincount);
	admincount++;
	setTimeout(countreset, 10000);
}
