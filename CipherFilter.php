<?php
namespace ashatrov\Psr7\StringStreamDecorator ;

/**
* Filter to encrypt\decrypt a stream
*
* @author Shatrov Aleksej Sergeevich <mail@ashatrov.ru>
*/
class CipherFilter extends \PHP_User_Filter {
	/**
	* @param string $filtername - filter name
	*/
	public $filtername ;

	/**
	* @param string $_data - input stream value
	*/
	protected $_data ;

	/**
	* @param string $iv - initialization vector
	*/
	protected $iv ;

	/**
	* @param integer $cipher_iv_len - length of the $iv
	*/
	protected $cipher_iv_len ;

	/**
	* @param string $encryption_key - password
	*/
	protected $encryption_key ;

	/**
	* @param string $method - "encrypt" or "decrypt"
	*/
	protected $method ;

	/**
	* @param string $algorithm - an algorithm name
	*/
	protected $algorithm ;

	/**
	* @param boolean $is_encrypt - when action is "encrypt"
	*/
	protected $is_encrypt ;

	/**
	* @param boolean $is_decrypt - when action is "decrypt"
	*/
	protected $is_decrypt ;

	/**
	* Initialization
	*
	* @throws \Exception - an exception when error arguments passed
	*
	* @return boolean  - the result
	*/
	public function onCreate( ) {
		$this->method = null ;
		$this->filtername = sha1( static::class ) ;

		if ( ! isset( $this->params[ 'encryption_key' ] ) || ! mb_strlen( $this->params[ 'encryption_key' ] ) ) {
			throw new \Exception( 'Param "encryption_key" is empty' ) ;
		}
		if ( ! isset( $this->params[ 'algorithm' ] ) ) {
			throw new \Exception( 'Param "algorithm" is empty' ) ;
		}
		if ( ! in_array( $this->params[ 'algorithm' ] , openssl_get_cipher_methods( ) ) ) {
			throw new \Exception( 'Algorithm "' . $this->params[ 'algorithm' ] . '" not supported' ) ;
		}

		switch ( @$this->params[ 'action' ] ) {
			case 'encrypt' : {
				$this->method = 'openssl_encrypt' ;
				$this->is_encrypt = true ;

				break ;
			}
			case 'decrypt' : {
				$this->method = 'openssl_decrypt' ;
				$this->is_decrypt = true ;

				break ;
			}
			default : {
				throw new \Exception( 'Param "action" isn\'t either "encrypt" or "decrypt"' ) ;
			}
		}

		$this->algorithm = $this->params[ 'algorithm' ] ;
		$this->encryption_key = $this->params[ 'encryption_key' ] ;
		$this->iv = $this->get_iv( ) ;
		$this->cipher_iv_len = strlen( $this->iv ) ;
		$this->_data = '' ;

		return true ;
	}

	/**
	* Get random IV
	*
	* @return string - random IV
	*/
	protected function get_iv( ) {
		$cipher_iv_len = openssl_cipher_iv_length( $this->algorithm ) ;

		return openssl_random_pseudo_bytes( $cipher_iv_len ) ;
	}

	/**
	* Get random encryption key
	*
	* @return string - encryption key
	*/
	public static function encryption_key( ) {
		return sha1( uniqid( ) ) ;
	}
 
	/*
	* This is where the actual stream data conversion takes place
	*
	* @param resource $inp - input stream
	* @param resource $out - output stream
	* @param integer $consumed - count of bytes used
	* @param integer $closing - when input stream has no more values to read and the signal that the stream have to wait the input
	*
	* @return integer - the result
	*/
	public function filter( $inp , $out , &$consumed , $closing ) {
		/* We read all the stream data and store it in 
		   the '$_data' variable 
		*/
		if ( $bucket = stream_bucket_make_writeable( $inp ) ) {
			$this->_data .= str_repeat( ' ' , $this->cipher_iv_len ) . $bucket->data ;
			$this->bucket = $bucket ;
		}
		while ( $bucket = stream_bucket_make_writeable( $inp ) ) {
			$this->_data .= $bucket->data ;
			$this->bucket = $bucket ;
		}
		if ( empty( $closing ) ) {
			return \PSFS_FEED_ME ;
		}

		$consumed += mb_strlen( $this->_data ) ;

		// Enryption\decryption
		$this->bucket->data = ( $this->method )( $this->_data , $this->algorithm , $this->encryption_key , 0 , $this->iv ) ;

		if ( $this->is_decrypt ) {
			$this->bucket->data = substr( $this->bucket->data , $this->cipher_iv_len ) ;
		}

		$this->bucket->datalen = mb_strlen( $this->_data ) ;

		if( empty( $this->bucket->data ) ) {
			return \PSFS_PASS_ON ;
		}

		stream_bucket_append( $out , $this->bucket ) ;

		return \PSFS_PASS_ON ;
	}
}