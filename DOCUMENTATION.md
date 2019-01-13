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

Aside from using the included optimizers it is also possible to change their default configuration or add some custom optimization tools like MozJPEG or cwebp by enabling the advanced settings. For each optimizer you will have to provide the mimetype of the images you want it to optimize and the command you would like to run on the server.

Please make sure the name of the executable and the arguments are correct. You can use `{binary:executable_name}` where `executable_name` has to be the correct name, you don't need to set a path to the executable, just the name.  
Furthermore you can use `:file` to reference the full path to the original image you are optimizing and `:temp` to use a temporary output file if the optimizer requires it. The contents of the `:temp` file will automatically be copied back to the original file after the optimization, therefore overwriting the original.

For example, if you would like to use MozJPEG you could enter the following:

Type | Command
--- | ---
image/jpeg | {binary:cjpeg} -quality 50 -optimize -progressive :file >:temp
