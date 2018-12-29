<?php

namespace Statamic\Addons\ImageOptimizer;
use Statamic\Api\Asset;

trait HasStatistics {

    /**
     * Get statistics
     *
     * @return array $statistics
     */
    public function getStatistics()
    {

        $optimized = collect();
        $original = collect();
        $current = collect();
        $images = collect();

        $assets = Asset::all()->filter(function($asset) {

            return $asset->isImage();

        });

        foreach ($assets as $asset)
        {

            if ($data = $asset->get('imageoptimizer'))
            {

                $original->push( $data['original_size'] );
                $current->push( $data['current_size'] );
                $optimized->push( $asset->id() );
                
            }

            $images->push( $asset->id() );

        }

        return [

            'original_size' => $original->sum(),
            'current_size' => $current->sum(),
            'optimized' => $optimized,
            'images' => $images

        ];

    }

}