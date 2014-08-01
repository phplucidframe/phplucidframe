<?php
/**
 * PHP 5
 *
 * LucidFrame : Simple & Flexible PHP Development
 * Copyright (c), LucidFrame.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @package     LC.helpers 
 * @author		Sithu K. <cithukyaw@gmail.com>
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 */

define('FILE_RESIZE_WIDTH', 'width');
define('FILE_RESIZE_HEIGHT', 'height');
define('FILE_RESIZE_BOTH', 'both');

class File{
		
	private $uniqueId;
	private $dimensions;
	private $uploadPath;
	private $extensions;
	private $resize = FILE_RESIZE_BOTH;
	private $fileNameBased;
	
	public function File(){
		$this->extensions = array('jpg', 'jpeg', 'png', 'gif');
	}
	
	public function set($key, $value){
		$this->$key = $value;
	}
	
	public function getFileNameBased(){
		return $this->fileNameBased;
	}
	/**
	 * Move the uploaded file into the given directory.
	 * If the uploaded file is image, this will create the various images according to the given $dimension
	 * @param $file	(array) the uploaded file information from $_FILES['xxx']
	 * @param 		(array) Array of the uploaded files, for example,
	 *						uploaded[dimension] = file-name for image files or uploaded[] = file-name for other files
	 */
	public function upload($file){		
		$fileName 		= stripslashes($file['name']);
		$uploadedFile 	= $file['tmp_name'];
		$info 			= pathinfo($fileName);
		$extension 		= strtolower($info['extension']);
		$uploaded 		= false;
		$path 			= $this->uploadPath;
		
		if($fileName && $uploadedFile){			
			
			if( !(is_array($this->dimensions) && count($this->dimensions)) ){
				$newFileName = $this->getNewFileName($fileName);
				if(move_uploaded_file($uploadedFile, $path . $newFileName)){
					$uploaded = array($newFileName);
				}			
			}else{				
				if($extension == "jpg" || $extension == "jpeg" ){
					$img = imagecreatefromjpeg($uploadedFile);
				}elseif($extension == "png"){
					$img = imagecreatefrompng($uploadedFile);
				}elseif($extension == "gif"){
					$img = imagecreatefromgif($uploadedFile);
				}
				
				if( isset($img) && $img ){
					$uploaded = array();			
					foreach($this->dimensions as $dimension){
						$resize = explode('x', $dimension);
						$resizeWidth 	= $resize[0];
						$resizeHeight 	= $resize[1];
						
						if($this->resize == FILE_RESIZE_WIDTH){
							$tmp = File::resizeImageWidth($img, $uploadedFile, $resizeWidth);
						}elseif($this->resize == FILE_RESIZE_HEIGHT){
							$tmp = File::resizeImageHeight($img, $uploadedFile, $resizeHeight);
						}else{
							$tmp = File::resizeImage($img, $uploadedFile, $resizeWidth, $resizeHeight);
						}

						$newFileName = $this->getNewFileName($fileName, $resizeWidth);
						
						if($extension == "gif"){				
							imagegif($tmp, $path . $newFileName);
						}elseif($extension == "png"){
							imagepng($tmp, $path . $newFileName);
						}else{
							imagejpeg($tmp, $path . $newFileName, 100);
						}
						
						imagedestroy($tmp);
						$uploaded[$dimension] = $newFileName;
					}
					if($img) imagedestroy($img);
				}									
			}
		}
		return $uploaded;		
	}
	/**
	 * Get a new file name, e.g., original-file-name-[imageWidth]-[uniqueId].ext
	 * Spaces and periods in the original file names are replaced with dashes.
	 * @param $file		(string) the uploaded file name
	 * @param $width 	(int) The image width if the uploaded file is image, otherwise 0
	 */
	private function getNewFileName($file, $width=0){
		$info = pathinfo($file);
		$uniqueId = $this->getUniqueId();
		# get the suffix
		$suffix = '';
		if($width) $suffix .= '-' . $width;		
		$suffix .= '-' . $uniqueId;
		# clean spaces and periods and replace with dashes
		$justName = str_replace(array(' ', '.'), '-', $info['filename']);
		$justName = preg_replace('/[\-]+/', '-', $justName);
		$justName = preg_replace("/['\"]+/", '', $justName);
		$fileName = $justName . $suffix . '.' . $info['extension'];		
		$this->fileNameBased = $justName . '-' . $uniqueId . $info['extension'];
		return $fileName;	
	}
	/**
	 * Get a unique id string from the property $uniqueId or generate a random 5-letters string	 
	 */
	private function getUniqueId(){
		if($this->uniqueId) return $this->uniqueId;
		else return substr(md5(time()), 0, 5); 
	}
	/**
	 * Resize an image to a desired width and height by given width
	 * @param $img		(resource) 
	 * @param $file		(string) the image file
	 * @param $newWidth	(string) the new width to resize
	 */		
	public static function resizeImageWidth(&$img, $file, $newWidth){
		list($width, $height) = getimagesize($file);	
		$newHeight = ($height/$width) * $newWidth;
		$tmp = imagecreatetruecolor($newWidth, $newHeight);	
		imagecopyresampled($tmp, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
		return $tmp;
	}
	/**
	 * Resize an image to a desired width and height by given height
	 * @param $img		(resource) 
	 * @param $file		(string) the image file
	 * @param $newHeight(string) the new height to resize
	 */		
	public static function resizeImageHeight(&$img, $file, $newHeight){
		list($width, $height) = getimagesize($file);	
		$newWidth = ($width/$height) * $newHeight;
		$tmp = imagecreatetruecolor($newWidth, $newHeight);	
		imagecopyresampled($tmp, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
		return $tmp;
	}
	/**
	 * Resize an image to a desired width and height by given width and height
	 *
	 * @param $img		 The GD image resource
	 * @param $file		 string The image file
	 * @param $newWidth	 integer The new width to resize
	 * @param $newHeight integer The new height to resize
	 *
	 * @return resource Returns an image identifier
	 */		
	public static function resizeImage(&$img, $file, $newWidth, $newHeight){
		list($width, $height) = getimagesize($file);
		
		$scale = min($newWidth/$width, $newHeight/$height);	
		# If the image is larger than the max shrink it
		if ($scale < 1) {
			# new width for the image
			$newWidth = floor($scale * $width);
			# new heigth for the image
			$newHeight = floor($scale * $height);							
		}else{ 
		# if the image is small than than the resized width and height			
			$newWidth = $width;
			$newHeight = $height;
		}
						
		$tmp = imagecreatetruecolor($newWidth, $newHeight);	
		imagecopyresampled($tmp, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
		return $tmp;
	}
	/**
	 * Display an image fitting into the desired dimension
	 *
	 * @param $fileName string The file name with an absolute web path
	 * @param $caption string The image caption
	 * @param $imgWidth	 integer The actual image width in pixel
	 * @param $imgHeight integer The actual image height in pixel
	 * @param $desiredWidth	 integer The desired image width in pixel
	 * @param $desiredHeight integer The desired image height in pixel	 
	 *
	 * @return string The <img> tag
	 */		
	public static function img($fileName, $caption, $imgWidth, $imgHeight, $desiredWidth=0, $desiredHeight=0, $attributes=array()){
		if($imgWidth > $desiredWidth || $imgHeight > $desiredHeight){ # scale down
			if($desiredWidth == 0 && $desiredHeight > 0){ # resized to height
				$desiredWidth 	= floor(($imgWidth/$imgHeight) * $desiredHeight);
				$imgWidth 		= $desiredWidth;
				$imgHeight 		= $desiredHeight;
			}
			elseif($desiredWidth > 0 && $desiredHeight == 0){ # resized to width
				$desiredHeight = floor(($imgHeight/$imgWidth) * $desiredWidth);
				$imgWidth 		= $desiredWidth;
				$imgHeight 		= $desiredHeight;				
			}
			elseif($desiredWidth > 0 && $desiredHeight > 0){ # resized both						
				$scale = min($desiredWidth/$imgWidth, $desiredHeight/$imgHeight);	
				//echo '....'.$scale;				
				# new width for the image
				$imgWidth  = floor($scale * $imgWidth);
				# new heigth for the image
				$imgHeight = floor($scale * $imgHeight);				
				if($imgWidth < $desiredWidth || $imgHeight < $desiredHeight){
					//echo $imgWidth.'x'.$imgHeight; echo ' --- ';
					$wDiff = $desiredWidth - $imgWidth;
					$hDiff = $desiredHeight - $desiredWidth;
					if($wDiff > $hDiff){ # resize to width						
						$imgHeight = floor(($imgHeight/$imgWidth) * $desiredWidth);
						$imgWidth  = $desiredWidth;
					}else{ # resize to height
						$imgWidth = floor(($imgWidth/$imgHeight) * $desiredHeight);
						$imgHeight = $desiredHeight;
					}
				}
			}
		}
		//echo $imgWidth.'x'.$imgHeight; echo ' --- ';
		//echo $desiredWidth.'x'.$desiredHeight;
		$style = '';
		if($imgWidth > $desiredWidth){
			$marginH = floor(($imgWidth - $desiredWidth)/2);
			$style = 'margin-left:-'.$marginH.'px';
		}
		if($imgHeight > $desiredHeight){
			$marginV = floor(($imgHeight - $desiredHeight)/2);
			$style = 'margin-top:-'.$marginV.'px';
		}
		if(isset($attributes['style']) && $attributes['style']){
			$style .= $attributes['style'];
		}
		$attributes['src']		= $fileName;
		$attributes['alt'] 		= _h($caption);
		$attributes['title'] 	= _h($caption);
		$attributes['width'] 	= $imgWidth;
		$attributes['height'] 	= $imgHeight;
		$attributes['style'] 	= $style;	
			
		$attrHTML = '';
		foreach($attributes as $key => $value){
			$attrHTML .= ' ' . $key . '="' . $value .'"';
		}
		return '<img '.$attrHTML.' />';
	}
}