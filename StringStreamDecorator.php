<?php
namespace ashatrov\Psr7\StringStreamDecorator ;

use ashatrov\Psr7\StringStreamDecorator\CipherFilter ;

/**
* Decorator for StringStream
*
* @author Shatrov Aleksej Sergeevich <mail@ashatrov.ru>
*/
class StringStreamDecorator {
	/**
	* @param resource $_inp_stream - input stream
	*/
	protected $_inp_stream ;

	/**
	* @param resource $_stream_filter - stream filter resource
	*/
	protected $_stream_filter ;

	/**
	* @param string $_encryption_key - encryption key
	*/
	protected $_encryption_key ;

	/**
	* @param string $_algorithm - algorithm name
	*/
	protected $_algorithm ;

	/**
	* @param string $_algorithm - algorithm name
	*/
	protected $_filtername ;

	/**
	* @const string ALGORITHM_DEFAULT - default algorithm name
	*/
	const ALGORITHM_DEFAULT = 'aes-256-cbc' ;

	/**
	* constructor
	*
	* @param resource $inp_stream - input stream
	* @param string $encryption_key - encryption key (pasword)
	* @param string $algorithm - algorithm name
	*/
	public function __construct( $inp_stream , string $encryption_key = null , $algorithm = self::ALGORITHM_DEFAULT ) {
		$this->_inp_stream = $inp_stream ;
		$this->_encryption_key = $encryption_key ;
		$this->_algorithm = $algorithm ;
		$this->_filtername = sha1( static::class ) ;
	}

	/**
	* encryption
	*
	* @param resource $out_stream - output stream
	*
	* @return boolean|string - boolean when $out_stream set and stream when it wasn't
	*/
	public function encrypt( $out_stream = null ) {
		return $this->translate( 'encrypt' , $out_stream ) ;
	}

	/**
	* decryption
	*
	* @param resource $out_stream - output stream
	*
	* @return boolean|resource - boolean when $out_stream set and resource when it wasn't
	*/
	public function decrypt( $out_stream = null ) {
		return $this->translate( 'decrypt' , $out_stream ) ;
	}

	/**
	* general method for encryption\decryption
	*
	* @param string $action - "encrypt" or "decrypt"
	* @param resource $out_stream - output stream
	*
	* @return boolean|resource - boolean when $out_stream set and resource when it wasn't
	*/
	protected function translate( string $action , $out_stream = null ) {
		$this->_stream_filter = stream_filter_register( $this->_filtername , CipherFilter::class ) ;
		stream_filter_append( $this->_inp_stream , $this->_filtername , STREAM_FILTER_ALL , [
			'algorithm' => $this->_algorithm ,
			'encryption_key' => $this->_encryption_key ,
			'action' => $action ,
		] ) ;

		if ( $out_stream ) {
			$this->copy_to( $out_stream ) ;
		}

		return $this->_inp_stream ;
	}

	/**
	* copying to output stream
	*
	* @param resource $out_stream - output stream
	*
	* @return boolean - true when success
	*/
	protected function copy_to( $out_stream ) {
		return stream_copy_to_stream( $this->_inp_stream , $out_stream ) ;
	}

	/**
	* Removes output or input filter created using this class
	*
	* @return boolean - true when success
	*/
	public function done( ) : boolean {
		return stream_filter_remove( $this->_stream_filter ) ;
	}

	/**
	* Get or set encryption key when $encryption_key was set or not
	*
	* @param string $encryption_key - encryption key
	*
	* @return string - new or old encryption key
	*/
	public function encryption_key( string $encryption_key = null ) {
		if ( ! is_null( $encryption_key ) ) {
			$this->_encryption_key = $encryption_key ;

			return $this->_encryption_key ;
		}
		if ( is_null( $this->_encryption_key ) ) {
			$this->_encryption_key = CipherFilter::encryption_key( ) ;
		}

		return $this->_encryption_key ;
	}
}