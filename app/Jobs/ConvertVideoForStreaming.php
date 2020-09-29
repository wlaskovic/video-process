<?php

namespace App\Jobs;

use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg as FFMpeg;
use App\Video;
use Carbon\Carbon;
use FFMpeg\Coordinate\Dimension;
use FFMpeg\Format\Video\X264;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Config;


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
        // create a video format...
        $lowBitrateFormat = (new X264('libmp3lame', 'libx264'))->setKiloBitrate(500);

        $video_name = $this->getCleanFileName($this->video->path);
        $original_extension = pathinfo($this->video->path, PATHINFO_EXTENSION);
        $converted_file = $video_name . '.' . $original_extension;
        
        // open the uploaded video from the right disk...
        FFMpeg::fromDisk($this->video->disk)
        ->open($this->video->path)
        
        // call the 'export' method...
        ->export()
        
        // tell the MediaExporter to which disk and in which format we want to export...
        ->toDisk('videos')
        ->inFormat($lowBitrateFormat)
        
        // call the 'save' method with a filename...
        ->save($converted_file);
        
        //executing the conversion with shell_exec
        
        do {
            mkdir('storage/app/converted_videos/' . $video_name);
            if (file_exists('storage/app/public/' . $converted_file) && file_exists('storage/app/converted_videos/' . $video_name)) {
                foreach (Config::get('app.converted_formats') as $cf_key => $cf_val) {
                    $execute_command = 'ffmpeg -i storage/app/public/' . $video_name . '.' . $original_extension . ' -vcodec libx264 -acodec aac -crf 25 -level 3.0 -profile:v baseline -vf scale=-2:' . $cf_key . ' storage/app/converted_videos/' . $video_name . '/' . $video_name . '-' . $cf_key . '.' . $cf_val;
                    echo shell_exec($execute_command);
                }
            }
        } while(!file_exists('storage/app/public/' . $converted_file) && !file_exists('storage/app/converted_videos/' . $video_name));

        // update the database so we know the convertion is done!
        $this->video->update([
            'converted_for_streaming_at' => Carbon::now(),
            'processed' => true,
            'stream_path' => $converted_file
        ]);
    }

    private function getCleanFileName($filename){
        return preg_replace('/\\.[^.\\s]{3,4}$/', '', $filename);
    }
}
