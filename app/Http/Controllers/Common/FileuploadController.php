<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;

class FileuploadController extends Controller
{
    public function __construct()
    {
        // checking authentication
        $this->middleware('auth');
        // checking if role is agent
        $this->middleware('role.agent');
    }

    // Returns a file size limit in bytes based on the PHP upload_max_filesize
    // and post_max_size
    public function file_upload_max_size()
    {
        static $result = null;

        if ($result === null) {
            // Start with post_max_size.
            $post_max_size = ini_get('post_max_size');
            $upload_max_size = ini_get('upload_max_filesize');
            
            $max_size_in_bytes = $this->parse_size($post_max_size ?: '8M');
            $max_size_in_actual = $post_max_size ?: '8M';

            // If upload_max_size is less, then reduce. Except if upload_max_size is
            // zero, which indicates no limit.
            if ($upload_max_size) {
                $upload_max = $this->parse_size($upload_max_size);
                if ($upload_max > 0 && $upload_max < $max_size_in_bytes) {
                    $max_size_in_bytes = $upload_max;
                    $max_size_in_actual = $upload_max_size;
                }
            }
            
            // Ensure we always return a valid array with 2 elements
            $result = [
                0 => $max_size_in_bytes,
                1 => $max_size_in_actual
            ];
        }

        // Double check that result is valid before returning
        if (!is_array($result) || count($result) < 2 || !isset($result[0]) || !isset($result[1])) {
            // Return safe defaults if something went wrong
            return [2097152, '2M']; // 2MB default
        }

        return $result;
//        return $max_size_in_bytes;
    }

    public function parse_size($size)
    {
        $unit = preg_replace('/[^bkmgtpezy]/i', '', $size); // Remove the non-unit characters from the size.
        $size = preg_replace('/[^0-9\.]/', '', $size); // Remove the non-numeric characters from the size.
        if ($unit) {
            // Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
            return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
        } else {
            return round($size);
        }
    }
}
