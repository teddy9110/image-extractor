<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
                $storedPath = $zipFile->store('uploads'); // Store the file in the 'uploads' directory
                // need to get the file and then send it to extractGPSData with the file path, its finikey af though 
                $zip = new ZipArchive;
                $zip->open(storage_path('app/' . $storedPath));
                $extractedPath = storage_path('app/uploads/' . uniqid());
                $zip->extractTo($extractedPath);
                $zip->close();
                
               
               
            } else {
                return redirect('/upload')->with('error', 'Please upload a zip file.');
            }
        } else {
            return redirect('/upload')->with('error', 'No file uploaded.');
        }
    }

    private function extractGPSData($filePath)
    {
        $exif = exif_read_data($filePath, 'EXIF');

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
            return null; // GPS data not found or invalid
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
