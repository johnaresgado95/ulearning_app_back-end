<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Encore\Admin\Traits\DefaultDatetimeFormat;

class Lesson extends Model
{
    use HasFactory;
    use DefaultDateTimeFormat;

    protected $casts = [
        'video' => 'json'
    ];

    public function setVideoAttribute($value)
    {
        // This method json_encode converts the object to json array
        /*
            Associated Array
            'a'=>'val1',
            'b'=>'val2',
            .....
            {
                'a':'val1',
                'b':'val2',
            }
        */
        $this->attributes['video'] = json_encode(array_values($value));
    }

    public function getVideoAttribute($value)
    {
        // This is getter for Video Attribute

        $resVideo = json_decode($value, true) ?: [];

        if (!empty($resVideo)) {
            foreach ($resVideo as $k => $v) { // $k=>$v = Key and value pair
                $resVideo[$k]['name']=$v['name'];
                $resVideo[$k]['url'] = $v['url'];
                $resVideo[$k]['thumbnail'] = $v['thumbnail'];
            }
        }
        return $resVideo;
    }
}
