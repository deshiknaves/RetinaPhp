#RetinaPhp

**Version 0.1 beta**

##Overview

RetinaPhp is a php class that can be used to save a second version of an image for non retina display devices. The user uploads an image that is twice the size of the normal image. RetinaPhp will take that image, rename it to @2x variant and then create an image with the original name at half the dimension.

**For example:**

Website requires an image that is 200px wide by 100px high.

The user should be asked to upload an image that is 400px wide by 200px high onto the website. User uploads an image called *image.jpg*

RetinaPhp will take this larger image and rename it to *image@2x.jpg* then creates another image called *image.jpg* resizes it 200px wide by 100px high and sharpens the image.

*I'm sure a lot more will get added to this class over time, but it will achieve what is needed to serve retina images. If you have any suggestions or feature requests let me know.*

##How the images get displayed
RetinaPhp was create with Retina.js <http://retinajs.com/> in mind. It is a fantastic script to swap high resolution images for devices that can take advantage of them. Check out the website for all the details.

Of course, there are plenty of other ways RetinaPhp can be used to serve high resolution images.

##How to use RetinaPhp
RetinaPhp has to be integrated into the website's upload/delete functions. There are method's in RetinaPhp that can be useful for bulk processing during Cron runs, but it's up to you how you want to use it.

###Include in your project
To include RetinaPhp to your project, use the *require, include, require_once, include_once*.

	require('includes/retinaphp.php');

At the of the file, a new RetinaPhp object will be instantiated in *$retinaPhp*. Now you can call methods on that object:

	$retinaPhp->resizeImage('/path/to/image.jpg');

Make sure that the web server has the ability to write to the directories that the files are located in.

###After image upload
After an image is uploaded through a form, you can take the path of the saved image to RetinaPhp to create a new version of the image and rename the current file to @2x.

	$retinaPhp->resizeImage('/path/to/image.jpg');
	
If your file gets updated, remember to remove the @2x image before saving a new one. This will be explained below.

This method has an optional second argument *$log* which defaults to TRUE. If this is set to FALSE, errors will not be logged. When this method gets called from the bulk process, it will be TRUE, as otherwise you will not be aware of errors that occurred. However, when dealing with a single image, you have the ability to handle the errors yourself. The error array has two items *error* and *error_code*. *Error* is the message and *error_code* is the numeric code. These codes are:

1. *filename* does not exist.
2. *filename* isn't writable.
3. *filename* isn't an image.

####Checking if a @2x image exists
You might want to check if a @2x variant you are saving already exists, or you might want to overwrite the file anyways. It's unto you. This method will return TRUE if it exists and FALSE if it doesn't.

	$retinaPhp->check2xExists('/path/to/image.jpg');

Most CMSs have hooks after a file is uploaded. You can incorporate this method in there to create a new image.	

###Removing a @2x image
When you remove a file on your website or CMS, you can send the path of the removed file to RetinaPhp to remove the associated with that file. Simply run:

	$retinaPhp->removeResizedImage('/path/to/image.jpg');
	
This method will return TRUE if deleted else return an error array like in *resizeImage*

Again, most CMSs will have hooks where you can incorporate this method.

##Bulk processing of images
You can chooses to process entire folders for new images or removed images if needed. Maybe there are theme folders that you need to process at once or there are folders that you want to watch and process periodically in a *Cron job*. *Search for **Cron jobs** to see how this might work*.

Both of these methods require an array of arrays of folders that it should check. There are 2 items in each array 1. path 2. Boolean for whether that folder should be scanned recursively (i.e. subdirectories).

		$folders = array(
			array('/path/to/folder', TRUE),
			array('/path/to/folder2', FALSE),
		);
		
###Scan folders for new images
RetinaPhp can scan a folder for any images that doesn't have a @2x variant. To achieve this, use:

	$retinaPhp->checkFoldersForNewImages($folders);
	
Any errors encountered will be logged in the retinaphp_errors.log file.

###Scan folders for removed images
RetinaPhp can also scan folders for images that have @2x files but their original non @2x version does not exist. To achieve this, use:
	
	$retinaPhp->checkFoldersForRemovedImages($folders);

Any errors encountered will be logged in the retinaphp_errors.log file.

##RetinaPhp log file
The log file is set to `getcwd() . '/retinaphp_errors.log'`, but you can set it to wherever you like by defining the constant `RETINA_PHP_LOG`.
