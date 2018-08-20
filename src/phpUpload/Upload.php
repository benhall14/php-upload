<?php

namespace benhall14\phpUpload;

/**
 * An Upload class that makes handing uploads and validation easier.
 *
 * @copyright  Copyright (c) Benjamin Hall
 * @author Benjamin Hall <ben@conobe.co.uk> https://conobe.co.uk
 * @license https://github.com/benhall14/php-upload
 * @version 1.0
 */
class Upload
{
    /**
     * Stores the files.
     *
     * @var array
     */
    public $files = [];

    /**
     * Store the original input elements.
     *
     * @var array
     */
    protected $input = [];

    /**
     * Store the number of file inputs found.
     *
     * @var int
     */
    protected $count = null;

    /**
     * The HTML elements id.
     *
     * @var string
     */
    protected $id = false;

    /**
     * Stores the name which will override the source filename. Should only be used with single uploads.
     *
     * @var string
     */
    protected $name = null;

    /**
     * Stores the id of the matching method to use. 1 - mime_type or 2 - extension.
     *
     * @var int
     */
    protected $matching_type = 1; # 1 - mime, 2 - extension

    /**
     * Stores the allowable mime types. Either a '*' wild-card string or an
     * array ('image/jpeg', 'image/png', 'image/gif');
     *
     * @var mixed
     */
    protected $allowed_mime_types = '*';

    /**
     * Stores the allowable file types. Either a '*' wild-card string or an array ('jpg', 'png', 'gif');
     *
     * @var mixed
     */
    protected $allowed_file_types = '*';

    /**
     * Stores the maximum file size in bytes. Default 5 MB.
     *
     * @var int
     */
    protected $max_file_size = 5242880;

    /**
     * Stores the minimum file size in bytes. Default 0.
     *
     * @var int
     */
    protected $min_file_size = 0;

    /**
     * If the upload should use a unique generated name.
     *
     * @var bool
     */
    protected $generate_name = false;

    /**
     * If the upload should force the use of a default extension type.
     *
     * @var string
     */
    protected $force_extension = false;

    /**
     * The default chmod folder creation mask.
     *
     * @var int
     */
    protected $path_chmod = 0777;

    /**
     * Stores the state of the upload process.
     *
     * @var bool
     */
    protected $success = false;

    /**
     * Class Version.
     *
     * @var float
     */
    protected $class_version = 1.0;

    /**
     * Stores the messages that could be thrown. Use the setMessages() method to override the default messages.
     *
     * @var array
     */
    protected $messages = [
        'invalid_file_element' => 'Invalid File Element ID',
        'nothing_uploaded' => 'No files have been selected to upload.',
        'invalid_destination' => 'Invalid Destination Path',
        'could_not_create_path' => 'The %s doesn\'t exist and could not be created.',
        'file_input_missing' => 'The file id %s is missing from the upload form.',
        'too_small' => 'Too small',
        'too_large' => 'Too large',
        'invalid_mime' => 'Invalid mime type uploaded (%s). Allowed mime types are: %s.',
        'invalid_ext' => 'Invalid file type uploaded (%s). Allowed file types are: %s.',
        'upload_move_error' => 'The file could not be uploaded: Permission Error',
        'destination_missing' => 'The destination path configuration setting is missing.',
    ];

    /**
     * Stores any error messages thrown in execution.
     *
     * @var array
     */
    protected $errors = [];

    /**
     * Sets up the upload handling.
     *
     * @param string $file The HTML element id for the file input.
     * @return void
     */
    public function __construct($file = null)
    {
        if (empty($file)) {
            $this->throwError('invalid_file_element');
        }

        $this->id = $file;
    }

    /**
     * The current version of the upload class.
     *
     * @return float The version number.
     */
    public function version()
    {
        return $this->class_version;
    }

