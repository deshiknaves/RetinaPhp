<?php 
/**
 * @version 0.1 beta
 * @file
 * @author  Deshiknaves
 * Retina.php will take an image and resize the image to half it's size,
 * rename the file, and save a new version for non retina displays.
 *
 * This class was built with Retina.js in mind for handling the display
 * of these images on the actual site. http://retinajs.com/
 */

// Default path for the RetinaPhp log. Define the constant to add custom
// location
defined('RETINA_PHP_LOG') ? RETINA_PHP_LOG : define('RETINA_PHP_LOG', getcwd() . '/retinaphp_errors.log');

class RetinaPhp {

	/**
	 * Takes an image path and resizes the image to half it's size
	 * Also renames the
	 * @param  [string] $path [path of the file to resize]
	 * @param  [boolean] $log [logs an error in the log file if true]
	 * @return [TRUE if successful else returns an error array]
	 */
	public function resizeImage($path = NULL, $log = TRUE) {

		// Check that the file exists
		if (!file_exists($path)) {
			if ($log)	error_log(date("F j, Y, g:i a") . "     {$path} does not exist.\n", 3, RETINA_PHP_LOG);
			return array( 'error' => "{$path} does not exist.", 'error_code' => 1);
		}

		// Check that the file is writable
		if (!is_writable($path)) {
			if ($log)	error_log(date("F j, Y, g:i a") . "     {$path} isn't writable.\n", 3, RETINA_PHP_LOG);
			return array( 'error' => "{$path} isn't writable.", 'error_code' => 2);
		}

		// Check that the file is an image
		$imageSize = getImageSize($path);
		if (!$imageSize) {
			if ($log)	error_log(date("F j, Y, g:i a") . "     {$path} isn't an image.\n", 3, RETINA_PHP_LOG);
			return array( 'error' => "{$path} isn't an image.", 'error_code' => 3);
		}

		// Copy the file
		$paths = pathinfo($path);

		if (!copy($path, $paths['dirname'] . '/' . $paths['filename'] . '@2x.' . $paths['extension'])) return FALSE;

		// Resize the original file to half it's dimensions
		$type = strtolower($paths['extension']);

		// Get the correct image type
		switch ($type) {
			case 'bmp' : 
				$image = imagecreatefromwbmp($path);
				break;
			case 'gif' :
				$image = imagecreatefromgif($path);
				break;
			case 'jpg' :
				$image = imagecreatefromjpeg($path);
				break;
			case 'jpeg' :
				$image = imagecreatefromjpeg($path);
				break;
			case 'png' :
				$image = imagecreatefrompng($path);
				break;
			default :
				return FALSE;
				break;
		}

		$height = $imageSize[1] / 2;
		$width = $imageSize[0] / 2;

		$newImage = imagecreatetruecolor($width, $height);

		// If the image is a gif or a png then preserve transparency
		if ($type == 'gif' || $type == 'png') {
			imagecolortransparent($newImage, imagecolorallocatealpha($newImage, 0, 0, 0, 127));
			imagealphablending($newImage, false);
	    imagesavealpha($newImage, true);
		}

		// Resample the image
		imagecopyresampled($newImage, $image, 0, 0, 0, 0, $width, $height, $imageSize[0], $imageSize[1]);

		// Sharpen the image
		$newImage = $this->sharpenImage($newImage);

		switch ($type) {
			case 'bmp' :
				imagewbmp($newImage, $path, 100);
				break;
			case 'gif' :
				imagegif($newImage, $path, 100);
				break;
			case 'jpg' :
				imagejpeg($newImage, $path, 100);
				break;
			case 'jpeg' :
				imagejpeg($newImage, $path, 100);
				break;
			case 'bmp' :
				imagepng($newImage, $path, 100);
				break;
		}

		return TRUE;
	}




	/**
	 * Sharpen an image
	 * @param  [image] $image [the resized image]
	 * @return [image]        [the sharpened image]
	 */
	public function sharpenImage($image) {

		// Create a sharpen matrix
		$matrix = array(
								array(-1, -1, -1),
								array(-1, 16, -1),
								array(-1, -1, -1),
							);

		// Calculate the sharpen divisor
		$divisor = array_sum(array_map('array_sum', $matrix));

		// Apply the matrix to the image
		imageconvolution($image, $matrix, $divisor, 0);

		return $image;
	}




	/**
	 * Check if a @2x file exists for a given file
	 * @param  [string] $path [path of the original file]
	 * @return [boolean]       [returns TRUE if the file exists]
	 * Can be used to check if a file already has a @2x file before
	 * sending it to the resize function. However, you might decide
	 * that you want to overwrite the file anyways. 
	 */
	public function check2xExists($path) {

		// Get the path of the image
		$paths = pathinfo($path);

		if (file_exists($paths['dirname'] . '/' . $paths['filename'] . '@2x.' . $paths['extension'])) {
			return TRUE;
		}
		else {
			return FALSE;
		}
	}




