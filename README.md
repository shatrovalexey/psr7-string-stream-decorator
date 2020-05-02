### EXAMPLE
```
use ashatrov\Psr7\StringStreamDecorator\StringStreamDecorator ;

/**
* @var resource $inp - input stream
*/
$inp = fopen( '../composer.json' , 'rb' ) ;

/**
* @var resource $out - output stream
*/
$out = fopen( '../out.txt' , 'wb' ) ;

/**
* @var ashatrov\Psr7\StringStreamDecorator\StringStreamDecorator $ssdh - decorator for stream
*/
$ssdh = new StringStreamDecorator( $inp ) ;

/**
* @var string $encryption_key - generated password
*/
$encryption_key = $ssdh->encryption_key( ) ;

/**
* encrypting $inp to $out with default 'aes-256-cbc' encryption
*/
$ssdh->encrypt( $out ) ;

/**
* @var resource $inp - input stream
*/
$inp = fopen( '../out.txt' , 'rb' ) ;

/**
* @var resource $out - output stream
*/
$out = fopen( '../inp.txt' , 'wb' ) ;

/**
* @var ashatrov\Psr7\StringStreamDecorator\StringStreamDecorator $ssdh - decorator for stream
*/
$ssdh = new StringStreamDecorator( $inp , $encryption_key ) ;

/**
* decrypting $inp to $out with default 'aes-256-cbc' encryption
*/
$ssdh->decrypt( $out ) ;
```

### AUTHOR
Shatrov Aleksej Sergeevich <mail@ashatrov.ru>