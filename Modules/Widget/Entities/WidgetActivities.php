<?php

namespace Modules\Widget\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WidgetActivities extends Model
{
    use HasFactory;

    protected $fillable = [];
    
    protected static function newFactory()
    {
        return \Modules\Widget\Database\factories\WidgetActivitiesFactory::new();
    }
}
