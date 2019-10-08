# Neos Folde NodeType
[![Latest Stable Version](https://poser.pugx.org/breadlesscode/neos-nodetypes-folder/v/stable)](https://packagist.org/packages/breadlesscode/neos-nodetypes-folder)
[![Downloads](https://img.shields.io/packagist/dt/breadlesscode/neos-nodetypes-folder.svg)](https://packagist.org/packages/breadlesscode/neos-nodetypes-folder)
[![License](https://img.shields.io/github/license/breadlesscode/neos-nodetypes-folder.svg)](LICENSE)
[![GitHub stars](https://img.shields.io/github/stars/breadlesscode/neos-nodetypes-folder.svg?style=social&label=Stars)](https://github.com/breadlesscode/neos-nodetypes-folder/stargazers)
[![GitHub watchers](https://img.shields.io/github/watchers/breadlesscode/neos-nodetypes-folder.svg?style=social&label=Watch)](https://github.com/breadlesscode/neos-nodetypes-folder/subscription)

This Neos Plugin contains a folder nodetype. This Folder **isnt rendered in the Uri** by default.

The main idea & code is from [@sebobo](https://gist.github.com/Sebobo) from [this Gist](https://gist.github.com/Sebobo/7b12f8e46778321f7b1b02d4b9aaad85). Thanks for that!!!

## Warning

This package overrides the FrontendNodeRoutePartHandler!

## Installation
Most of the time you have to make small adjustments to a package (e.g., the configuration in Settings.yaml). Because of that, it is important to add the corresponding package to the composer from your theme package. Mostly this is the site package located under Packages/Sites/. To install it correctly go to your theme package (e.g.Packages/Sites/Foo.Bar) and run following command:

```bash
composer require breadlesscode/neos-nodetypes-folder --no-update
```

The --no-update command prevent the automatic update of the dependencies. After the package was added to your theme composer.json, go back to the root of the Neos installation and run composer update. Your desired package is now installed correctly.
