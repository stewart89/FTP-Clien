<?php

/*
* CLass for FTP Operations
 */

class FTP {

	/**
	 * Host name
	 * @var string
	 * @access private
	 */
	private $host;

	/**
	 * Username
	 * @var string
	 * @access private
	 */
	private $username;

	/**
	 * Password
	 * @var string
	 * @access private
	 */
	private $password;

	/**
	 * Port
	 * @var integer
	 * @access private
	 */
	private $port;

	/**
	 * Timeout
	 * @var integer
	 * @access private
	 */
	private $timeout;

	/**
	 * FTP connection string
	 * @var object
	 * @access private
	 */
	private $connection;

	/**
	 * Creates and FTP connection object
	 * @param string  $host     host name
	 * @param string  $username username
	 * @param string  $password password
	 * @param integer $port     port
	 * @param integer $timeout  timeout
	 * @access public
	 */
	public function __construct($host, $username, $password, $port = 21, $timeout = 90){

		if($host == '' || $username == '' || $password == ''){

			throw new \Exception('Error: Empty host name, username or password');
		}

		$this->host = $host;
		$this->username = $username;
		$this->password = $password;
		$this->port = $port;
        $this->timeout = $timeout;
        

        $this->connection = ftp_connect($host, $port, $timeout);
		if($this->connection){

			if(!ftp_login($this->connection, $username, $password)){

				throw new \Exception('Error: Wrong username or password');
			}
		}else{

			throw new \Exception('Error: Can\'t connect to the server');
		}
	}

	/**
	 * Closes the connection
	 */
	public function __destruct(){

		$this->disconnect();
	}

	/**
	 * Closes the connection and try a reconnect
	 * @return boolean
	 * @access public
	 */
	public function reConnect(){

		$this->disconnect();
		$this->connection = ftp_connect($host, $port, $timeout);
		if($this->connection){

			if(!ftp_login($this->connection, $username, $password)){

				throw new \Exception('Error: Wrong username or password');
			}
		}else{

			throw new \Exception('Error: Can\'t connect to the server');
		}
		return true;
	}
	
	/**
	 * Sets passive mode
	 * @return boolean
	 * @access public
	 */
	public function setPassiveMode(){
	
		if(!ftp_pasv($this->connection, true)){
	
			throw new Exception('Hiba a passziv mód beállitása során');
		}
		return true;
	}

	/**
	 * Deletes passive mode
	 * @return boolean
	 * @access public
	 */
	public function deletePassiveMode(){

		if(!ftp_pasv($this->connection, false)){

			throw new Exception('Error: ');
		}
		return true;
	}

	/**
	 * Closes the connection 
	 * @return boolean
	 * @access public
	 */
	public function disconnect(){

		if(isset($this->connection)){
			return @ftp_close($this->connection);
		}
		return false;
	}

	/**
	 * Retrieves the files from the given path
	 * @param  string $path elérési út
	 * @return array       A fájlokat tartalmazó tömb
	 * @access public
	 */
	public function fileList($path){

		if(!$this->variableCheck($path, 'string')){
			return false;
		}

		if(!$filelist = ftp_nlist($this->connection, $path)){

			throw new Exception('Error: Cannot read files from the given path');
		}
		return $filelist;
	}

	/**
	 * Check the given file is exitst
	 * @param  string $path az eléréi útvonal
	 * @return boolean
	 * @access public
	 */
	public function fileExists($path){

		if(!$this->variableCheck($path, 'string')){
			return false;
		}

		$filelist = $this->fileList(dirname($path));
		if(is_array($filelist)){
			if(in_array(basename($path), $filelist)){
				return true;
			}
		}
		return false;
	}

	/**
	 * Upload the given file to the ftp
	 * @param  string $remote_file FTP file path 
	 * @param  string $local_file  Local file path
	 * @param  constant $mode      Mode of copy
	 * @return boolean
	 * @access public
	 */
	public function upload($remote_file, $local_file, $mode = FTP_BINARY){

		if(isset($remote_file, $local_file)) {
			if(file_exists($local_file)) {
				return ftp_put($this->connection, $remote_file, $local_file, $mode);
			}else{
				throw new Exception("Error: local file does not exit, filename: ". $local_file);
			}
		}
		return false;
	}

