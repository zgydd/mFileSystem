<?php

define('KC_FFMPEG_PATH', '/usr/local/bin/ffmpeg -i "%s" 2>&1');
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of behavior
 *
 * @author bartonjoe
 */
class behavior {

    private $size = 'N';
    private $customSize = array();

    public function __construct($size, $customSize = []) {
        $this->size = $size;
        switch ($size) {
            case 'L':
                array_push($this->customSize, 120, 120);
                break;
            case 'M':
                array_push($this->customSize, 60, 60);
                break;
            case 'S':
                array_push($this->customSize, 40, 40);
                break;
            case 'C':
                $this->customSize = $customSize;
                break;
            default: break;
        }
    }

    public function queryFile($fileDir, $fileName) {
        $srcImg = __FILEROOT__ . __OPENDIR__ . $fileDir . $fileName;
        $fileDir = $fileDir . 'thumb' . $this->size;
        if ($this->size === 'C' && count($this->customSize) === 2) {
            $fileDir .= '_' . $this->customSize[0] . '_' . $this->customSize[1];
        }
        $fileDir .= '/';
        $targetDir = __FILEROOT__ . __OPENDIR__ . $fileDir;
        if (!file_exists($targetDir)) {
            mkdir($targetDir);
        }
        if (!file_exists($targetDir . $fileName)) {
            $this->resizeImage($srcImg, $this->customSize[0], $this->customSize[1], substr($fileName, 0, strripos($fileName, '.')), '.png', $targetDir);
        }
        return $fileDir;
    }

    public function getVideoCover($file, $time, $name) {
        if (empty($time)) {
            $time = '1';
        }

        $info = $this->video_info($file);
        $resolution = '320x320';
        if (!is_null($info['resolution'])) {
            if (is_numeric($info['rotate']) && (abs($info['rotate']) % 90 === 0)) {
                $arr = explode('x', $info['resolution']);
                $resolution = $arr[1] . 'x' . $arr[0];
            } else {
                $resolution = $info['resolution'];
            }
        }
        try {
            //$strlen = strlen($file);
            //exec("ffmpeg -i ".$file." -y -f mjpeg -ss ".$time." -t 0.001 -s 320x240 ".$name."",$out,$status);
            $str = "ffmpeg -i " . $file . " -y -f mjpeg -ss 3 -t " . $time . " -s " . $resolution . " " . $name;
            //echo $str."</br>";
            $result = system($str);
            return $result;
        } catch (Exception $e) {
            return $e;
        }
    }