    /**
     * A static method that reveals if any $_FILES have been submitted in the form.
     *
     * @return bool True if submitted, else false.
     */
    public function submitted()
    {
        if (empty($_FILES) || empty($_FILES[$this->id]) || empty($_FILES[$this->id]['name'][0])) {
            return false;
        }

        return true;
    }

    /**
     * Throw the error exception.
     *
     * @param  string $key  The key of the message to throw.
     * @param  array $args The argument list of replacements - see sprintf.
     * @return void
     */
    protected function throwError($key, $args = array())
    {
        throw new \Exception($this->errorMessage($key, $args));
    }

    /**
     * Gets the error message.
     *
     * @param  string $key  The key of the error message to get.
     * @param  array $args The argument list of replacements - see sprintf.
     * @return void
     */
    protected function errorMessage($key, $args = array())
    {
        if (!is_array($args)) {
            $args = array($args);
        }

        return vsprintf($this->getMessage($key), $args);
    }

    /**
     * Get the message by key - enables the custom message overrides.
     *
     * @param  string $key The key index of the message to return.
     * @return string      The actual message string.
     */
    protected function getMessage($key)
    {
        return $this->messages[$key];
    }

    /**
     * Allows the customization of the preset class exception messages.
     *
     * @param array $messages A message_id => message array of messages to update.
     * @return self
     */
    public function setMessages($messages = [])
    {
        if (!$messages || !is_array($messages)) {
            return $this;
        }

        foreach ($messages as $key => $message) {
            $this->messages[$key] = $message;
        }

        return $this;
    }

    /**
     * Set the list of allowed file mime types.
     *
     * This is a more secure method of matching the file mime types uploaded with
     * a preset list of allowable mime types. The allowed mime types can be either
     * an array of mime_types ('image/jpg', 'image/png') or the '*' wild-card string.
     *
     * @param mixed $allowed_mime_types The allowed mime types.
     * @return self
     */
    public function setAllowedMimeTypes($allowed_mime_types = false)
    {
        if (!$allowed_mime_types || !is_array($allowed_mime_types)) {
            $this->allowed_mime_types = '*';
            return $this;
        }

        $this->matching_type = 1;
        $this->allowed_mime_types = [];

        foreach ($allowed_mime_types as $mime_type) {
            $mime_type = trim($mime_type);
            if ($mime_type) {
                $this->allowed_mime_types[] = strtolower($mime_type);
            }
        }

        return $this;
    }

    /**
     * Set the list of allowed file extensions.
     *
     * This is a basic, although insecure, method of matching the file extension
     * uploaded with a preset list of allowable extensions. Allowed filed types
     * can be either an array of file extensions ('jpg', 'png', 'gif'), a pipe-separated
     * list 'jpg|png|gif' or the '*' wild-card string.
     *
     * @param mixed $allowed_file_types Allowed file types.
     * @return self
     */
    public function setAllowedExtensions($allowed_file_types = false)
    {
        if (!$allowed_file_types) {
            return $this;
        }

        $this->matching_type = 2;

        if ($allowed_file_types === '*') {
            $this->allowed_file_types = '*';

            return $this;
        }

        $this->allowed_file_types = [];

        if (!is_array($allowed_file_types)) {
            $allowed_file_types = explode('|', $allowed_file_types);
        }

        foreach ($allowed_file_types as $file_type) {
            $file_type = trim($file_type);
            if ($file_type) {
                $this->allowed_file_types[] = strtolower($file_type);
            }
        }

        return $this;
    }

    /**
     * Get the current list of allowed file extensions.
     *
     * @return array A list of file extensions that are allowed
     */
    public function getAllowedExtensions()
    {
        return $this->allowed_file_types;
    }

    /**
     * Get the current list of allowed mime types.
     *
     * @return array A list of mime types that are allowed
     */
    public function getAllowedMimeTypes()
    {
        return $this->allowed_mime_types;
    }

