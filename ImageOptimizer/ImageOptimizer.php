<?php

namespace Statamic\Addons\ImageOptimizer;

use Statamic\Extend\Extensible;
use Log;

class ImageOptimizer
{

	use Extensible;

    /**
     * The OptimizerFactory instance
     *
     * @var null
     */
	private $optimizer;

    /**
     * Create a new command instance.
     */
	public function __construct()
	{

		$this->optimizer = $this->getOptimizer();

	}

    /**
     * Optimize image by path, log results if applicable
     *
     * @param string $path
     */
	public function optimize($path)
	{

		if ($this->getConfig('log_optimizer', true))
		{
        
        	$before = filesize($path);
            $filename = basename($path);

        }

        $this->optimizer->optimize($path);

        if ($this->getConfig('log_optimizer', true))
        {

        	$after = filesize($path);
            $percentage = (($before - $after) / $before) * 100;

        	Log::info('Optimized ' . $filename . ' - saved ' . $percentage . '%');

        }

	}

    /**
     * Get the OptimizerFactory instance
     *
     * @return \ImageOptimizer\OptimizerFactory $optimzer
     */
    private function getOptimizer()
    {

        $logger = $this->getConfig('log_optimizer', true) ? app('log') : null;
        $factory = new \ImageOptimizer\OptimizerFactory(['ignore_errors' => true], $logger);
        
        return $factory->get();

    }

}