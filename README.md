# PHP Upload
A PHP class that makes handling uploads simpler. --------------------------------------------------------------

Tested to work with *PHP 5.6 and **PHP 7.***

# Installation with composer
You can now install the upload class with composer
	
	$ composer require benhall14/phpUpload

Please make sure that you include the composer autoloader and use the corrct namespace.

	require 'vendor/autoload.php';

	use benhall14\phpUpload\Upload as Upload;


# API Options

## ->version()

The *version()* method will return the class version.

    $upload->version();

## ->submitted()

The *submitted()* method will return either **true** or **false** depending on if the selected input element has been submitted or not.

    $upload->submitted();

## setMessages()

You can override the default messages by passing an array of custom messages to the *setMessages()* method.

    $upload->setMessages($custom_messages);

The default messages are:
    
    $messages = [
        # When the file element in the class is invalid.
        'invalid_file_element' => 'Invalid File Element ID', 
        # When no files have been selected for upload.
        'nothing_uploaded' => 'No files have been selected to upload.', 
        # When the destination path is invalid.
        'invalid_destination' => 'Invalid Destination Path', 
        # When the upload path could not be created.
        'could_not_create_path' => 'The %s doesn\'t exist and could not be created.', 
        # When the file element is missing from the __construct() method.
        'file_input_missing' => 'The file id %s is missing from the upload form.', 
        # When the uploaded file(s) don't fit the criteria for the minimum file size.
        'too_small' => 'Too small', 
        # When the uploaded file(s) don't fit the criteria for the maximum file size.
        'too_large' => 'Too large', 
        # When the uploaded file(s) don't fit the criteria for the allowed mime types.
        'invalid_mime' => 'Invalid mime type uploaded (%s). Allowed mime types are: %s.', 
        # When the uploaded file(s) don't fit the criteria for the allowed file extensions.
        'invalid_ext' => 'Invalid file type uploaded (%s). Allowed file types are: %s.', 
        # When the uploaded file(s) could not be moved due to a server or permission error.
        'upload_move_error' => 'The file could not be uploaded: Permission Error', 
        # When the destination path set does not exist and/or could not be created.
        'destination_missing' => 'The destination path configuration setting is missing.', 
    ];

## getAllowedExtensions()

This will return the current list of allowed file extensions.

	$extensions = $upload->getAllowedExtensions();
	
## setAllowedFileExtensions()
This is a basic, although insecure, method of matching the file extension uploaded with a preset list of allowable extensions. Allowed file types can be either an array of file extensions **array('jpg', 'png', 'gif')**, a pipe-separated list **'jpg|png|gif'**, or the **'*'** wild-card string.

	$upload->setAllowedFileExtensions('jpg|png|gif');
	# or
	$upload->setAllowedFileExtensions('*');
	# or
	$upload->setAllowedFileExtensions(array('jpg', 'png', 'gif'));

## setAllowedMimeTypes()
This is a more secure method of matching the file mime types uploaded with a preset list of allowable mime types. The allowed mime types can be either an array of mime_types **array('image/jpg', 'image/png')** or the **'*'** wild-card string.

	# to allow all types
	$upload->setAllowedMimeTypes('*');
	
	# to only allow jpg and png images.
	$upload->setAllowedMimeTypes(array('image/jpg', 'image/png'));

## getAllowedMimeTypes()

This will return the current list of allowed mime types.
	
	$mime_types = $upload->getAllowedMimeTypes();

## getMaxFileSize()
This will return the current maximum set file size in bytes.
	
	$size = $upload->getMaxFileSize();
	
## setMaxFileSize($maximum_file_size = false, $type = 'b')

This will set the **MAXIMUM** size that is permitted for the upload. The first parameter is the integer size and the second parameter is the type - such as b, kb, mb or gb.

	$upload->setMaxFileSize(10, 'mb');

## getMinFileSize()
This will return the current minimum set file size in bytes.
	
	$size = $upload->getMaxFileSize();

## setMinFileSize($minimum_file_size = false, $type = 'b')

This will set the **MINIMUM** size that is permitted for the upload. The first parameter is the integer size and the second parameter is the type - such as b, kb, mb or gb.

	$upload->setMinFileSize(1, 'mb');

## setDestinationPath($path = false)
Sets the destination path for the uploads. It can either be a absolute path or a relative path to the script.

	$upload->setDestinationPath('uploads/');

## setExtensionAs($extension = null)
Forces the uploaded file to use the passed $extension. This doesn't change the mime type - just forces the new file extension. Useful if you only want to accept a certain file type - such as jpg, it allows the forcing of of the jpg extension.

	$upload->setExtensionAs('jpg');

## setName($name = false, $clean = true)
Sets the overriding file name - using the cleanFilename method. **This can only be used with single element files**

	$upload->setName('my-image');

## generateName($bool)
Sets the configuration option to create random filenames on the fly.

	$upload->generateNames(true);

## ignition()
This is the main ignition switch. This must be called **after** all of the configuration options have been set.

This will populate the file counts  & internal file objects. *This starts the upload engine.*

	$upload->ignition();

## hasErrors()
This will return **true** or **false** depending on if an error has occurred. It can be combined with *errors()* to show a list of errors. 

	if($upload->hasErrors()){
		foreach($upload->errors() as $error){
			echo '<li>ERROR: ' . $error . '</li>';
		}
	}

## errors()
This returns an array of errors that occurred during the upload process. It can be combined with *hasError()*.

	if($upload->hasErrors()){
		foreach($upload->errors() as $error){
			echo '<li>ERROR: ' . $error . '</li>';
		}
	}