    /**
     * Calculate the number of bytes according to the specified size and type.
     *
     * @param  int $size The size
     * @param  string $type The type i.e b/kb/mb/gb
     * @return int       The calculated size
     */
    protected function calculateSize($size, $type)
    {
        $type = strtolower($type);
        if ($type === 'kb') {
            return $size * 1024;
        } elseif ($type === 'mb') {
            return $size * 1048576;
        } elseif ($type === 'gb') {
            return $size * 1073741824;
        }

        return $size;
    }

    /**
     * Set the maximum file size. Can be either bytes, kilobytes, megabytes or gigabytes.
     * Use the $type flag to set the formatting.
     *
     * @param bool $maximum_file_size A int file size
     * @param string  $type           The amount - b=bytes, kb=kilobytes, mb=megabytes or gb=gigabytes
     */
    public function setMaxFileSize($maximum_file_size = false, $type = 'b')
    {
        $this->max_file_size = (int) $this->calculateSize($maximum_file_size, $type);

        return $this;
    }

    /**
     * Set the minimum file size. Can be either bytes, kilobytes, megabytes or gigabytes.
     * Use the $type flag to set the formatting.
     *
     * @param bool $minimum_file_size A int file size
     * @param string  $type              The amount - b=bytes, kb=kilobytes, mb=megabytes or gb=gigabytes
     */
    public function setMinFileSize($minimum_file_size = false, $type = 'b')
    {
        $this->min_file_size = (int) $this->calculateSize($minimum_file_size, $type);

        return $this;
    }

    /**
     * Returns a bool based on if there are multiple files attached to $_FILES.
     *
     * @return bool True if multiple files exist, else false.
     */
    public function isMultiple()
    {
        if (count($_FILES[$this->id]['name']) > 1) {
            return true;
        }

        return false;
    }

    /**
     * Returns the maximum file size allowed in bytes.
     *
     * @return int The maximum file size.
     */
    public function getMaxFileSize()
    {
        return $this->max_file_size;
    }

    /**
     * Returns the minimum file size allowed in bytes.
     *
     * @return int The minimum file size.
     */
    public function getMinFileSize()
    {
        return $this->min_file_size;
    }

    /**
     * Sets up the upload handling based on the configuration.
     *
     * @return self
     */
    public function ignition()
    {
        /* get the posted files */
        $this->input = isset($_FILES[$this->id]) ? $_FILES[$this->id] : [];
        if (!$this->input || empty($this->input['name'][0])) {
            $this->throwError('invalid_file_element');
        }

        $this->count = count(array_filter($this->input['tmp_name']));

        if (!isset($this->destination_path) || empty($this->destination_path)) {
            $this->throwError('destination_missing');
        }

        $files = [];

        # check if there is ONE input using either name="input" or name="input[]".
        if ($this->count === 1) {
            $files[0] = array(
                'name' => is_array($this->input['name']) ? $this->input['name'][0] : $this->input['name'],
                'type' => is_array($this->input['type']) ? $this->input['type'][0] : $this->input['type'],
                'tmp_name' => is_array($this->input['tmp_name'])
                        ? $this->input['tmp_name'][0]
                        : $this->input['tmp_name'],
                'error' => is_array($this->input['error']) ? $this->input['error'][0] : $this->input['error'],
                'size' => is_array($this->input['size']) ? $this->input['size'][0] : $this->input['size']
            );
            # else if there are more than 1 name="input[]"
        } elseif ($this->count > 1) {
            foreach ($this->input as $property_index => $property) {
                foreach ($property as $index => $value) {
                    if ($this->input['name'][$index]) {
                        $files[$index] = array(
                            'name' => $this->input['name'][$index],
                            'type' => $this->input['type'][$index],
                            'tmp_name' => $this->input['tmp_name'][$index],
                            'error' => $this->input['error'][$index],
                            'size' => $this->input['size'][$index],
                        );
                    }
                }
            }
        }

        foreach ($files as $index => $file) {
            $source = new \stdClass;
            $source->path = $file['tmp_name'];
            $source->filename = $file['name'];
            $source->extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $source->name = str_replace('.' . $source->extension, '', $source->filename);
            $source->size = (int) $file['size'];
            $source->type = $file['type'];
            if (class_exists('finfo')) {
                $finfo = new \finfo;
                $source->mime_type = $finfo->file($source->path, FILEINFO_MIME_TYPE);
            }

            if (!isset($source->mime_type) || empty($source->mime_type)) {
                $source->mime_type = strtolower(mime_content_type($source->path));
            }
            $source->error = $file['error'];

            $dest_file = new \stdClass;
            $dest_file->size = $source->size;
            $dest_file->mime_type = $source->mime_type;
            $dest_file->extension = $this->force_extension ? : $source->extension;

            $filename = $this->name ? $this->name : $source->name;

            $dest_file->name = $this->uploadFilePath($filename, $dest_file->extension);
            $dest_file->filename = $dest_file->name . '.' . $dest_file->extension;
            $dest_file->path = $this->destination_path;

            $this->files[$index] = new \stdClass;
            $this->files[$index]->id = $this->id;
            $this->files[$index]->source = $source;
            $this->files[$index]->destination = $dest_file;
            $this->files[$index]->success = false;
            $this->files[$index]->isValid = false;
        }

        return $this;
    }

