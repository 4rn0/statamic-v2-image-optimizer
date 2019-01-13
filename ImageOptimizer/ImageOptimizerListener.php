<?php

namespace Statamic\Addons\ImageOptimizer;

use Statamic\Config\Settings;
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

    private $settings;

    function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

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

        // workaround for bug: https://github.com/statamic/v2-hub/issues/2317
        // correct path to asset if "Serve cached images directly" is activated.
        $serveDirect = $this->settings->get('assets.image_manipulation_cached');
        $cachedPath = $this->settings->get('assets.image_manipulation_cached_path');
        if ($serveDirect && $cachedPath != 'local' && strpos($path, 'local/cache/glide') !== false)
        {
            $path = realpath(str_replace('local/cache/glide', trim($cachedPath, '/'), $path));
        }

        if ($this->getConfig('handle_glide', true))
        {
            
            $optimizer = new ImageOptimizer();
            $optimizer->optimizePath($path);

        }
    	
    }

}
