<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\Lesson;

class LessonController extends Controller
{
    //
    public function lessonList(Request $request)
    {
        $id = $request->id;
        try {
            $result = Lesson::where('course_id', '=', $id)->select(
                'id',
                'name',
                'description',
                'thumbnail',
                'video')->get();

            return response()->json(
                [
                    'code' => 200,
                    'msg' => 'Lessons list is here',
                    'data' => $result
                ],
                200
            );
        } catch (\Throwable $e) {
            return response()->json(
                [
                    'code' => 500,
                    'msg' => 'Internal Server error',
                    'data' => $e->getMessage()
                ],
                500
            );
        }
    }
    //This is for our lesson list for a particular course
    public function lessonDetail(Request $request)
    {
        $id = $request->id;
        try {
            $result = Lesson::where('id', '=', $id)->select(
                'name',
                'description',
                'thumbnail',
                'video')->first();

            return response()->json(
                [
                    'code' => 200,
                    'msg' => 'Lesson detail is here',
                    'data' => $result->video
                ],
                200
            );
        } catch (\Throwable $e) {
            return response()->json(
                [
                    'code' => 500,
                    'msg' => 'Internal Server error',
                    'data' => $e->getMessage()
                ],
                500
            );
        }
    }
}
