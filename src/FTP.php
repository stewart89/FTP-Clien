<?php

/*
* FTP müveleteket végre hajtó osztály
 */

class FTP {

	/**
	 * A szerver elérése (címe)
	 * @var string
	 * @access private
	 */
	private $host;

	/**
	 * Felhasználó név
	 * @var string
	 * @access private
	 */
	private $username;

	/**
	 * Jelszó
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
	 * Probálkozási idő
	 * @var integer
	 * @access private
	 */
	private $timeout;

	/**
	 * Az ftp csatlakozási objektumot
	 * @var object
	 * @access private
	 */
	private $connection;

	/**
	 * Peldányosításkor kell megadni a címet, felhasználónevet, jelszót, meglehet adni a portot és probálkozási időt is.
	 * @param string  $host     szerver elérése
	 * @param string  $username felhasználónév
	 * @param string  $password jelszó
	 * @param integer $port     port
	 * @param integer $timeout  probálkozási idő
	 * @access public
	 */
	public function __construct($host, $username, $password, $port = 21, $timeout = 90){

		if($host == '' || $username == '' || $password == ''){

			throw new Exception('Üres szerver, felhasználónév, vagy jelszó');
			return false;
		}

		$this->host = $host;
		$this->username = $username;
		$this->password = $password;
		$this->port = $port;
		$this->timeout = $timeout;

		if($this->connection = ftp_connect($host, $port, $timeout)){

			if(!ftp_login($this->connection, $username, $password)){

				throw new Exception('Hiba a bejelentkezés során');
				return false;
			}
		}else{

			throw new Exception('Hiba a csatlakozás során');
			return false;
		}
	}

	/**
	 * Destructor, bontja a kapcsolatot az ftp szerverrel
	 */
	public function __destruct(){

		$this->disconnect();
	}

	/**
	 * A már beálitott paraméterek alapján újra csatlakozik a szerverhez, miután az előző kapcsolatot bontotta
	 * @return boolean
	 * @access public
	 */
	public function reConnect(){

		$this->disconnect();

		if($this->connection = ftp_connect($this->host, $this->port, $this->timeout)){

			if(!ftp_login($this->connection, $this->username, $this->password)){

				throw new Exception('Hiba a bejelentkezés során');
				return false;
			}
		}else{

			throw new Exception('Hiba a csatlakozás során');
			return false;
		}
		return true;
	}

	/**
	 * Beállitja a passziv módot
	 * @return boolean
	 * @access public
	 */
	public function setPassiveMode(){

		/*if(!ftp_pasv($this->connection, true)){

			throw new Exception('Hiba a passziv mód beállitása során');
			return false;
		}*/ 
		return true;
	}
	
	/**
	 * Beállitja a passziv módot
	 * @return boolean
	 * @access public
	 */
	public function setPassiveMode2(){
	
		if(!ftp_pasv($this->connection, true)){
	
			throw new Exception('Hiba a passziv mód beállitása során');
			return false;
		}
		return true;
	}

	/**
	 * Törli a passziv módot
	 * @return boolean
	 * @access public
	 */
	public function deletePassiveMode(){

		if(!ftp_pasv($this->connection, false)){

			throw new Exception('Hiba a passziv törlése beállitása során');
			return false;
		}
		return true;
	}

	/**
	 * Bontra a kapcsolatot az ftp szerverrel
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
	 * A megadott utvonalon vissza adja fájlokat
	 * @param  string $path elérési út
	 * @return array       A fájlokat tartalmazó tömb
	 * @access public
	 */
	public function fileList($path){

		if(!$this->variableCheck($path, 'string')){
			return false;
		}

		if(!$filelist = ftp_nlist($this->connection, $path)){

			throw new Exception('Hiba a fájl lista lekérdezése során.');
			return false;
		}
		return $filelist;
	}

	/**
	 * Megnézi hogy a megadott fájl létezike
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
	 * Feltölt egy fájlt az ftp szerverre
	 * @param  string $remote_file Az ftp fájl
	 * @param  string $local_file  a helyi fájl
	 * @param  constant $mode      a másolás módja
	 * @return boolean
	 * @access public
	 */
	public function upload($remote_file, $local_file, $mode = FTP_BINARY){

		if(isset($remote_file, $local_file)) {
			if(file_exists($local_file)) {
				return ftp_put($this->connection, $remote_file, $local_file, $mode);
			}else{
				throw new Exception("A helyi fájl nem létezik, fájlnév: ". $local_file);
			}
		}
		return false;
	}

	/**
	 * Rekurziv feltöltés
	 * @param  string $remote_dir Az ftp elérés, hogy hová töltse a fájlokat
	 * @param  string $local_dir  a helyi fájlok, ami mapp kell hogy legyen
	 * @param  constant $mode     a feltöltés módja
	 * @return boolean
	 * @access public
	 */
	public function rUpload($remote_dir, $local_dir, $mode = FTP_BINARY){

		if(!$this->variableCheck($remote_dir, 'string')){
			return false;
		}

		if(!is_dir($local_dir)){
			throw new Exception("A megadott fájl nem mappa", 1);
			return false;
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
            	$this->mkdir($remote_dir.$folderpath);

                if(!$object->isDir()){

                	$srcpath = $local_dir.$realtivepath;
		       		$destpath = $remote_dir.$realtivepath;
		       		$this->upload($destpath, $srcpath);
                }
            }
        }
        return true;
	}

	/**
	 * Fájl letöltése ftpről
	 * @param  string $remote_file A ftpn levő fájl
	 * @param  string $local_file  Az elérési útvonal ahová letöltje
	 * @param  constant $mode      A letöltés módja
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
	 * Mappát hoz létre az ftp szerveren a megadott utvonalon
	 * @param  string $path a mappa elérési utvonala
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
	 * Törli a megadott utvonalon található mappát
	 * @param  string $dirpath a mappa elérését tartalmazó utvonal
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
	 * Fájlt töröl az ftp szerverről
	 * @param  string $remote_file A fájl elérési utvonala
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
	 * Törli a megadott mappa tartalmát
	 * @param  string $dirpath a mappa elérése
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
	 * Megváltoztatja egy fájl hozzá férési jogát
	 * @param  string $remote_file A fájl elérése
	 * @param  integer $mode       Hozzáférési mód
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
	 * Átnevez egy fájl az ftp szerveren
	 * @param  string $oldname a régi fájl elérése
	 * @param  string $newname az új fájl elérése
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
	 * Megváltoztatja az aktuális mappát
	 * @param  string $dir a új mappa elérése
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
	 * Vissza adja az aktuális mappát
	 * @return string
	 * @access public
	 */
	public function pwd(){

		return ftp_pwd($this->connection);
	}

	/**
	 * Leelenörzi hogy a megadott változó tipusa megfelelőe
	 * @param  variable $variable a megadott változó
	 * @param  string $type       a változó tipusa
	 * @return boolean
	 * @access private
	 */
	private function variableCheck($variable, $type){

		if(!isset($variable)){
			throw new Exception('A változó nem létezik');
		}

		if(gettype($variable) != $type){
			throw new Exception('A paraméter tipusa '.$type.' kellene legyen '.gettype($variable).' lett megadva');
		}

		return true;
	}
}