<?php
/**
 * Created by IntelliJ IDEA.
 * User: ppound
 * Date: 2014-10-06
 * Time: 2:48 PM
 */

require_once 'Derivatives.php';

class Video extends Derivative {

  function __destruct() {
    parent::__destruct();
  }

  /**
   * Create a video derivative.
   *
   * @param string $outputdsid
   *   The output dsid
   * @param string $label
   *   the datastream label
   * @param array $params
   *   an array containing parameters currently type is required an must = mp4
   *   or mkv.  ie $params['type'] = 'mp4' but defined in the workflow
   *
   * @return int|string
   */
  function createVideoDerivative($outputdsid, $label, $params) {
    $return = MS_SUCCESS;
    $mp4_output = array();
    if (empty($type)) {
      $this->log->lwrite("Failed to create video derivative no type provided", 'PROCESS_DATASTREAM', $this->pid, $this->incoming_dsid, 'ERROR');
      return MS_FEDORA_EXCEPTION;
    }
    $type = escapeshellarg($params['type']);
    $out_file = $this->temp_file . "-video.$type";
    $command = "ffmpeg -i $this->temp_file $out_file";
    if ($type = 'mp4') {
      $command = "ffmpeg -i $this->temp_file -f mp4 -vcodec libx264 -preset medium -acodec libfaac -ab 128k -ac 2 -async 1 -movflags faststart $out_file";
    }
    try {
      exec($command, $mp4_output, $return);
      $log_message = "$dsid derivative created using ffmpg - $command || SUCCESS";
      $this->add_derivative($outputdsid, $label, $out_file, 'video/mp4', $log_message);
      $this->log->lwrite("Updated $outputdsid datastream", 'PROCESS_DATASTREAM', $this->pid, $this->incoming_dsid, 'SUCCESS');
    }
    catch (Exception $e) {
      $return = MS_FEDORA_EXCEPTION;
      $this->log->lwrite("Failed to create video derivative" . $e->getMessage(), 'PROCESS_DATASTREAM', $this->pid, $this->incoming_dsid, 'ERROR');
    }
    return $return;
  }

  /**
   * Create a thumbnail from a video.
   *
   * @param string $outputdsid
   *   The output dsid
   * @param string $label
   *   the datastream label
   * @param array $params
   *   an array containing optional parameters
   *
   * @return int|string
   *   0 = success
   */
  function createThumbnailFromVideo($outputdsid, $label, $params) {
    $return = MS_SUCCESS;
    $out_file = $this->temp_file . '-TN.jpg';
    $vid_length_command = "ffmpeg -i $this->temp_file 2>&1";
    exec($vid_length_command, $time_output, $ret_value);
    $dur_match = FALSE;
    $duration = '';
    foreach ($time_output as $key => $value) {
      preg_match('/Duration: (.*), start/', $value, $time_match);
      if (count($time_match)) {
        $dur_match = TRUE;
        $duration = $time_match[1];
        break;
      }
    }
    if ($dur_match) {
      // Snip off the ms because we don't care about them.
      $time_val = preg_replace('/\.(.*)/', '', $duration);
      $time_array = explode(':', $time_val);
      $output_time = floor((($time_array[0] * 360) + ($time_array[1] * 60) + $time_array[2]) / 2);

      $tn_creation_command = "ffmpeg -itsoffset -2 -ss $output_time -i $this->temp_file -vcodec mjpeg -vframes 1 -an -f rawvideo $out_file";

      $return_value = FALSE;
      exec($tn_creation_command, $output, $return_value);
      if ($return_value === 0) {
        try {
          $log_message = "$dsid derivative created using ffmpg - $tn_creation_command || SUCCESS";
          $this->add_derivative($outputdsid, $label, $out_file, 'image/jpeg', $log_message);
          $this->log->lwrite("Updated $outputdsid datastream", 'PROCESS_DATASTREAM', $this->pid, $this->incoming_dsid, 'SUCCESS');
        }
        catch (Exception $e){
          $return = MS_FEDORA_EXCEPTION;
          $this->log->lwrite("Failed to add video derivative" . $e->getMessage(), 'PROCESS_DATASTREAM', $this->pid, $this->incoming_dsid, 'ERROR');
        }
      }
      // Unable to generate with ffmpeg, add default TN.
      else {
        $return = $this->addDefaultThumbnail($outputdsid, $label);
      }
    }
    // Unable to grab duration at the default thunbnail.
    else {
      $return = $this->addDefaultThumbnail($outputdsid, $label);
    }
    return $return;
  }

  /**
   * Create a thumbnail from a jpg image.
   *
   * @param string $outputdsid
   *   The output dsid
   * @param string $label
   *   the datastream label
   *
   * @return int|string
   *   0 = success
   */
  function addDefaultThumbnail($outputdsid, $label) {
    $this->log->lwrite("Could not create thumbnail derivative from video file, using default video thumbnail", 'PROCESS_DATASTREAM', $this->pid, $this->incoming_dsid, 'WARNING');
    $return = MS_SUCCESS;
    try {
      $out_file = '../images/crystal_clear_app_camera.png';
      $log_message = "$dsid using default video thumbnail || SUCCESS";
      $this->add_derivative($outputdsid, $label, $out_file, 'image/jpeg', $log_message);
      $this->log->lwrite("Updated $outputdsid datastream using default video thumbnail", 'PROCESS_DATASTREAM', $this->pid, $this->incoming_dsid, 'SUCCESS');
    }
    catch (Exception $e) {
      $return = MS_FEDORA_EXCEPTION;
      $this->log->lwrite("Failed to add defaulte video derivative" . $e->getMessage(), 'PROCESS_DATASTREAM', $this->pid, $this->incoming_dsid, 'ERROR');
    }
    return $return;
  }
}



