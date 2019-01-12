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
            'arguments'  => '--force --output=:file'

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

                $command = !$this->getConfig('advanced') ? $this->getCommand($optimizer['executable'], $optimizer['arguments']) : $optimizer['command'];
                $command = str_replace(':file', escapeshellarg($path), $command);

                $tempfile = strpos($command, ':temp') !== false;

                if ($tempfile)
                {

                    $temp = tempnam(sys_get_temp_dir(), 'imageoptimizer');
                    $command = str_replace(':temp', escapeshellarg($temp), $command);

                }

                $result = $this->optimize($command);

                if ($tempfile && filesize($tempfile))
                {

                    rename($temp, $path);

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

        $binary = $this->findBinary($executable) : $executable;
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

        return $finder->find($name, $default, [

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

    /**
     * Find bundled binary for optimizer
     *
     * @param string $name
     * @return string $binary
     */
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

        if ($process->isSuccessful())
        {

            Log::info( $process->getOutput() );

        }

        else
        {

            Log::error( $process->getErrorOutput() );

        }

        return $process->isSuccessful();

    }

}