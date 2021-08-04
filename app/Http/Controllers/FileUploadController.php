<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use File;
use App\FileUploadStore;
class FileUploadController extends Controller
{

    private function errorMessage($message){
        return response()->json([
            'success' => false,
            'message' => $message
        ]);
    }
    private function random_number($length = 10){
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
    public function upload(Request $request){
        if(!$request->hasFile('file')){
            return $this->errorMessage('Cannot Find File in Request');
        }
        $request->validate([
            'type'=>'required',
            'file'=>'required',
        ]);

        
        $user_id = $request->header('user_id');
        $school_id = $request->header('school_id');
        $token = $request->header('token');
        $year_id = $request->header('year_id');
        if(!$user_id || !$token || !$year_id || $token != env("SECRET_TOKEN")){
            return $this->errorMessage('Invalid Request');
        }
        $file = $request->file;
        $type = $request->type;
        $extension = $file->getClientOriginalExtension();
        $original_name = preg_replace('/\s+/', '', $file->getClientOriginalName());   
        $size = $file->getSize();   
        $mime_type = $file->getMimeType();   

        $application_path = "/storage/media/$school_id/$year_id/$type/".date('d-m-Y');
        $path = public_path($application_path);
        if(!File::isDirectory($path)){
            File::makeDirectory($path, 0644, true, true);
        }
        $final_filename = $this->random_number(10)."_$original_name";
        $final_url = $application_path.'/'.$final_filename;
        $file->move($path, $final_filename);

        FileUploadStore::create([
            'user_id'=>$user_id,
            'school_id'=>$school_id,
            'year_id'=>$year_id,
            'type'=>$type,
            'size'=>$size,
            'mime_type'=>$mime_type,
            'path'=>$final_url,
            'original_name'=>$original_name,
            'final_name'=>$final_filename,
            'total_download'=>1
        ]);
        return response()->json([
            'success'=> true,
            'link'=> url($final_url)
        ]);
    }
}
