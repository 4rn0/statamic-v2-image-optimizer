<?php

namespace Statamic\Addons\ImageOptimizer;

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Log;
use Statamic\Extend\Extensible;
use Statamic\Config\Settings;
use Statamic\Assets\Asset;

class ImageOptimizer
{

    use Extensible;

    private $optimizers = [

        [

            'mimetype'   => 'image/jpeg',
            'executable' => 'jpegoptim',
            'arguments'  => '--strip-all --all-progressive -m85 :file'

        ],

        [

            'mimetype'   => 'image/gif',
            'executable' => 'gifsicle',
            'arguments'  => '-b -O3 :file'

        ],

        [

            'mimetype'   => 'image/png',
            'executable' => 'pngquant',
            'arguments'  => '--force --output=:file :file'

        ],

        [

            'mimetype'   => 'image/png',
            'executable' => 'optipng',
            'arguments'  => '-i0 -o2 :file'

        ]

    ];

    /**
     * Optimize Asset, save metadata
     *
     * @param Statamic\Assets\Asset $asset
     * @return Statamic\Assets\Asset $asset
     */
    public function optimizeAsset(Asset $asset)
    {

        $path = root_path($asset->resolvedPath());
        $path = realpath($path);

        $data = $asset->get('imageoptimizer', []);

        if (empty($data)) {

            $filesize = filesize($path);
            $data['original_size'] = $filesize;

        }

        $this->optimizePath($path);
        clearstatcache(true, $path);

        $filesize = filesize($path);
        $data['current_size'] = $filesize;

        $asset->set('imageoptimizer', $data);
        $asset->save();

        return $asset;

    }

    /**
     * Optimize Glide image if necessary
     *
     * @param string $path
     */
    public function optimizeGlide($path)
    {

        $path = $this->getRealGlidePath($path);
        $key  = 'imageoptimizer' . pathinfo($path, PATHINFO_FILENAME);

        if ($this->cache->exists($key))
        {
        
            $optimized = $this->cache->get($key);
            $modified = filemtime($path);

            if ($modified == $optimized)
            {
                
                return;

            }

        }

        $this->optimizePath($path);
        $this->cache->put($key, filemtime($path));

    }

    /**
     * Get actual path to Glide image
     * Workaround for bug: https://github.com/statamic/v2-hub/issues/2317
     *
     * @param string $path
     * @return string $path
     */
    private function getRealGlidePath($path)
    {

        $settings = app(Settings::class);

        $cachedPath = $settings->get('assets.image_manipulation_cached_path');
        $cached = $settings->get('assets.image_manipulation_cached');
        
        if ($cached && $cachedPath !== 'local/cache/glide' && strpos($path, 'local/cache/glide') !== false)
        {
            
            $path = str_replace('local/cache/glide', trim($cachedPath, '/'), $path);

        }

        $path = realpath($path);

        return $path;

    }

    /**
     * Optimize image by path, save statistics
     *
     * @param string $path
     */
    private function optimizePath($path)
    {

        if (file_exists($path)) {

            $this->attemptOptimization($path);

        }

    }

    /**
     * Attempt image optimizations
     *
     * @param string $path
     */
    private function attemptOptimization($path)
    {

        $optimizers = $this->getConfig('advanced') ? $this->getConfig('optimizers', []) : $this->optimizers;
        $filetype = mime_content_type($path);

        foreach ($optimizers as $optimizer)
        {

            if ($optimizer['mimetype'] === $filetype)
            {

                $command = !$this->getConfig('advanced') ? $this->getCommand($optimizer['executable'], $optimizer['arguments']) : $optimizer['command'];
                $command = str_replace(':file', escapeshellarg($path), $command);

                $tempfile = strpos($command, ':temp') !== false;

                if ($tempfile)
                {

                    $tempfile = tempnam(sys_get_temp_dir(), 'imageoptimizer');
                    $command = str_replace(':temp', escapeshellarg($tempfile), $command);

                }

                $this->optimize($command);

                if ($tempfile && filesize($tempfile))
                {

                    rename($tempfile, $path);

                }

            }

        }

    }

    /**
     * Create optimizer command
     *
     * @param string $executable
     * @param string $arguments
     * @return string $command
     */
    private function getCommand($executable, $arguments)
    {

        $binary = $this->findBinary($executable);
        $command = $binary . ' ' . $arguments;

        return $command;

    }

    /**
     * Find executable binary for optimizer
     *
     * @param string $name
     * @return string $binary
     */
    private function findBinary($name)
    {

        $finder = new ExecutableFinder();

        $default = $this->findBundledBinary($name);
        $binary = basename($name);

        return $finder->find($binary, $default, [

            '/usr/local',
            '/usr/local/bin',
            '/usr/bin',
            '/usr/sbin',
            '/usr/local/bin',
            '/usr/local/sbin',
            '/bin',
            '/sbin'

        ]);

    }

    private function findBundledBinary($name)
    {

        if (in_array(PHP_OS, ['Linux'])) {

            return realpath($this->getDirectory() . '/bin/linux/' . $name);

        }

        if (in_array(PHP_OS, ['Darwin'])) {

            return realpath($this->getDirectory() . '/bin/mac/' . $name);

        }

        if (in_array(PHP_OS, ['WIN32', 'WINNT', 'Windows'])) {

            return realpath($this->getDirectory() . '/bin/win/' . $name . '.exe');

        }

        return $name;

    }

    /**
     * Execute optimizer command
     *
     * @param string $command
     * @return bool $result
     */
    private function optimize($command)
    {

        Log::info('Starting optimization: ' . $command);

        $process = new Process($command);

        $process->setTimeout(60);
        $process->enableOutput();
        $process->run();

        if ($process->isSuccessful()) {

            Log::info($process->getOutput());

        }

        else {

            Log::error($process->getErrorOutput());

        }

        return $process->isSuccessful();

    }

}