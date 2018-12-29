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

        'image/jpeg' => [

            'jpegoptim' => ['--strip-all', '--all-progressive', '-m85']

        ],

        'image/gif' => [

            'gifsicle' => ['-b', '-O3']

        ],

        'image/png' => [

            'pngquant' => ['--force', '--output=%s'], 
            'optipng' => ['-i0', '-o2']

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

        $filesize = $this->optimizePath($path);
        $data['current_size'] = $filesize;

        $asset->set('imageoptimizer', $data);
        $asset->save();

        return $asset;

	}

    /**
     * Optimize image by path, save statistics
     *
     * @param string $path
     * @return int $filesize
     */
    public function optimizePath($path)
    {

        $path = realpath($path);
        
        $this->attemptOptimization($path);
        clearstatcache(true, $path);

        return filesize($path);

    }

    /**
     * Attempt image optimizations
     *
     * @param string $path
     */
    private function attemptOptimization($path)
    {

        $filetype = mime_content_type($path);

        if (array_key_exists($filetype, $this->optimizers))
        {

            foreach ($this->optimizers[$filetype] as $name => $options)
            {

                $this->optimize( 

                    $this->getCommand($name, $options, $path)

                );

            }

        }

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

        $this->logger( !$process->isSuccessful() ? $process->getErrorOutput() : $process->getOutput() );

    }

    /**
     * Create optimizer command
     *
     * @param string $name
     * @param array $options
     * @param string $path
     * @return string $command
     */
    private function getCommand($name, $options, $path)
    {

        $binary = $this->findBinary($name);

        $arguments = implode(' ', $options);
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

        return (new ExecutableFinder())->find($name, $name, [

            '/usr/local',
            '/usr/local/bin',
            '/usr/bin',
            '/usr/sbin',
            '/usr/local/bin',
            '/usr/local/sbin',
            '/bin',
            '/sbin',
            '~/bin'

        ]);

    }

    /**
     * Write to log if addon is configured to do so
     *
     * @param string $message
     */
    private function logger($message)
    {

        if ($this->getConfig('log_optimizer', true) && $message)
        {

            Log::info($message);

        }

    }

}