## isMultiple()
This will return **true** or **false** is the there are multiple files being uploaded.

	$is_multiple = $upload->isMultiple();
	
## fileCount()
This will return the number of file uploads submitted.

	$count = $upload->fileCount();
	
## debug()
This will print a debug message for development and testing.

	$upload->debug();

## successes()
This will return an array of successfully uploaded files.

	foreach($upload->successes() as $files){
		echo $file->name . ' has been uploaded';
	}

## files()
This will return an array of file objects. If called after the upload()  will also return the status of the upload.

	foreach($upload->files() as $files){
		echo $file->name;
	}
	
## upload()
This automatically performs the validation and processing on the uploaded files without additional coding.

	$upload->upload();

# Callback Options
The reason for this class is to provide the easiest way to handle uploads and validation without having to manually perform all of the checks. The following callback methods can be used for advanced upload integration. If you are looking for a simple integration - see **upload()** above.

## each($callback)
The **each()** accepts a callback and it loops through each file waiting to be uploaded.

	$upload->each(function($file){
		# this will print the file object for each upload found.
		print_r($file);
	});

## success($callback)
Applies the callback to all of the successfully upload files.
	
	$upload->success(function($file){
		echo $file->name . ' has been successfully uploaded.';
	});

## error($callback)
Applies the callback to all of the uploads that have had an error.

	$upload->error(function($file){
		echo $file->name . ' could not be uploaded due to an error.';	
	});
	
## validate($file)
Runs the validation method on the $file supplied. This is for use within an **each()** callback.

	$upload->each(function($file){
		$isValid = $this->validate($file);
	});

## process($file)
Runs the actual upload on the $file supplied. This is for use within an **each()** callback.

	$upload->each(function($file){
		$isValid = $this->validate($file);
		if($isValid){
			$this->process($file);
		}
	});
	
#$file
All of the methods that return a **$file** object will have the following properties.

	$file->id; # The id of the upload.
	$file->source->path; # the source tmp_path
	$file->source->filename; # the source file name
	$file->source->extension; # the source file extension
	$file->source->name; # the source file name
	$file->source->size; # the source size
	$files->source->type; # the source type from $_FILES
 	$files->source->mime_type; # the source mime type
	$files->source->error; # the $_FILES error.
	$file->destination->size; # the destination file size
	$file->destination->mime_type; # the destination mime type
 	$file->destination->extension; # the destination file extension
	$file->destination->filename; # the destination file name
	$file->destination->path; # the destination file path
	$file->success # returns boolean true/false
	$file->isValid # returns boolean true/false

# Simple Example
In this example, we show the uploader in its simplest form. We are using the following HTML code:

```html
<!DOCTYPE html>
<html>
	<body>
		<form method="post" enctype="multipart/form-data">
		    Select image to upload:
		    <input type="file" name="image_upload[]" id="image_upload">
		    <input type="file" name="image_upload[]" id="image_upload">
		    <input type="file" name="image_upload[]" id="image_upload">
		    <input type="file" name="image_upload[]" id="image_upload">
		    <input type="submit" value="Upload Image" name="submit">
		</form>
	</body>
</html>
```
PHP:

	try {
		$upload = new Upload('image_upload');

		if ($upload->submitted()) {
			$upload
				->setDestinationPath('uploads/') 
				->setAllowedMimeTypes(array('image/jpg', 'image/jpeg', 'image/png', 'text/plain'))
				->setMaxFileSize(2, 'mb')
				->ignition()
				->upload();

			if ($upload->hasErrors()) {
				foreach($upload->errors() as $error){
					echo '<li>' . $error . '</li>';
				}
			} else {
				echo 'Upload(s) Complete!';
			}
		}
	} catch (Exception $e) {
		die($e->getMessage());
	}

# Advanced Example
In this advanced example, we can showcase the callback methods. Again, we use the following HTML code:

```html
<!DOCTYPE html>
<html>
	<body>
		<form method="post" enctype="multipart/form-data">
		    Select image to upload:
		    <input type="file" name="image_upload[]" id="image_upload">
		    <input type="file" name="image_upload[]" id="image_upload">
		    <input type="file" name="image_upload[]" id="image_upload">
		    <input type="file" name="image_upload[]" id="image_upload">
		    <input type="submit" value="Upload Image" name="submit">
		</form>
	</body>
</html>
```
PHP: 

	try {
	    $upload = new Upload('image_upload');

	    if ($upload->submitted()) {
		$upload
		    ->setDestinationPath('uploads/')
		    ->setAllowedMimeTypes(array('image/jpg', 'image/jpeg', 'image/png', 'text/plain'))
		    ->setMaxFileSize(2, 'mb')
		    ->ignition();
	
		$upload->each(function ($file) use ($upload) {
		    if ($upload->validate($file)) {
			
			# we can use $file here and perform additional checks or manipulations.

			# now we process the upload
			if ($upload->process($file)) {

			    echo '<li>' . $file->destination->name . ' has been uploaded <b>SUCCESSFULLY</b>.</li>';
			    return;
			}
		    }
		    
		    echo '<li><b>ERROR:</b> ' . $file->source->name . ' could not be uploaded.</li>';
		    
		    return;
		});
	    }
	} catch (Exception $e) {
	    die($e->getMessage());
	}


# Requirements
**Tested to work with PHP 5.6 and PHP 7**

# License
Copyright (c) Benjamin Hall, benhall14@hotmail.com

Licensed under the MIT license

# Donate?

If you find this project helpful or useful in anyway, please consider getting me a cup of coffee - It's really appreciated :)

[![Donate](https://img.shields.io/badge/Donate-PayPal-green.svg)](https://paypal.me/benhall14)