	/**
	 * Remove a @2x file. Should be run after the original
	 * is deleted
	 * @param  [boolean] $log [logs an error in the log file if true]
	 * @return [TRUE if successful else returns an error array]
	 */
	public function removeResizedImage($path, $log = TRUE) {

		// Get the path info of the image
		$paths = pathinfo($path);
		$path = $paths['dirname'] . '/' . $paths['filename'] . '@2x.' . $paths['extension'];

		// Check that the file exists
		if (!file_exists($path)) {
			if ($log)	error_log(date("F j, Y, g:i a") . "     {$path} does not exist.\n", 3, RETINA_PHP_LOG);
			return array( 'error' => "{$path} does not exist.", 'error_code' => 1);
		}

		// Check that the file is writable
		if (!is_writable($path)) {
			if ($log)	error_log(date("F j, Y, g:i a") . "     {$path} isn't writable.\n", 3, RETINA_PHP_LOG);
			return array( 'error' => "{$path} isn't writable.", 'error_code' => 2);
		}

		// Delete the file
		unlink($path);

		return TRUE;
	}




	/**
	 * Takes a folder array and checks each folder for added images
	 * The folder array consists of an array for each 
	 * folder [0] => path, [1] => boolean for recursive.
	 * If recursive is TRUE, the method will search all sub
	 * folders too.
	 * @param  array  $folders [(string) path, (boolean) recursive]
	 * @return [boolean]          [returns TRUE]
	 */
	public function checkFoldersForNewImages($folders = array()) {

		// For each of the folders check the folder
		foreach ($folders as $folder) {
			$this->checkFolderForNewImages($folder[0], $folder[1]);
		}
		return TRUE;
	}




	/**
	 * Check the images in a particular folder
	 * @param  [string]  $folder    [path]
	 * @param  boolean $recursive [check recursive]
	 * @return [boolean]             [return TRUE on success]
	 */
	public function checkFolderForNewImages($folder, $recursive = FALSE) {

		// Check that the folder is indeed a folder
		if (!is_dir($folder)) return FALSE;

		// Get all the files from the directory
		$files = scandir($folder);

		// Loop through each file and resize the image
		// If it is not an image the method will exit early
		foreach ($files as $file) {
			// Ignore hidden files and parent directories
			if (substr($file, 0, 1) !== '.') {

				$file = $folder . '/' . $file;

				// If it's a directory and recursive is true, go to next level
				if (is_dir($file)) {
					if ($recursive) $this->checkFolderForNewImages($file, $recursive);
				}
				else {
					// Send to the resizeImage
					if (!$this->check2xExists($file)) {
						$this->resizeImage($file);
					}
				}
			}
		}
		return TRUE;
	}




	/**
	 * Takes a folder array and checks each folder for removed images
	 * The folder array consists of an array for each 
	 * folder [0] => path, [1] => boolean for recursive.
	 * If recursive is TRUE, the method will search all sub
	 * folders too.
	 * @param  array  $folders [(string) path, (boolean) recursive]
	 * @return [boolean]          [returns TRUE]
	 */
	public function checkFoldersForRemovedImages($folders = array()) {

		// For each of the folders check the folder
		foreach ($folders as $folder) {
			$this->checkFolderForRemovedImages($folder[0], $folder[1]); 
		}
		return TRUE;
	}




	/**
	 * Checks a folder to see if @2x images have an original file
	 * @param  [string]  $folder    [path of the folder]
	 * @param  boolean $recursive [TRUE if subfolders should be scanned]
	 * @return [boolean]             [returns TRUE on success]
	 */
	public function checkFolderForRemovedImages($folder, $recursive = FALSE) {

		// Check that the folder is indeed a directory
		if (!is_dir($folder)) return FALSE;

		// Get all the files from the directory
		$files = scandir($folder);

		// Loop through each file and check if it's a @2x image
		foreach ($files as $file) {
			if (substr($file, 0, 1) !== '.') {

				$file = $folder . '/' . $file;

				// If it's a directory and recursive is true, go to next level
				if (is_dir($file)) {
					if ($recursive) $this->checkFolderForRemovedImages($file, $recursive);
				}
				else {
					$filename = pathinfo($file, PATHINFO_FILENAME);

					// If the last 3 characters of the file is @2x
					if (substr($filename, strlen($filename) - 3, strlen($filename)) === '@2x') {

						// If it's writable, then check if original exists
						// if not, then remove it
						if (is_writable($file)) {
							if (!$this->checkOrignalExists($file)) {
								unlink($file);
							}
						}
					}
				}
			}
		}
	}




	/**
	 * Check if the original image still exists for an existing @2x file
	 * @param  [string] $path [path to file]
	 * @return [boolean]       [returns FALSE if file doesn't exist]
	 */
	public function checkOrignalExists($path) {
		$paths = pathinfo($path);
		$filename = substr($paths['filename'], 0, strlen($paths['filename']) - 3);

		if (file_exists($paths['dirname'] . '/' . $filename . '.' . $paths['extension'])) {
			return TRUE;
		}
		return FALSE;
	}

}

$retinaPhp = new RetinaPhp;
