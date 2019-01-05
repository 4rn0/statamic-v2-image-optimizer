## Setup

- Download and unzip
- Copy the `ImageOptimizer` folder into `site/addons/`.

## Configuration and usage

You can view some optimization statistics and choose whether to automatically optimize the original Assets, the Glide images or both under `Tools` > `Optimizer`.

## CLI usage

Run the `please optimize` command to optimize all your existing image Assets and clear the glide cache.

## Optimization tools

The addon will use the following optimizers if they are available on your system:

- [JpegOptim](http://freecode.com/projects/jpegoptim)
- [Optipng](http://optipng.sourceforge.net/)
- [Pngquant 2](https://pngquant.org/)
- [Gifsicle](http://www.lcdf.org/gifsicle/)

It will try to find the executables in the following paths on your server:

    /usr/local
    /usr/local/bin
    /usr/bin
    /usr/sbin
    /usr/local/bin
    /usr/local/sbin
    /bin
    /sbin

**Sounds pretty technical, huh? Don't worry: ImageOptimizer comes with batteries included!** 🔋⚡ 

The addon includes precompiled versions of these optimizers for Linux, MacOS and Windows. If an optimizer is not available on your server it will try to use the included version. This will work with most servers and configurations, but if for some reason it doesn't, you can always try to install the optimizers yourself.

Here's how to install all the optimizers on Ubuntu:

```bash
sudo apt-get install jpegoptim
sudo apt-get install optipng
sudo apt-get install pngquant
sudo apt-get install gifsicle
```

Here's how to install the optimizers on MacOS (using [Homebrew](https://brew.sh/)):

```bash
brew install jpegoptim
brew install optipng
brew install pngquant
brew install gifsicle
```

## Customization

Aside from using the included optimizers it is also possible to change their default configuration or add some custom optimization tools like MozJPEG or cwebp by enabling the advanced settings. For each optimizer you will have to provide the mimetype of the images you want it to optimizer, the path to the executable on your server and the arguments the tool needs.

Please make sure the path to the executable and the arguments are correct. You can use :file to reference the full path to the image you are optimizing and :temp to use a temporary output file if the optimizer requires it.

For example, if you would like to use MozJPEG you could enter the following:

Type | Executable | Arguments
--- | --- | ---
image/jpeg | /usr/local/mozjpeg/bin/cjpeg | -quality 85 -optimize -outfile :temp