<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Meeting;

class MeetingController extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'jwt.auth',
            ['except' => ['index', 'show']]
        );
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $meetings = Meeting::all();
        foreach($meetings as $meeting){
            $meeting->view_meeting = [
                'href' => 'api/v1/meeting/' . $meeting->id,
                'method' => 'GET'
            ];
        }

        $response = [
            'message' => 'List of all Meetings',
            'meetings' => $meetings
        ];

        return response()->json($response, 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request,[
           'title' => 'required',
           'description' => 'required',
           'time' => 'required',
           'user_id' => 'required'
        ]);

        $title = $request->input('title');
        $description = $request->input('description');
        $time = $request->input('time');
        $user_id = $request->input('user_id');

        $meeting = new Meeting([
            'time' => $time,
            'title' => $title,
            'description' => $description
        ]);

        if ($meeting->save()) {
            $meeting->users()->attach($user_id);
            $meeting->view_meeting = [
                'href' => 'api/v1/meeting/' . $meeting->id,
                'method' => 'GET'
            ];
            $message = [
                'message' => 'Meeting created',
                'meeting' => $meeting
            ];
            return response()->json($message, 201);
        }

        $response = [
            'message' => 'Error during creationg'
        ];

        return response()->json($response, 404);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $meeting = Meeting::with('users')->where('id', $id)->firstOrFail();
        $meeting->view_meetings = [
            'href' => 'api/v1/meeting',
            'method' => 'GET'
        ];

        $response = [
            'message' => 'Meeting information',
            'meeting' => $meeting
        ];
        return response()->json($response, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->validate($request,[
            'title' => 'required',
            'description' => 'required',
            'time' => 'required',
            'user_id' => 'required'
        ]);

        $title = $request->input('title');
        $description = $request->input('description');
        $time = $request->input('time');
        $user_id = $request->input('user_id');

        $meeting = Meeting::with('users')->findOrFail($id);

        if (!$meeting->users()->where('users.id', $user_id)->first()) {
            return response()->json(['message' => 'user not registered for meeting, update not successful'], 401);
        };

        $meeting-> time = $time;
        $meeting->title = $title;
        $meeting->description = $description;

        if(!$meeting->update()){
            return response()->json([
                'message' => 'Error during update'
            ], 404);
        }

        $meeting->view_meeting = [
            'href' => 'api/v1/meeting/' . $meeting->id,
            'method' => 'GET'
        ];

        $response = [
            'message' => 'Meeting Updated',
            'meeting' => $meeting
        ];

        return response()->json($response, 200);
     }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $meeting = Meeting::findOrFail($id);
        $users = $meeting->users;
        $meeting->users()->detach();

        if(!$meeting->delete()){
            foreach ($users as $user){
                $meeting->users()->attach($user);
            }
            return response()->json([
                'message' => 'Deletion Failed'
            ], 404);
        }

        $response = [
            'message' => 'Meeting deleted',
            'create' => [
                'href' => 'api/v1/meeting',
                'method' => 'POST',
                'params' => 'title, description, time'
            ]
        ];

        return response()->json($response, 200);
    }
}
