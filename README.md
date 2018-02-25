# statamic-optimize-images
Statamic v2 Addon to optimizes new Assets and Glide images

## Setup

* Copy the `ImageOptimizer` folder into `site/addons/`.
* Run `php please update:addons` to install dependencies

The package will uses various image optimization tools if they are present on your system. Please have a look at the documentation on [`psliwa/image-optimizer`](https://github.com/psliwa/image-optimizer#supported-optimizers) and make sure you install them if necessary.

## Settings

You can choose whether to optimize the original Assets, the Glide images or both under `Tools` > `Optimize`

## CLI

Run the `please optimize` command to optimize all your existing image Assets and clear the glide cache.