# photobooth+
A Photobooth webinterface for Raspberry Pi and Windows, based on [photobooth](https://github.com/andreknieriem/photobooth) by Andre Rinas.

I've extended the original [photobooth](https://github.com/andreknieriem/photobooth) with a print feature, so you can print newly taken pictures or any picture in the gallery. Photobooth uses the command line to print the picture. The command can be modified in ```config.inc.php```.

Modifications and new features:
- Pictures can be printed directly after they were taken or later from the gallery
- Moved a lot of parameters and settings into the ```config.inc.php```
- Changed the ```data.txt``` from a line seperated database into a JSON structure
- The images are now processed with GD/ImageMagick rather than avconv
- Now works on Windows and Linux
- Added [digiCamControl](http://digicamcontrol.com/) by Duka Istvan to control the camera and to take pictures under Windows
- Photobooth caches all generated QR-Codes, Thumbnails and Prints
- All directories are not automatically created if they doesn't exist
- The gallery can now be ordered ascending oder descending by picture age (see ```$config['gallery']['newest_first']``` in ```config.inc.php```)

### Prerequisites
- gphoto2 installed, if used on a Raspberry for DSLR control
- digiCamControl, if used unter Windows for DSLR control
- Apache

### Installation
- Download
- Put all files to your destination folder, e.g. /var/www/html/
- Change the styling to your needs
- Windows
    - Download [digiCamControl](http://digicamcontrol.com/) and extract the archive into ```digicamcontrol``` in the photobooth root

### Change Labels
There are two label files in the lang folder, one for de and one for en. The de lang-file is included at the bottom of the index.php.
If you want the english labels, just change it to en.js.
If you want to change the labels just change the de.js or en.js

### Changelog
1.2.0 - Printing feature, code rework, bugfixes
1.1.1 - Bugix - QR not working on touch devices
1.1.0 - Added QR Code to Gallery
1.0.0 - Initial Release  

### Thanks to
- [dimsemenov](https://github.com/dimsemenov/photoswipe) for photoswipe
- [t0k4rt](https://github.com/t0k4rt/phpqrcode) for phpqrcode
- [andrerinas](https://github.com/andreknieriem/) for the original photobooth
