<?php

namespace App\Jobs;

use App\Video;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;


class ConvertVideoForStreaming implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $video;

    /**
     * Create a new job instance.
     *
     * @param Video $video
     */
    public function __construct(Video $video)
    {
        $this->video = $video;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $video_name = $this->getCleanFileName($this->video->path);
        $original_extension = pathinfo($this->video->path, PATHINFO_EXTENSION);
        $converted_file = $video_name . '.' . $original_extension;
        
        // creating the directory for certain files

        Storage::makeDirectory('converted_videos/' . $video_name);

        try {
            if (Storage::disk('public')->exists($converted_file) && Storage::exists('converted_videos/' . $video_name)) {
                foreach (Config::get('app.converted_formats') as $cf_key => $cf_val) {
                    foreach ($cf_val as $dim_y) {
                        $input_file = 'ffmpeg -i storage/app/public/' . $video_name . '.' . $original_extension;
                        $output_file = 'storage/app/converted_videos/' . $video_name . '/' . $video_name . '-' . $dim_y . '.' . $cf_key;
    
                        // generate the corresponding conversion command

                        if ($cf_key == 'mp4') {
                            $execute_command = $input_file . ' -vcodec libx264 -acodec aac -crf 25 -level 3.0 -profile:v baseline -vf scale=-2:' . $dim_y . ' ' . $output_file;
                        }
                        else {
                            $execute_command = $input_file . ' -codec:v libvpx -quality good -cpu-used 0 -b:v 225k -qmin 10 -qmax 42 -maxrate 300k -bufsize 1000k -threads 2 -vf scale=-2:' . $dim_y . ' -codec:a libvorbis -b:a 128k -f webm ' . $output_file;
                        }

                        // executing the conversion with shell_exec

                        echo shell_exec($execute_command);
                    }
                }
            }
        } catch(\Exception $e) {
            report($e);
        }

        // update the database so we know the convertion is done

        $this->video->update([
            'converted_for_streaming_at' => Carbon::now(),
            'processed' => true,
            'stream_path' => $converted_file
        ]);
    }

    // cleaning the file name from not eligible chars, and extensions too, to be able to create dir under files name, and requested files (mp4, webm)

    private function getCleanFileName($filename){
        return preg_replace('/\\.[^.\\s]{3,4}$/', '', $filename);
    }
}
