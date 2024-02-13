<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Course;

class CourseController extends Controller
{
    //
    public function courseList() {

        // Select method helps you to select the fields from Model and doesn't return anything
        // Get method will have [] in array format
        // if Course::get() it will get all the database table data
        $result = Course::select('name', 'thumbnail', 'lesson_num', 'price', 'id')->get();

        return response()->json([
            'code' => 200,
            'msg' => 'CourseList is loaded successfully',
            'data' => $result
        ], 200);
    }

    // 
    public function courseDetail(Request $request) {

        $id = $request->id; 
        try {
            $result = Course::where('id', '=', $id)->select(
                'id',
                'name', 
                'user_token',
                'description',
                'thumbnail', 
                'lesson_num', 
                'video_length',
                
                'price')->first();

                return response()->json(
                    [
                        'code' => 200,
                        'msg' => 'Course detail is here',
                        'data' => $result
                    ], 200
                );
        } catch(\Throwable $e) {
            return response()->json(
                [
                    'code' => 500,
                    'msg' => 'Internal Server error',
                    'data' => $e->getMessage()
                ], 500
            );
        }
    }
}
