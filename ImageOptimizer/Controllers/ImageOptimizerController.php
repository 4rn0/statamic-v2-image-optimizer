<?php

namespace Statamic\Addons\ImageOptimizer\Controllers;

use Statamic\Addons\ImageOptimizer\ImageOptimizer;
use Statamic\Addons\ImageOptimizer\HasStatistics;
use Statamic\Extend\Controller;
use Statamic\Api\Asset;
use Illuminate\Http\Request;

class ImageOptimizerController extends Controller
{

	use HasStatistics;

	public function optimize(Request $request)
	{

		if ($asset = Asset::find($request->asset))
		{

			$optimizer = new ImageOptimizer();
			$asset = $optimizer->optimizeAsset($asset);

			$asset->setSupplement('last_modified_relative', $asset->lastModified()->diffForHumans());

			return [

				'statistics' => $this->getStatistics(),
				'asset' => $asset->toArray()

			];

		}

		abort(404);

	}

}