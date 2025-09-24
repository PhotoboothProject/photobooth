# Printer troubleshooting

## Dye-Sublimation Photo Printers and Linux

Informative read about Dye-Sublimation Photo Printers and Linux can be found at [Peachy Photos Blog](https://www.peachyphotos.com/blog/stories/dye-sublimation-photo-printers-and-linux/)

## Printing on Linux with USB Printers

If your printer is connected via **USB**, you’ll need local drivers for it to work.

In many cases, there may not be an official driver provided by the manufacturer.

### Gutenprint Drivers

The **OpenSource Gutenprint** project provides support for a wide range of printers and is often the best option when no vendor drivers are available.

- Project website: [Gutenprint](http://gimp-print.sourceforge.net/)
- Supported printers: Hundreds of models across multiple brands

**Recommendation:** Compile and install the latest Gutenprint driver from source to ensure your printer drivers are fully up to date.

### Building the Latest Gutenprint on Linux

A detailed guide for compiling the latest Gutenprint driver can be found here:
[Building Modern Gutenprint](https://www.peachyphotos.com/blog/stories/building-modern-gutenprint/)

---

## Special printer specific notes

### Canon Selphy CP1300/CP1500

- make sure using latest firmware on your Canon Selphy

---

## How to administer CUPS remotely using the web interface?

By default the CUPS webinterface can only be accessed via [http://localhost:631](http://localhost:631) from your local machine.

To remote access CUPS from other clients you need to run the following commands:

```sh
sudo cupsctl --remote-any
sudo /etc/init.d/cups restart
```

---

## Printing Fails

If printing fails, make sure a **default printer** is defined. You can check or set it using:

```bash
lpoptions
````

If needed, you can **specify the printer** directly in the print command:

```bash
lp -d <Printer_Name> <file_to_print>
```

or using `lpr`:

```bash
lpr -P <Printer_Name> <file_to_print>
```

### Make sure `www-data` user can print

Sometimes, the web server user (`www-data`) does not have permission to print. You can fix this by adding `www-data` to the `lp` or `lpadmin` group (depending on your system):

```bash
sudo usermod -aG lp,www-data
```

After that, you may need to **restart the web server**:

```bash
sudo systemctl restart apache2   # or nginx, depending on your setup
```

**Tips:**

* Replace `<Printer_Name>` with the exact name of your printer (use `lpstat -p` to list printers).
* Ensure the printer is online and connected.
* For network printers, make sure the hostname or IP is correct.
* Replace `<file_to_print>` with `%s` if adding your print command to your Photobooth configuration.
