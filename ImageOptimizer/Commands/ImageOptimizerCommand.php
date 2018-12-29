<?php

namespace Statamic\Addons\ImageOptimizer\Commands;

use Statamic\Addons\ImageOptimizer\ImageOptimizer;
use Statamic\Extend\Command;
use Statamic\Api\Asset;

class ImageOptimizerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'optimize';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimizes all your asset images';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $this->call('clear:glide');

        $optimizer = new ImageOptimizer();
        $assets = Asset::all();
        
        $this->output->progressStart( $assets->count() );

        foreach ($assets as $asset)
        {

            if ($asset->isImage())
            {

                $optimizer->optimizeAsset($asset);

            }

            $this->output->progressAdvance();

        }

        $this->output->progressFinish();

    }

}