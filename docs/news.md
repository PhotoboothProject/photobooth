# News

Updates, announcements and release notes from the Photobooth team.

## December 2025

2025 was a big year—more polish, smarter tools, and plenty of community-driven wins. We folded in long-requested features, hardened the setup path, and smoothed rough edges across capture, printing, and theming so the booth feels faster and friendlier. Here’s what landed.

- **Custom collage refresh** — Simplified, customizable collage flow with a new 2+1 layout, per-slot placeholders, and clearer guidance so layouts can be built without hand-editing JSON.
- **Documentation cleanup** — Streamlined navigation, refreshed guides, and general restructuring for easier browsing and search.
- **New Setup Wizard** — The whiptail-based wizard now logs stdout/stderr, avoids screen flashing, and guides kiosk/autostart and permission setup with saner defaults.
- **Background magic** — Native rembg integration with advanced controls, logging, safer installation, and improved compositing.
- **Look & feel** — Theme management (with documentation), custom CSS, collage color presets, virtual keyboard layouts and colors, Git version indicator, clearer admin hints, and uploadable keying backgrounds.
- **Collages & media** — New layout enum, rotation range control, video preview sizing, safer placeholders, automatic background/frame toggles, preview caching, gallery dimension caching, optional result blur, iOS preview fixes, and custom `stage.start.php` support.
- **Printing & sharing** — Copy prompts and print-limit endpoint, longer timeouts, multi-print fixes, queued message timing improvements, multi-recipient email with virtual keyboard support, higher minimum JPEG quality, configurable email fields, and Turkish language support.
- **Hardware & inputs** — Hardware photo button support, improved USB HID hardware-button handling, hardware keyboard for admin PIN entry, remote buzzer on chroma, support for all input types, IP whitelisting, and refreshed keypad interactions.
- **Setup & reliability** — Tailwind v4 and PHP 8.4 upgrades, more robust `install_rembg`, hardened USB sync, resettable box info, safer uploads and video backgrounds, cached IP/host resolution, tighter collage defaults, and smoother installer updates.
- **Bug fixes** — More resilient selfie and chroma previews, loader cleanup, proper hiding of video backgrounds during capture, validated PHP PPAs/flags/permissions, fixed multi-print casting and deprecations, explicit rotate errors, stronger rembg typing/logging/error handling, corrected stop-second timing, fixed collage/theme paths and placeholder limits, iOS preview fixes, admin tooltip/label and default path corrections, and extensive documentation/link cleanups.
and many doc/link cleanups.

Thank you for all the contributions, testing, and thoughtful feedback that shaped these releases. Keep sharing what you build and what you need—we’ll keep pushing Photobooth forward. See you in 2026!


---

## December 2024

Hey together!

This year almost comes to an end.

I am glad to see the community is growing every day and I am proud about the support and help from everyone! It's kind of special and unique seeing user helping each other like done in our community!

I am happy about all the contributions made to the project - especially Benjamin Kott helped a lot reworking and improving the source of Photobooth! With the improved source and code quality Photobooth runs always stable on latest development version!

There's still work left before Photobooth v5 can be released as a stable release, due to changes in private life I haven't had the time needed for it.

Photobooth stays feature complete for this year in latest development source, only dependencies updates will be applied.

I hope everyone can enjoy the Christmas days and have a good start into the new year 2025!

Best regards

Andi

---

## 17 September 2024

Photobooth v4.5.1 has been released.

The release keeps Photobooth v4 compatible with latest Photobooth installer and allows automated installation tests via GitHub actions.

Besides that only dependencies have been updated to latest version.

The changed Onoff library should be able to handle the changed GPIO sysfs on latest PiOS kernel.

We're still working on rewriting the Photobooth source for the upcoming v5 release - there's still some work left before the new version can be released.

The current development version runs nicely and stable for most user! We are always happy about feedback and helping hands!

New feature have been added already and the UI got an overhaul in a lot of places.

Like always: The full Changelog can be found [here](changelog.md).

If you're running latest development version already: there's no need to install Photobooth v4.5!

Your Photobooth-Team

---

## 09 January 2024

It's been a while, but here's some news outside of our Community on Telegram.

Actually the source code of Photobooth is rewritten in almost all sections of Photobooth and there's still some work left before Photobooth v5 can be released.

Photobooth v4.4.0 was released! This release is meant as bugfix-release to fix some known bugs, retain Windows compatibility and to keep compatibility with the changed install steps on latest development version.
A few new features have made it inside this release, but more to come with the upcoming Photobooth v5!

And don't worry! The current development version runs nicely and stable for most user! We are always happy about feedback and helping hands!

Like always: The full Changelog can be found [here](changelog.md).

Your Photobooth-Team

---

## 24 December 2022

Hey everyone!

Photobooth again was improved a lot this year and a lot of user wishes have been added to the project.

Thanks to everyone for being part of this community, your feedback and your help to make Photobooth such a great OpenSource Project!

We hope you have a safe and relaxing christmas time!

Your Photobooth-Team

---

## 06 December 2022

Photobooth v4.3.1 released!

Build dependencies have been updated and the process of taking an image was improved to optimize the timings between the single actions.
The visible countdown now is independent of the time we need to take an image, the defined offset will be respected now.
Also we now don't wait for the cheese message to end, the picture will be taken without waiting for it.
A small bug was fixed, where the shutter animation was started twice if an cheese image is used.

Like always: The full Changelog can be found [here](changelog.md).

Enjoy Photobooth v4.3.1!

---

## 28 November 2022

Photobooth v4.3.0 released!

Some minor bugs have been fixed, build dependencies have been updated, new Features have been added.

Like always: The full Changelog can be found [here](changelog.md).

Enjoy Photobooth v4.3.0!

---

## 16 October 2022

Photobooth v4.2.0 released today!

Some minor bugs have been fixed, PHPMailer and build dependencies have been updated.

The full Changelog can be found [here](changelog.md).

Enjoy Photobooth v4.2.0!

---

## 30 September 2022

We're proud to release Photobooth v4.1.0!

Some bugs have been fixed, some new features have made it's way into the new version and some code have been cleaned.

Logging is added to save and reset actions via Adminpanel for easier debugging.

The full Changelog can be found [here](changelog.md).

Enjoy Photobooth v4.1.0!

---

## 10 September 2022

We're proud to release Photobooth v4.0.0 with the code switch to PhotoboothProject which contains a lot of Bugfixes and user-wishes could be integrated.

Photobooth v4.0.0 comes in a new _**modern squared**_ look!