    /**
     * Set the destination path for the uploaded files.
     *
     * @param bool $path The absolute path.
     * @return self
     */
    public function setDestinationPath($path = false)
    {
        if (!$path) {
            $this->throwError('invalid_destination');
        }

        if (!file_exists($path)) {
            if (!mkdir($path, $this->path_chmod)) {
                $this->throwError('could_not_create_path', array($path));
            }
        }

        $this->destination_path = $path;

        return $this;
    }

    /**
     * Cleans the filename to ensure its server-ready.
     *
     * @param  string $name The unclean name
     * @return string       The clean name
     */
    protected function cleanFilename($name)
    {
        $name = preg_replace('/\s+/', '-', $name);

        return $name;
    }

    /**
     * Sets the overriding file name - using the cleanFilename method.
     *
     * @param bool $name  The overriding file name.
     * @param bool $clean Whether to turn on/off name cleaning.
     * @return self
     */
    public function setName($name = false, $clean = true)
    {
        if ($clean) {
            $name = strtolower($this->cleanFilename($name));
        }

        if ($name) {
            $this->name = $name;
        }

        return $this;
    }

    /**
     * Forces the uploaded file to be saved as the custom extension.
     *
     * @param string $extension The overriding file extension.
     * @return self
     */
    public function setExtensionAs($extension = null)
    {
        if ($extension) {
            $this->force_extension = $extension;
        }

        return $this;
    }

    /**
     * Sets the configuration option to create random filenames on the fly.
     *
     * @param  bool $bool True or False to turn on/off the generation of file names.
     * @return self
     */
    public function generateName($bool)
    {
        $this->generate_name = (bool) $bool;

        return $this;
    }

    /**
     * Uses the filename and extension arguments and creates a full upload path that is unique.
     * Appends -$counter to avoid files that already exist.
     *
     * @param  string $filename  The filename without extension.
     * @param  string $extension The file extension.
     * @return string            The unique name that is available.
     */
    protected function uploadFilePath($filename, $extension)
    {
        $counter = 0;

        $temp_name = $this->getName($filename);

        $path = $this->destination_path . $temp_name  . '.' . $extension;

        do {
            $counter++;
            $temp_name = $this->getName($filename, $counter);
            $path = $this->destination_path . $temp_name . '.' . $extension;
        } while (file_exists($path));

        return $temp_name;
    }

