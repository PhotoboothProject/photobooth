# photobooth
A Photobooth Webinterface for Raspberry Pi

I've build this for my own wedding photobooth. There are some things I will might add/change in the future for example:
- database support(mysql,mongo,or what you need)
- image processing with php,gd or imagemagick
- admin panel for settings

### Prerequisites
- gphoto2 installed on a raspberry for dslr control
- apache and installed on a raspberry
- avconv for image processing

### installation
- Download
- Put all files to your destination folder, e.g. /var/www/html/
- Change the styling to your needs

### Change Labels
There are two label files in the lang folder, one for de and one for en. The de lang-file is included at the bottom of the index.php.
If you want the english labels, just change it to en.js.
If you want to change the labels just change the de.js or en.js

### Changelog
1.1.1 - Bugix - QR not working on touch devices
1.1.0 - Added QR Code to Gallery   
1.0.0 - Initial Release  

### Thanks to
- [dimsemenov](https://github.com/dimsemenov/photoswipe) for photoswipe
- [t0k4rt](https://github.com/t0k4rt/phpqrcode) for phpqrcode
