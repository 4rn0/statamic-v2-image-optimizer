<?php

namespace Statamic\Addons\ImageOptimizer;

use Statamic\Events\Data\AssetUploaded;
use Statamic\Events\Data\AssetReplaced;
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

        
        'cp.add_to_head' => 'handleStyles',
        'cp.nav.created' => 'handleSettings',
        'fieldsets.json.show' => 'handleFieldset',
        'glide.generated' => 'handleGlide',
        
        AssetUploaded::class => 'handleAssets',
        AssetReplaced::class => 'handleAssets',

    ];

    /**
     * Return a <link> tag containing the addon stylesheet
     *
     * @return string
     */
    public function handleStyles()
    {
        
        return $this->css->tag('styles');

    }

    /**
     * Add ImageOptimizer settings page
     *
     * @param Statamic\CP\Navigation\Nav $nav
     */
    public function handleSettings($nav)
    {

        $item = Nav::item('Optimizer')->route('addon.settings', 'image-optimizer')->icon('images');
        $nav->addTo('tools', $item);

    }
    
    /**
     * Add ImageOptimizer fieldtype to assets fieldset
     *
     * @param Statamic\CP\Fieldset $fieldset
     */
    public function handleFieldset($fieldset)
    {

        if ($fieldset->name() === 'asset')
        {

            $sections = $fieldset->sections();
            $sections['imageoptimizer'] = [
    
                'fields' => [

                    'imageoptimizer' => [

                        'type' => 'image_optimizer'

                    ]

                ]

            ];

            $contents = $fieldset->contents();
            $contents['sections'] = $sections;

            $fieldset->contents($contents);

        }

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
            $optimizer->optimizeAsset($asset);

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
            $optimizer->optimizeGlide($path);

        }
    	
    }

}
