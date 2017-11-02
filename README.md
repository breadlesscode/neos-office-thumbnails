# NEOS office thumbnails generator
This NEOS CMS Plugin is for generating thumbnails of office documents. 

## Installation

This package can be installed through Composer.
```bash
composer require breadlesscode/neos-office-thumbnails
```

## Requirements 
This package needs [LibreOffice](https://libreoffice.org/) for the convertion.


## Configuration 

```yaml

Neos:
  Media:
    thumbnailGenerators:
      Breadlesscode\Domain\Model\ThumbnailGenerator\OfficeThumbnailGenerator:
        priority: 120 
        timeout: 120 # timeout of the convertionb process
        binPath: 'libreoffice' # path to the libreoffice binary
```
