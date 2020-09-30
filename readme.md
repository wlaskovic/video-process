<p align="center"><img src="https://laravel.com/assets/img/components/logo-laravel.svg"></p>


### Short Description of Project
```
This project was made for uploading certain videos
and converting them into .mp4 and .webm in two formats 720p, 360p.
The base of this API is forked from waleedahmad laravel-stream project, using FFMpeg library.
https://ffmpeg.org/download.html

The project, API has 3 main parts:
    - upload video and convert it to given formats and extensions
    - retrieve - which gives back the link from the video
        http://127.0.0.1:8000/video/Bv3RwP1Tqir - without parameters - the default is 720p and .mp4
        {
            "video_link": "http://127.0.0.1:8000/storage/Bv3RwP1Tqir/Bv3RwP1Tqir-720.mp4",
            "response": 200
        }

        http://127.0.0.1:8000/video/Bv3RwP1Tqir/360/webm - gives the following
        {
            "video_link": "http://127.0.0.1:8000/storage/Bv3RwP1Tqir/Bv3RwP1Tqir-360.webm",
            "response": 200
        }

        Otherwise, if the link is broken or doesn't meet the requirements then throws 
        back a corresponding message.
    - delete - which deletes the given video folder and database record, based on video_id parameter
```

### Setup Instructions
```
$ git clone https://github.com/wlaskovic/video-process.git
$ composer install
$ php artisan preset bootstrap
$ npm install && npm run dev

# update database credentials, queue connection driver and FFmpeg binaries

DB_DATABASE=laravel
DB_USERNAME=username
DB_PASSWORD=password

QUEUE_CONNECTION=database

FFMPEG_BINARIES=''
FFPROBE_BINARIES=''
```
### Additional Instructions for Setup
```
# if you are gonna use the app on local machine probably you gonna have to add the 
ffmpeg and ffprobe exe file to the PATH
# in this case I put it into /app directory and refer to it

# I'm using Laravel version 7.3.12

# if npm run dev throws error then you should try this command first: npm install --save-dev cross-env
# after it will generate the corresponding .js and .css files
```
#### Running queue worker
```
Run this command every time you want to upload a video, it will start a queue for making a job

$ php artisan queue:work --tries=3 --timeout=8600
```