	/**
	 * Recursive file upload
	 * @param  string $remote_dir FTP dir path
	 * @param  string $local_dir  Local dir path (must be a dir)
	 * @param  constant $mode     a feltöltés módja
	 * @return boolean
	 * @access public
	 */
	public function rUpload($remote_dir, $local_dir, $mode = FTP_BINARY){

		if(!$this->variableCheck($remote_dir, 'string')){
			return false;
		}

		if(!is_dir($local_dir)){
			throw new Exception("THe given path is not a dir", 1);
		}

		$objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($local_dir), RecursiveIteratorIterator::SELF_FIRST);
        foreach ($objects as $name => $object){
            $srcpath = $object->getPathname();
            $realtivepath = str_replace($local_dir, "", $srcpath);
            if (!$objects->isDot())
            {
            	$folderpath = str_replace(basename($realtivepath), "", $realtivepath);
            	if($folderpath == "/"){
            		$folderpath = "";
            	}
            	$this->mkdir($remote_dir . $folderpath);

                if(!$object->isDir()){

                	$srcpath = $local_dir . $realtivepath;
		       		$destpath = $remote_dir . $realtivepath;
		       		$this->upload($destpath, $srcpath);
                }
            }
        }
        return true;
	}

	/**
	 * Download the file from ftp
	 * @param  string $remote_file FTP file path
	 * @param  string $local_file  Local file path
	 * @param  constant $mode      Download type
	 * @return boolean
	 * @access public
	 */
	public function download($remote_file, $local_file, $mode = FTP_BINARY){

		if (isset($remote_file)) {
			if (ftp_get($this->connection, $local_file, $remote_file, $mode)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Creates a directory on FTP
	 * @param  string $path Dir path
	 * @return boolean
	 * @access public
	 */
	public function mkdir($path){

		if(!$this->variableCheck($path, 'string')){
			return false;
		}

		if(!$this->fileExists($path)){
			return ftp_mkdir($this->connection, $path);
		}

		return false;
	}

	/**
	 * Deletes the dir on FTP
	 * @param  string $dirpath The dir path
	 * @return boolean
	 * @access public
	 */
	public function rmdir($dirpath){

		if(!$this->variableCheck($dirpath, 'string')){
			return false;
		}

		if($this->fileExists($dirpath)){
			return ftp_rmdir($this->connection, $dirpath);
		}
		return false;
	}

	/**
	 * Deletes the file on FTP
	 * @param  string $remote_file The file path on FTP
	 * @return boolean
	 * @access public
	 */
	public function delete($remote_file){

		if(!$this->variableCheck($remote_file, 'string')){
			return false;
		}

		if($this->fileExists($remote_file)){
			return ftp_delete($this->connection, $remote_file);
		}
		return false;
	}

	/**
	 * Deletes the given dir / dir content on FTP 
	 * @param  string $dirpath Dir path on ftp
	 * @return boolean
	 * @access public
	 */
	public function rDelete($dirpath){

		if(!$this->variableCheck($dirpath, 'string')){
			return false;
		}

		$files = $this->fileList($dirpath);
		foreach ($files as $file) {

			if($file != '.' && $file != '..'){
				if(@ftp_chdir($this->connection, $dirpath.'/'.$file.'/'))
    			{
    				$this->rDelete($dirpath.'/'.$file);
    			}else{
    				$this->delete(rtrim($dirpath,'/').'/'.$file);
    			}
			}
		}

		$this->rmdir($dirpath);
	}

	/**
	 * Changes the file rights on FTP
	 * @param  string $remote_file The file path on FTP
	 * @param  integer $mode       
	 * @return boolean
	 * @access public
	 */
	public function chmod($remote_file, $mode){

		if(!$this->variableCheck($remote_file, 'string')){
			return false;
		}

		if($this->fileExists($remote_file)){
			return ftp_chmod($this->connection, $mode, $remote_file);
		}

		return false;
	}

	/**
	 * Renames the file on FTP server
	 * @param  string $oldname old file path
	 * @param  string $newname new file path
	 * @return boolean
	 * @access public
	 */
	public function rename($oldname, $newname){

		if(!$this->variableCheck($oldname, 'string')){
			return false;
		}

		if(!$this->variableCheck($newname, 'string')){
			return false;
		}

		return ftp_rename($this->connection, $oldname, $newname);
	}

	/**
	 * Changes the currect directory on ftp
	 * @param  string $dir new dir path
	 * @return boolean
	 * @access public
	 */
	public function chdir($dir){

		if(!$this->variableCheck($dir, 'string')){
			return false;
		}

		if($this->fileExists($dir)){
			return ftp_chdir($this->connection, $dir);
		}
	}

	/**
	 * Return the current directory name
	 * @return string
	 * @access public
	 */
	public function pwd(){

		return ftp_pwd($this->connection);
	}

	/**
	 * Checks the variable type
	 * @param  variable $variable the given variable
	 * @param  string $type       the variable type
	 * @return boolean
	 * @access private
	 */
	private function variableCheck($variable, $type){

		if(!isset($variable)){
			throw new Exception('Error: The given variable does not exits');
		}

		if(gettype($variable) != $type){
			throw new Exception('The give variable type should be '.$type.' instead of '.gettype($variable));
		}

		return true;
	}
}