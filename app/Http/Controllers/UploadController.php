<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Photo;
use Illuminate\Support\Facades\File;
use ZipArchive;

class UploadController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'zip_file' => 'required|file', // Ensure it's a file and less than 2MB
        ]);

        if ($request->hasFile('zip_file')) {
            $zipFile = $request->file('zip_file');

            if ($zipFile->getClientOriginalExtension() === 'zip') {
                // If it's a zip file
                $id =  uniqid();
                $storedPath = $zipFile->store('uploads'); // Store the file in the 'uploads' directory
                // need to get the file and then send it to extractGPSData with the file path, its finikey af though 
                $zip = new ZipArchive;
                $zip->open(storage_path('app/' . $storedPath));
                $extractedPath = storage_path('app/uploads/' .$id);
                $zip->extractTo($extractedPath);
                $zip->close();
                //delete the zip file 
                // Loop through the extracted files
                
                $files = scandir($extractedPath); // assuming $extractedPath is the path where your files are located
                // clip the first 2 from the array

                $files = array_slice($files, 2);
                
                foreach ($files as $file) {
                    
                    if (is_file($extractedPath . '/' . $file)) {
                       $gps = $this->extractGPSData($extractedPath . '/' . $file);

                          if ($gps) {
                            $photo = new Photo();
                            $photo->file_name = $file;
                            $photo->file_path = $extractedPath . '/' . $file;
                            $photo->latitude = $gps['latitude'];
                            $photo->longitude = $gps['longitude'];
                            $photo->save();
                          }
                    }
                }

                $files = glob(storage_path('app/uploads/*')); // get all file names
                foreach($files as $file){ // iterate files
                    if(is_file($file)) {
                        unlink($file); // delete file
                    }
                    
                }
        
                $uploadDir = storage_path('app/uploads');
                File::deleteDirectory($uploadDir); // delete the directory and its contents
                File::makeDirectory($uploadDir); // create a new directory


                return redirect('/upload')->with('success', 'Files uploaded successfully.');
            } else {
                return redirect('/upload')->with('error', 'Please upload a zip file.');
            }
        } else {
            return redirect('/upload')->with('error', 'No file uploaded.');
        }
    }

    private function extractGPSData($filePath)
    {
        $exif = exif_read_data($filePath,'EXIF');
        if ($exif && isset($exif['GPS'])) {
            $latitudeRef = $exif['GPS']['GPSLatitudeRef'];
            $latitude = $this->gpsToDecimal($exif['GPS']['GPSLatitude'], $latitudeRef);
            
            $longitudeRef = $exif['GPS']['GPSLongitudeRef'];
            $longitude = $this->gpsToDecimal($exif['GPS']['GPSLongitude'], $longitudeRef);

            return [
                'latitude' => $latitude,
                'longitude' => $longitude,
            ];
        } else {
            return [
                'latitude' => '',
                'longitude' => '',
            ];
        }
    }

    private function gpsToDecimal($gps, $ref)
    {
        $degrees = count($gps) > 0 ? $this->gpsFractionToDecimal($gps[0]) : 0;
        $minutes = count($gps) > 1 ? $this->gpsFractionToDecimal($gps[1]) : 0;
        $seconds = count($gps) > 2 ? $this->gpsFractionToDecimal($gps[2]) : 0;

        $decimal = $degrees + ($minutes / 60) + ($seconds / 3600);

        if ($ref == 'S' || $ref == 'W') {
            $decimal *= -1;
        }

        return $decimal;
    }

    private function gpsFractionToDecimal($fraction)
    {
        $parts = explode('/', $fraction);
        if (count($parts) == 2) {
            return $parts[0] / $parts[1];
        } else {
            return 0;
        }
    }
}