    private function resizeImage($src_img, $width, $height, $name, $filetype, $thumb, $cut = 0) {
        $proportion = 0;
        if (!file_exists($thumb)) {
            mkdir($thumb);
        }
        $dst_img = $thumb . $name . $filetype;
        $ot = pathinfo($dst_img, PATHINFO_EXTENSION);
        $otfunc = 'image' . ($ot == 'jpg' ? 'jpeg' : $ot);
        $srcinfo = getimagesize($src_img);
        $src_w = $srcinfo[0];
        $src_h = $srcinfo[1];
        $type = strtolower(substr(image_type_to_extension($srcinfo[2]), 1));
        $createfun = 'imagecreatefrom' . ($type == 'jpg' ? 'jpeg' : $type);

        $dst_h = $height;
        $dst_w = $width;
        $x = $y = 0;
        if (($width > $src_w && $height > $src_h) || ($height > $src_h && $width == 0) || ($width > $src_w && $height == 0)) {
            $proportion = 1;
        }
        if ($width > $src_w) {
            $dst_w = $width = $src_w;
        }

        if ($height > $src_h) {
            $dst_h = $height = $src_h;
        }

        if (!$width && !$height && !$proportion) {
            return false;
        }
        if (!$proportion) {
            if ($cut == 0) {
                if ($dst_w && $dst_h) {
                    if ($dst_w / $src_w < $dst_h / $src_h) {
                        $dst_w = $src_w * ($dst_h / $src_h);
                        $x = 0 - ($dst_w - $width) / 2;
                    } else {
                        $dst_h = $src_h * ($dst_w / $src_w);
                        $y = 0 - ($dst_h - $height) / 2;
                    }
                } else {
                    if ($dst_w xor $dst_h) {
                        if ($dst_w && !$dst_h) {
                            $propor = $dst_w / $src_w;
                            $height = $dst_h = $src_h * $propor;
                        } else {
                            if (!$dst_w && $dst_h) {
                                $propor = $dst_h / $src_h;
                                $width = $dst_w = $src_w * $propor;
                            }
                        }
                    }
                }
            } else {
                if (!$dst_h) {
                    $height = $dst_h = $dst_w;
                }
                if (!$dst_w) {
                    $width = $dst_w = $dst_h;
                }
                $propor = min(max($dst_w / $src_w, $dst_h / $src_h), 1);
                $dst_w = (int) round($src_w * $propor);
                $dst_h = (int) round($src_h * $propor);
                $x = ($width - $dst_w) / 2;
                $y = ($height - $dst_h) / 2;
            }
        } else {
            $proportion = min($proportion, 1);
            $height = $dst_h = $src_h * $proportion;
            $width = $dst_w = $src_w * $proportion;
        }
        $src = $createfun($src_img);
        $dst = imagecreatetruecolor($width ? $width : $dst_w, $height ? $height : $dst_h);
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefill($dst, 0, 0, $white);
        if (function_exists('imagecopyresampled')) {
            imagecopyresampled($dst, $src, $x, $y, 0, 0, $dst_w, $dst_h, $src_w, $src_h);
        } else {
            imagecopyresized($dst, $src, $x, $y, 0, 0, $dst_w, $dst_h, $src_w, $src_h);
        }
        $otfunc($dst, $dst_img);
        imagedestroy($dst);
        imagedestroy($src);
    }

    private function video_info($file) {
        ob_start();
        passthru(sprintf(KC_FFMPEG_PATH, $file));
        $info = ob_get_contents();
        ob_end_clean();
        $vid = substr(substr(substr($info, strpos($info, 'Video: ')), 7), 0, strpos(substr(substr($info, strpos($info, 'Video: ')), 7), ':'));
        $arr = explode(',', $vid);
        $result = null;
        foreach ($arr as $value) {
            if (strpos($value, 'x') > 0) {
                $arrTmp = explode('x', trim($value));
                if (count($arrTmp) === 2 && is_numeric($arrTmp[0]) && is_numeric($arrTmp[1])) {
                    $result = trim($value);
                    break;
                }
            }
        }
        $rotate = null;
        $arrrt = explode(' ', substr(substr($info, strpos($info, 'rotate')), strpos(substr($info, strpos($info, 'rotate')), ':')));
        if (count($arrrt) > 1 && is_numeric(trim($arrrt[1]))) {
            $rotate = intval(trim($arrrt[1]));
        }
        $arrResult = ['resolution' => $result, 'rotate' => $rotate];

        return $arrResult;


        /*
          $ret = array();
          if (preg_match("/Duration: (.*?), start: (.*?), bitrate: (\d*) kb\/s/", $info, $match)) {
          $ret['duration'] = $match[1];
          $da = explode(':', $match[1]);
          $ret['seconds'] = $da[0] * 3600 + $da[1] * 60 + $da[2];
          $ret['start'] = $match[2];
          $ret['bitrate'] = $match[3];
          }
          if (preg_match("/Video: (.*?), (.*?), (.*?)[,\s]/", $info, $match)) {
          $ret['vcodec'] = $match[1];
          $ret['vformat'] = $match[2];
          $ret['resolution'] = $match[3];
          $a = explode('x', $match[3]);
          $ret['width'] = $a[0];
          $ret['height'] = $a[1];
          }

          if (preg_match("/Audio: (\w*), (\d*) Hz/", $info, $match)) {
          $ret['acodec'] = $match[1];
          $ret['asamplerate'] = $match[2];
          }

          if (isset($ret['seconds']) && isset($ret['start'])) {
          $ret['play_time'] = $ret['seconds'] + $ret['start'];
          }

          $ret['size'] = filesize($file);
          return $ret;
         * 
         */
    }

}
