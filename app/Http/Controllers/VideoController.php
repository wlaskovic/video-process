<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVideoRequest;
use App\Jobs\ConvertVideoForStreaming;
use App\Video;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class VideoController extends Controller
{

    /**
     * Return video blade view and pass videos to it.
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        $videos = Video::orderBy('created_at', 'DESC')->get();
        return view('videos')->with('videos', $videos);
    }

    /**
     * Return uploader form view for uploading videos
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function uploader(){
        return view('uploader');
    }

    /**
     * Handles form submission after uploader form submits
     * @param StoreVideoRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreVideoRequest $request)
    {
        $video_id = str_random(11);
        $path = $video_id . '.' . $request->video->getClientOriginalExtension();
        $request->video->storeAs('public', $path);

        while (Video::where('video_id', '=', $video_id)->exists()) {
            $video_id = str_random(11);
        }
        
        $video = Video::create([
            'video_id'      => $video_id,
            'disk'          => 'public',
            'original_name' => $request->video->getClientOriginalName(),
            'path'          => $path,
            'title'         => $request->title,
        ]);

        return ConvertVideoForStreaming::dispatch($video) ? response()->json(['video_id' => $video_id], 200) : response()->json('Error');
    }

    public function retrieve($video_id, $quality = 720, $format = 'mp4') {
        $not_found_message = 'The searched video was not found or is still under process, please try again few seconds later!';
        if (!empty($video_id) && count(Video::where('video_id', '=', $video_id)->get())) {

            $check_video_queue = Video::where('video_id', '=', $video_id)->where('processed', '=', '0')->get();

            if (count($check_video_queue) > 0) {
                return 
                response()
                ->json(
                    [
                        'video_link' => url('storage/' . Config::get('app.default_video')),
                        'message' => $not_found_message,
                        'response' => 404
                    ], 
                    404,
                    [],
                    JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
            }

            $video_link = 'storage/' . $video_id . '/' . $video_id . '-' . $quality . '.' . $format;

            if (file_exists(public_path($video_link))) {
                return
                response()
                ->json(
                    [
                        'video_link' => url($video_link),
                        'response' => 200
                    ],
                    200,
                    [],
                    JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
            }
            else {
                return response()->json(['message' => $not_found_message], 404);
            }
        }
        else {
            return response()->json(['message' => $not_found_message], 404);
        }
    }

    public function destroy($video_id) {
        try {
            if (Storage::exists('converted_videos/' . $video_id)) {
                if (Video::where('video_id', $video_id)->delete() && Storage::deleteDirectory('converted_videos/' . $video_id))
                return response()->json(['message' => 'Successfully deleted!', 'response' => true], 200);
            }
            else {
                return response()->json(['message' => 'No such file or directory', 'response' => 404], 404);
            }
        } catch (\RunTimeException $e) {
            report($e);
        }
    }
}
