Add to `/etc/php/php.ini` if not present:
```
extension=pdo_sqlite
extension=sqlite3
```

Then in the code directory:
```sh
php -S 127.0.0.1:8000
```

And visit `127.0.0.1:8000` in your browser of choice.

> [!IMPORTANT]
> **Images Disclaimer**: The images in the `images/` folder are used for demonstration purposes only and are not covered by the GPL-3.0 license. These images may be subject to their own copyright restrictions. If you plan to use this code, please replace all images with your own or properly licensed alternatives.
