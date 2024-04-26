<?php

namespace Modules\Booking\Http\Controllers;

use App\Http\Resources\DataResource;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Booking\Entities\Booking;
use Modules\Booking\Entities\BookingParticipant;
use Modules\Booking\Http\Requests\ParticipantRequest;

class ParticipantController extends Controller
{
    /**
     * Display a listing of the resource.
     * @param $booking_id
     * @return JsonResponse|DataResource
     */
    public function index($booking_id): JsonResponse|DataResource
    {
        try {
            $booking = Booking::find($booking_id);
            if ($booking){
                $participant = $booking->booking_participants()->paginate(5);
                return new DataResource($participant);
            }else{
                return response()->json([
                    'status' => false,
                    'message' => 'Booking not found!',
                ], 404);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('booking::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param ParticipantRequest $request
     * @param $booking
     * @return DataResource|JsonResponse
     */
    public function store(ParticipantRequest $request, $booking): JsonResponse|DataResource
    {
        try {
            $booking_data = Booking::find($booking);
            if ($booking_data){
                $participant = $booking_data->booking_participants()->create($request->validated());
                return new DataResource($participant);
            }else{
                return response()->json([
                    'status' => false,
                    'message' => 'Booking not found!',
                ], 404);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Show the specified resource.
     * @param $participant
     * @return JsonResponse
     */
    public function show($participant): JsonResponse
    {
        try {
            $participant_data = BookingParticipant::find($participant);
            if ($participant_data) {
                return response()->json([
                    'status' => true,
                    'message' => $participant_data,
                ], 200);
            }else {
                return response()->json([
                    'status' => false,
                    'message' => 'Data not found'
                ], 204);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('booking::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param ParticipantRequest $request
     * @param $booking
     * @param $participant
     * @return JsonResponse
     */
    public function update(ParticipantRequest $request, $booking, $participant): JsonResponse
    {
        try {
            $booking = Booking::find($booking);

            if ($booking) {
                $participant = BookingParticipant::find($participant);

                if ($participant) {
                    // Assuming your BookingParticipant model has the $fillable property defined.
                    $participant->update($request->all());

                    return response()->json([
                        'status' => true,
                        'message' => 'Booking participant data updated!',
                    ], 200);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Participant not found!',
                    ], 404);
                }
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Booking not found!',
                ], 404);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param $booking
     * @param $participant
     * @return JsonResponse
     */
    public function destroy($booking, $participant): JsonResponse
    {
        try {
            $booking_data = Booking::find($booking);
            if ($booking_data){
                $participant = $booking_data->booking_participants()->find($participant);
                if ($participant){
                    $participant->update(['is_deleted'=>true]);
                    return response()->json([
                        'status' => false,
                        'message' => 'Booking participant removed!',
                    ], 200);
                }else{
                    return response()->json([
                        'status' => false,
                        'message' => 'Participant not found!',
                    ], 404);
                }
            }else{
                return response()->json([
                    'status' => false,
                    'message' => 'Booking not found!',
                ], 404);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
}