    /**
     * Get the name based on the filename and counter supplied.
     *
     * @param  string  $filename The filename to check.
     * @param  int $counter  The counter int
     * @return string            The returned name.
     */
    protected function getName($filename, $counter = null)
    {
        if ($this->generate_name) {
            return $this->createName();
        }

        if ($counter) {
            return $filename . '-' . $counter;
        }

        return $filename;
    }

    /**
     * Generate a unique string - used for creating names.
     *
     * @param  array $args An array of options to override the defaults.
     * @return string       The generated string
     */
    protected function generateRandomString($args)
    {
        $default = ['length' => 12, 'uppercase' => true, 'lowercase' => false, 'numbers' => true];

        $args = array_merge($default, $args);

        $chars = '_';

        if ($args['uppercase'] === true) {
            $chars .= "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        }

        if ($args['lowercase'] === true) {
            $chars .= "abcdefghijklmnopqrstuvwxyz";
        }

        if ($args['numbers'] === true) {
            $chars .= "123456789";
        }

        $name = "";
        for ($i = 0; $i < $args['length']; $i++) {
            $name.= $chars[rand(0, strlen($chars)-1)];
        }

        return $name;
    }

    /**
     * A wrapper that calls the generateRandomString method with some new parameters applied.
     *
     * @return string The generated name string
     */
    protected function createName()
    {
        return $this->generateRandomString(array(
            'length' => 32,
            'numbers' => true,
            'uppercase' => false,
            'lowercase' => true
        ));
    }

    /**
     * An event function that allows you to loop through the files list.
     *
     * @param  bool $callback
     * @return void
     */
    public function each($callback = false)
    {
        if (!is_callable($callback) || is_string($callback)) {
            return;
        }

        foreach ($this->files as &$file) {
            call_user_func($callback, $file);
        }
    }

    /**
     * An event function that allows you to loop through successful uploads.
     *
     * @param boolean $callback
     * @return void
     */
    public function success($callback = false)
    {
        if (!is_callable($callback) || is_string($callback)) {
            return;
        }

        foreach ($this->files as &$file) {
            if ($file->isValid && $file->success) {
                call_user_func($callback, $file);
            }
        }
    }

    /**
     * Returns a list of successfully uploaded files.
     *
     * @return array
     */
    public function successes()
    {
        $successes = [];
        foreach ($this->files as $file) {
            if ($file->isValid && $file->success) {
                $successes[] = $file;
            }
        }

        return $successes;
    }

    /**
     * An event function that allows you to loop through failed uploads.
     *
     * @param boolean $callback
     * @return void
     */
    public function error($callback = false)
    {
        if (!is_callable($callback) || is_string($callback)) {
            return;
        }

        foreach ($this->files as &$file) {
            if (!$file->isValid && !$file->success) {
                call_user_func($callback, $file);
            }
        }
    }

    /**
     * Returns an array of all files in the queue (including processed upload).
     *
     * @return array An array of files.
     */
    public function files()
    {
        return $this->files;
    }

    /**
     * A bool state of whether all files are valid.
     *
     * @return bool True or False
     */
    public function hasErrors()
    {
        if (count($this->errors()) != 0) {
            return true;
        }

        return false;
    }

    /**
     * Returns an array of all files that are invalid and have not been processed.
     *
     * @return array An array of files
     */
    public function errors()
    {
        $errors = [];
        foreach ($this->files as $file) {
            if (!$file->isValid && !$file->success) {
                $errors[] = $file;
            }
        }

        return $errors;
    }

    /**
     * Loops through all files that are valid but have not yet been processed and applies the callback to each one.
     *
     * @param  function $callback The callback function.
     * @return void
     */
    public function awaiting($callback = false)
    {
        if (!is_callable($callback) || is_string($callback)) {
            return;
        }

        foreach ($this->files as &$file) {
            if (!$file->success) {
                call_user_func($callback, $file);
            }
        }
    }

    /**
     * Runs the validation on the $file supplied.
     *
     * @param  object $file The current file object
     * @return bool       Returns true on success, or throws error.
     */
    public function validate($file)
    {
        if (!$file) {
            $file->isValid = false;
            $file->errors[0] = $this->errorMessage('invalid_file_element', $file->id);
            return false;
        }

        if ($file->source->size < $this->min_file_size) {
            $file->isValid = false;
            $file->errors[1] = $this->errorMessage('too_small');
            return false;
        }

        if ($file->source->size > $this->max_file_size) {
            $file->isValid = false;
            $file->errors[2] = $this->errorMessage('too_large');
            return false;
        }

        if ($this->matching_type === 1) {
            if ($this->allowed_mime_types != '*') {
                if (!in_array($file->source->mime_type, $this->allowed_mime_types)) {
                    $file->isValid = false;
                    $file->errors[3] = $this->errorMessage('invalid_mime', array(
                        $file->source->mime_type,
                        implode(
                            ', ',
                            $this->allowed_mime_types
                        )
                    ));
                    return false;
                }
            }
        } else {
            if ($this->allowed_file_types != '*') {
                if (!in_array($file->source->extension, $this->allowed_file_types)) {
                    $file->isValid = false;
                    $file->errors[4] = $this->errorMessage('invalid_ext', array(
                        $file->source->extension,
                        implode(
                            ', ',
                            $this->allowed_file_types
                        )
                    ));
                    return false;
                }
            }
        }

        $file->isValid = true;
        $file->errors = null;
        return true;
    }

    /**
     * Validate and process the $file supplied.
     *
     * @param  object $file The current file object.
     * @return bool       Returns true on success, or throws error.
     */
    public function process($file)
    {
        if ($file->success) {
            return true;
        }

        if (!$this->validate($file)) {
            $file->success = false;
            return false;
        }

        $destination_path = $file->destination->path . $file->destination->name . '.' . $file->destination->extension;

        if (!move_uploaded_file($file->source->path, $destination_path)) {
            $this->throwError('upload_move_error');
            $file->success = false;
        }

        $file->success = true;
        $file->processed = true;
        return true;
    }

    /**
     * Sets up the validation and automatically processes all files.
     *
     * @return bool Returns true on success, or throws error.
     */
    public function upload()
    {
        foreach ($this->files as $file) {
            $this->process($file);
        }

        return true;
    }

    /**
     * Returns the number of files found by the class on the file element.
     *
     * @return int
     */
    public function fileCount()
    {
        return $this->count;
    }

    /**
     * Prints some debugging information - used for pinpointing permission/file size errors.
     *
     * @return void
     */
    public function debug()
    {
        echo '<pre>';
        echo "=======================================================================\r\n";
        echo "================================ Debug ================================\r\n";
        echo "=======================================================================\r\n";
        echo "Class Version: <b>" . number_format($this->class_version, 2) . "</b>\r\n";
        echo "PHP File Uploads: <b>" . (ini_get('file_uploads') ? 'ON' : 'OFF') . "</b>\r\n";
        echo "PHP TMP Upload Dir: <b>" . ini_get('upload_tmp_dir') . "</b>\r\n";
        echo "PHP Max Input Nesting Level: <b>" . ini_get('max_input_nesting_level') . "</b>\r\n";
        echo "PHP Max Input Vars: <b>" . ini_get('max_input_vars') . "</b>\r\n";
        echo "PHP Max Upload File size: <b>" . ini_get('upload_max_filesize') . "</b>\r\n";
        echo "PHP Max File Uploads: <b>" . ini_get('max_file_uploads') . "</b>\r\n";
        $post_size = ini_get('post_max_size') == 0 ? 'UNLIMITED' : ini_get('post_max_size');
        echo "PHP Max Post Size: <b>" . $post_size . "</b>\r\n";
        echo "=======================================================================\r\n";
        echo "=======================================================================\r\n";
        echo "=======================================================================\r\n";
        echo '</pre>';
    }
}
