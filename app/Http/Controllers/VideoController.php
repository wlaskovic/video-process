<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVideoRequest;
use App\Jobs\ConvertVideoForStreaming;
use App\Video;
use Illuminate\Http\Request;

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
        if (isset($video_id) && !empty($video_id) && Video::where('video_id', '=', $video_id && 'processed', '=', '1')) {
            if (file_exists(public_path('storage/' . $video_id . '/' . $video_id . '-' . $quality . '.' . $format))) {
                $video_link = 'storage/' . $video_id . '/' . $video_id . '-' . $quality . '.' . $format;
                return 
                response()
                ->json(
                    ['video_link' => url($video_link)], 200, [], JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
            }
        }
        else {
            return response()->json(['not_found' => 'The searched video was not found or still under process!'], 404);
        }
    }
}
