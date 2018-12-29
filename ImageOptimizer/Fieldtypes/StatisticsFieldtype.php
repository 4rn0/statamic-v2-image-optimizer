<?php

namespace Statamic\Addons\ImageOptimizer\Fieldtypes;

use Statamic\Addons\ImageOptimizer\HasStatistics;
use Statamic\Extend\Fieldtype;

class StatisticsFieldtype extends Fieldtype
{
	
	use HasStatistics;
	 
    public $selectable = false;

    public function preProcess($data)
    {

    	return $this->getStatistics();

    }

}
