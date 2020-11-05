
Magento2 Delete Unused Product Images
=============================
Command Line module to validate database images and remove from pub/media/catalog/product those JPG or PNG's which are not present in the database.

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Add to your Magento2 composer.json repositories section, via running:

```
composer config repositories.moogento vcs https://github.com/moogento/Mage2UnusedImageRemover
```

or adding:

```
"repositories": [
   {
     "type": "vcs",
     "url": "https://github.com/moogento/Mage2UnusedImageRemover"
   }
 ],
```

Then run:

```
composer require ekouk/imagecleaner:dev-corrupted
```

or add:

```
"ekouk/imagecleaner": "dev-corrupted"
```

to the require section of your `composer.json` file.

Then run ``composer install``.

Once the files have been installed to vendor/ekouk/imagecleaner,

enable the module:-

```
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

Usage
-----

Run in check only mode which will report the number and size in Mb of unused images:
```
bin/magento ekouk:cleanimages
```

Run and delete images:
```
bin/magento ekouk:cleanimages -d
```

Run in check only mode which will report the number and size in Mb of <strong>corrupted</strong> images in pub/media/catalog/product folder:
```
bin/magento ekouk:cleancorruptedimages
```

Run in check only mode which will show files that are not image type:
```
bin/magento ekouk:cleancorruptedimages -s
```

Run in check only mode which will report the number and size in Mb of <strong>corrupted</strong> images in pub/media folder:
```
bin/magento ekouk:cleancorruptedimages -p
```

Run and delete <strong>corrupted</strong> images:
```
bin/magento ekouk:cleancorruptedimages -d
```

Notes
-----

This module will only remove jpg, png, jpeg images from pub/media/catalog/product which are not referenced in the database.
The default function without any switches will just report on unused files and NOT delete anything
You must use the -d switch to remove files.

This module is provided free of charge with no warranty. 

<strong>Please ensure you have a backup copy of your ```pub/media/catalog``` directory</strong>  just in case anything does go wrong.


Support
-----

If you need any help please log a ticket at [http://support.ekouk.com](http://support.ekouk.com)

