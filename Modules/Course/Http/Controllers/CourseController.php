<?php

namespace Modules\Course\Http\Controllers;

namespace Modules\Course\Http\Controllers;
use App\Http\Resources\DataResource;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Modules\Course\Entities\Course;
use Modules\Course\Http\Requests\CourseRequest;
use Spatie\QueryBuilder\QueryBuilder;


class CourseController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        $courses = QueryBuilder::for(Course::class)
            ->where('is_deleted', false)
            ->with(['activity' => function($query){
                $query->with(['images' => function ($query) {
                    $query->select('imageable_id')
                        ->selectRaw('COALESCE(
                MAX(CASE WHEN collection = "thumbnail_image" THEN  link END),
                MAX(CASE WHEN collection = "main_image" THEN link END)
            ) AS link')
                        ->groupBy('imageable_id');
                }]);
            }])
            ->paginate(10)
            ->onEachSide(1);

        foreach ($courses as $course) {
            $frequency = $course->frequency;
            $sessions = $course->sessions;
            if ($frequency === 'Daily') {
                $durationString = $sessions.($sessions == 1 ?' day' :' days');
                $course->duration = $durationString;
            } elseif ($frequency === 'Weekly') {
                $durationString = $sessions.($sessions == 1 ?' week' :' weeks');
                $course->duration = $durationString;
            } elseif ($frequency === 'Monthly') {
                $durationString = $sessions.($sessions == 1 ?' month' :' months');
                $course->duration = $durationString;
            }
//            $startDateCarbon = Carbon::parse($course->start_date);
//            $endDateCarbon = Carbon::parse($course->end_date);
//
//            $duration = $endDateCarbon->diff($startDateCarbon);
//            $years = $duration->y;
//            $months = $duration->m;
//            $weeks = floor($duration->d / 7);
//            $days = $duration->d % 7;
//
//            if ($years == 1) {
//                $durationString = $years . ' year';
//            } elseif ($years > 0) {
//                $durationString = $years . ' years';
//            } elseif ($months==1) {
//                $durationString = $months . ' month';
//            } elseif ($months > 0 && $months < 12) {
//                $durationString = $months . ' months';
//            } elseif ($weeks==1) {
//                $durationString = $weeks . ' week';
//            } elseif ($weeks > 0) {
//                $durationString = $weeks . ' weeks';
//            } elseif ($days==1) {
//                $durationString = $days . ' day';
//            } elseif ($days==0) {
//                $durationString = 'unscheduled';
//            } else {
//                $durationString = $days . ' days';
//            }
//            $course->duration = $durationString;
        }
        return DataResource::collection($courses);
    }


    /**
     * Store a newly created resource in storage.
     * @param CourseRequest $request
     * @return DataResource|JsonResponse
     */
    public function store(CourseRequest $request): JsonResponse|DataResource
    {
        try {
            $course = Course::create($request->validated());
            return new DataResource($course);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Show the specified resource.
     * @param $course_id
     * @return DataResource|JsonResponse
     */
    public function show($course_id): JsonResponse|DataResource
    {
        try {
            $course = QueryBuilder::for(Course::class)
                ->where('id', $course_id)
                ->with('activity')
                ->firstOrFail();
            return new DataResource($course);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param $course_id
     * @return JsonResponse
     */
    public function destroy($course_id): JsonResponse
    {
        try {
            $course = Course::findOrFail($course_id);
            $course->update(['is_deleted' => true]);
            return response()->json(['message' => 'Course remove successfully!'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     * @param CourseRequest $request
     * @param $course_id
     * @return DataResource|JsonResponse
     */
    public function update(CourseRequest $request, $course_id): JsonResponse|DataResource
    {
        try {
            $course = Course::findOrFail($course_id);
            $course->update($request->validated());
            return new DataResource($course);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
