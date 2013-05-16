<?php

require_once 'Derivatives.php';

class Image extends Derivative {
 
  function __destruct() {
    parent::__destruct();
  }
  function JP2($dsid = 'JP2', $label = 'Compressed jp2') {
    $this->log->lwrite('Starting processing', 'PROCESS_DATASTREAM', $this->pid, $dsid);
    if (file_exists($this->temp_file)){
    try {
      $output_file = $this->temp_file . '_JP2.jp2';
      $command = 'kdu_compress -i ' . $this->temp_file . ' -o ' . $output_file ;      
      $jp2_output = array();
      $output = exec($command, $jp2_output, $return);
      $log_message = "$dsid derivative created using kdu_compress with command - $command || SUCCESS";
      $this->add_derivative($dsid, $label, $output_file, 'image/jp2', $log_message);
    } catch (Exception $e) {
      $this->log->lwrite("Could not create the $dsid derivative! ". $return . ' '.implode($jp2_output), 'FAIL_DATASTREAM', $this->pid, $dsid, NULL, 'ERROR');
      unlink($output_file);
    }
    } else {
      $this->log->lwrite("Could not create the $dsid derivative! could not find file $this->temp_file ". $return . ' '.implode($jp2_output), 'FAIL_DATASTREAM', $this->pid, $dsid, NULL, 'ERROR');
    }
    return $return;
  }

  function TN($dsid = 'TN', $label = 'Thumbnail', $height = '200', $width = '200') {
    $this->log->lwrite('Starting processing', 'PROCESS_DATASTREAM', $this->pid, $dsid);
    try {
      $output_file = $this->temp_file . '_TN.jpg';
      $command = "convert -thumbnail " . $height . "x" . $width . " $this->temp_file $output_file &> /var/log/phpfunctions/cmd1.log";
      exec($command, $tn_output, $return);
      $log_message = "$dsid derivative created using ImageMagick with command - $command || SUCCESS";
      $this->add_derivative($dsid, $label, $output_file, 'image/jpeg', $log_message);
    } catch (Exception $e) {
      $this->log->lwrite("Could not create the $dsid derivative!", 'FAIL_DATASTREAM', $this->pid, $dsid, NULL, 'ERROR');
      unlink($output_file);
    }
    return $return;
  }

  function TN_department($dsid = 'TN', $label = 'Thumbnail', $height = '200', $width = '200') {
    $this->log->lwrite('Starting processing', 'PROCESS_DATASTREAM', $this->pid, $dsid);
    try {
      $tn_filename = 'department_tn.png';
      if (!file_exists($tn_filename)) {
        $this->log->lwrite("Could not find thumbnail image!", 'FAIL_DATASTREAM', $this->pid, $dsid, NULL, 'ERROR');
        return FALSE;
      }
      $log_message = "$dsid derivative uploaded from file system || SUCCESS";
      $this->add_derivative($dsid, $label, $tn_filename, 'image/png', $log_message);
    } catch (Exception $e) {
      $this->log->lwrite("Could not create the $dsid derivative!", 'FAIL_DATASTREAM', $this->pid, $dsid, NULL, 'ERROR');
    }
    return TRUE;
  }

  function TN_faculty($dsid = 'TN', $label = 'Thumbnail', $height = '200', $width = '200') {
    $this->log->lwrite('Starting processing', 'PROCESS_DATASTREAM', $this->pid, $dsid);
    try {
      $tn_filename = 'faculty_tn.png';
      if (!file_exists($tn_filename)) {
        $this->log->lwrite("Could not find thumbnail image!", 'ERROR');
        return FALSE;
      }
      $log_message = "$dsid derivative uploaded from file system || SUCCESS";
      $this->add_derivative($dsid, $label, $tn_filename, 'image/png',$log_message);
    } catch (Exception $e) {
      $this->log->lwrite("Could not create the $dsid derivative!", 'FAIL_DATASTREAM', $this->pid, $dsid, NULL, 'ERROR');
    }
    return TRUE;
  }

  function JPG($dsid = 'JPEG', $label = 'JPEG image', $resize = '800') {
    $this->log->lwrite('Starting processing', 'PROCESS_DATASTREAM', $this->pid, $dsid);
    try {
		$pathinfo = pathinfo($this->temp_file);
      $output_file = $pathinfo['dirname'] . DIRECTORY_SEPARATOR . $pathinfo['filename'] . '_JPG.jpg';
      $command = "convert $this->temp_file -resize $resize $output_file &> /var/log/phpfunctions/cmd1.log";
      exec($command, $jpg_output, $return);
      $log_message = "$dsid derivative created using ImageMagick with command - $command || SUCCESS";
      $this->add_derivative($dsid, $label, $output_file, 'image/jpeg', $log_message);
    } catch (Exception $e) {
      $this->log->lwrite("Could not create the $dsid derivative!", 'FAIL_DATASTREAM', $this->pid, $dsid, NULL, 'ERROR');
      unlink($output_file);
    }
    return $return;
  }

}

?>
