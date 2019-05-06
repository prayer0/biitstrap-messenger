<?php
	namespace App\Util;

	class ECDH{
	    public function encrypt($plaintext, $key, $method = 'aes-256-cbc'){
	        $iv = chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0);
	        return bin2hex(openssl_encrypt($plaintext, $method, $key, OPENSSL_RAW_DATA, $iv));
	    }

	    public function decrypt($encrypted, $key, $method = 'aes-256-cbc'){
	        $iv = chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0);
	        return openssl_decrypt(hex2bin($encrypted), $method, $key, OPENSSL_RAW_DATA, $iv);
	    }
	}