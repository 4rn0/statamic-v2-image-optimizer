<?php

namespace Statamic\Addons\ImageOptimizer;

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Log;
use Statamic\Extend\Extensible;
use Statamic\Assets\Asset;

class ImageOptimizer
{

    use Extensible;

    private $optimizers = [

        [

            'mimetype' => 'image/jpeg',
            'command'  => '{binary:jpegoptim} --strip-all --all-progressive -m85 :file',

        ],

        [

            'mimetype' => 'image/gif',
            'command'  => '{binary:gifsicle} -b -O3 :file',

        ],

        [

            'mimetype' => 'image/png',
            'command'  => '{binary:pngquant} --force :file --output=:file',

        ],

        [

            'mimetype' => 'image/png',
            'command'  => '{binary:optipng} -i0 -clobber -o5 :file',

        ]

    ];

    /*
     * The temporary file, required by some image optimizers
     */
    private $temp = false;

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
     * Optimize image by path, save statistics
     *
     * @param string $path
     */
    public function optimizePath($path)
    {

        $path = realpath($path);

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

        foreach ($optimizers as $optimizer) {

            if ($optimizer['mimetype'] === $filetype) {
                $command = $this->getCommand($optimizer['command'], $path);
                $result = $this->optimize($command);

                if ($this->temp && $result) {
                    $size = filesize($this->temp);

                    if (is_int($size) && filesize($this->temp) > 0) {
                        rename($this->temp, $path);
                    }
                    else {
                        Log::info("Temp File size was 0 bytes. Please double-check your CLI arguments.");
                    }

                }

            }

            // reset temp path
            $this->temp = false;

        }

    }

    /**
     * Create optimizer command
     *
     * @param string $command
     * @param string $path
     * @return string
     */
    private function getCommand($command, $path)
    {

        // locate binary by using name from {binary:name} placeholder
        try {
            preg_match('/{binary:.*?}/', $command, $placeHolder);
            $binaryName = str_replace(['{binary:', '}'], '', $placeHolder[0]);
            $binary = $this->findBinary($binaryName);
        } catch (\Exception $e) {
            Log::info($e);
            exit(1);
        }

        // build command & replace placeholders
        $exec = str_replace($placeHolder[0], $binary, $command);
        $exec = preg_replace('/:file?/', escapeshellarg($path), $exec);
        $tempfile = strpos($exec, ':temp') !== false;

        if ($tempfile) {

            $this->temp = tempnam(sys_get_temp_dir(), 'imageoptimizer');
            $exec = preg_replace('/:temp?/', escapeshellarg($this->temp), $exec);

        }

        return trim($exec);

    }

    /**
     * Find executable binary for optimizer
     *
     * @param string $name
     * @return string $binary | bool false
     *
     * @throws \Exception
     */
    private function findBinary($name)
    {

        $finder = new ExecutableFinder();
        $exe = basename($name);
        $default = $this->findBundledBinary($exe);

        $binary = $finder->find($exe, $default, [

            '/usr/local',
            '/usr/local/bin',
            '/usr/bin',
            '/usr/sbin',
            '/usr/local/bin',
            '/usr/local/sbin',
            '/bin',
            '/sbin'

        ]);

        if ( ! $binary) {
            throw new \Exception("Binary {$name} doesn't exist on your system, please install.");
        }

        return $binary;

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