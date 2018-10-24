<?php

namespace Statamic\Addons\ImageOptimizer;

use Statamic\Addons\ImageOptimizer\ImageOptimizer;
use Statamic\Events\Data\AssetUploaded;
use Statamic\Extend\Listener;
use Statamic\API\Nav;

class ImageOptimizerListener extends Listener
{

    /**
     * The events to be listened for, and the methods to call.
     *
     * @var array
     */
    public $events = [

        
        'cp.nav.created' => 'addSettingsPage',
        'glide.generated' => 'handleGlide',
        
        AssetUploaded::class => 'handleAssets'

    ];

    /**
     * Add addon settings page
     *
    * @param Statamic\CP\Navigation\Nav $nav
    */
    public function addSettingsPage($nav)
    {

        $store = Nav::item('Optimize')->route('addon.settings', 'image-optimizer')->icon('images');
        $nav->addTo('tools', $store);

    }

    /**
     * Optimize new asset images
     *
     * @param Statamic\Events\Data\AssetUploaded $event
     */
    public function handleAssets(AssetUploaded $event)
    {

        $asset = $event->asset;

        if ($this->getConfig('handle_assets', true) && $asset->isImage())
        {
            
            $optimizer = new ImageOptimizer();

            $path = $asset->resolvedPath();
            $path = root_path($path);

            $optimizer->optimize($path);

        }

    }

    /**
     * Optimize new glide images
     *
     * @param string $path
     * @param array $params
     */
    public function handleGlide($path, $params)
    {

        if ($this->getConfig('handle_glide', true))
        {

            $optimizer = new ImageOptimizer();
            $path = realpath($path);

            $optimizer->optimize($path);

        }
    	
    }

}
