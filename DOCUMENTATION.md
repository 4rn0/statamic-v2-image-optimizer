# Setup

* Download and unzip
* Copy the `ImageOptimizer` folder into `site/addons/`.

# Configuration and usage

You can view some optimization statistics and choose whether to automatically optimize the original Assets, the Glide images or both under `Tools` > `Optimizer`.

# CLI usage

Run the `please optimize` command to optimize all your existing image Assets and clear the glide cache.

## Optimization tools

The addon will use the following optimizers if they are available on your system:

- [JpegOptim](http://freecode.com/projects/jpegoptim)
- [Optipng](http://optipng.sourceforge.net/)
- [Pngquant 2](https://pngquant.org/)
- [Gifsicle](http://www.lcdf.org/gifsicle/)

It will try to find the executables in the following paths on your system:

    /usr/local
    /usr/local/bin
    /usr/bin
    /usr/sbin
    /usr/local/bin
    /usr/local/sbin
    /bin
    /sbin
    ~/bin

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