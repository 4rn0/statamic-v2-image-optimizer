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

            'mimetype'   => 'image/jpeg',
            'executable' => 'jpegoptim',
            'arguments'  => '--strip-all --all-progressive -m85'

        ],

        [

            'mimetype'   => 'image/gif',
            'executable' => 'gifsicle',
            'arguments'  => '-b -O3'

        ],

        [

            'mimetype'   => 'image/png',
            'executable' => 'pngquant',
            'arguments'  => '--force --output=%s'

        ],

        [

            'mimetype'   => 'image/png',
            'executable' => 'optipng',
            'arguments'  => '-i0 -o2'

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

        $path = root_path( $asset->resolvedPath() );
        $path = realpath($path);

        $data = $asset->get('imageoptimizer', []);

        if (empty($data))
        {

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
        
        if (file_exists($path))
        {

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

                $this->optimize( 

                    $this->getCommand($optimizer['executable'], $optimizer['arguments'], $path)

                );

            }

        }

    }

    /**
     * Create optimizer command
     *
     * @param string $executable
     * @param string $arguments
     * @param string $path
     * @return string $command
     */
    private function getCommand($executable, $arguments, $path)
    {

        $binary = !$this->getConfig('advanced') ? $this->findBinary($executable) : $executable;
        $arguments = sprintf($arguments, escapeshellarg($path));

        return "\"{$binary}\" {$arguments} " . escapeshellarg($path);

    }

    /**
     * Find executable binary for optimizer
     *
     * @param string $name
     * @return string $binary
     */
    private function findBinary($name)
    {

        return (new ExecutableFinder())->find($name, $this->findBundledBinary($name), [

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

        $bits = PHP_INT_SIZE * 8;
        $os = PHP_OS;

        if (in_array($os, ['Linux'])) {

            return realpath($this->getDirectory() . '/bin/linux-' . $bits . '/' . $name);

        }

        if (in_array($os, ['Darwin'])) {

            return realpath($this->getDirectory() . '/bin/mac-' . $bits . '/' . $name);

        }

        if (in_array($os, ['WIN32', 'WINNT', 'Windows'])) {

            return realpath($this->getDirectory() . '/bin/win-' . $bits . '/' . $name . '.exe');

        }

        return $name;

    }

    /**
     * Execute optimizer command
     *
     * @param string $command
     */
    private function optimize($command)
    {
        
        $process = new Process($command);

        $process->setTimeout(60);
        $process->enableOutput();
        $process->run();

        if (!$process->isSuccessful())
        {

            Log::info( $process->getErrorOutput() );

        }

    }

}