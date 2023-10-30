# CORS Middleware for PHP

This middleware handles CORS requests and adds cors headers  

## Installation

This middleware can be installed with composer.

```bash
$ composer require los/los-cors
```

## Usage

Include the ```Los\Cors\CorsMiddleware``` and it's factory  ```Los\Cors\CorsMiddlewareFactory``` to your pipeline and use the configuration bellow.

```php
/**
  * Set the list of allowed origins domain with protocol.
  * For example:
  * 'allowed_origins' => ['http://www.mysite.com','https://api.mysite.com'],
  */
'allowed_origins' => ['*'],

 /**
  * Set the list of HTTP verbs.
  */
'allowed_methods' => ['GET', 'OPTIONS'],

 /**
  * Set the list of allowed headers. This is returned in the preflight request to indicate
  * which HTTP headers can be used when making the actual request
  */
'allowed_headers' => ['Authorization', 'Accept', 'Content-Type'],

 /**
  * Set the max age of the preflight request in seconds. A non-zero max age means
  * that the preflight will be cached during this amount of time
  */
'max_age' => 120,

 /**
  * Set the list of exposed headers. This is a whitelist that authorize the browser
  * to access to some headers using the getResponseHeader() JavaScript method. Please
  * note that this feature is buggy and some browsers do not implement it correctly
  */
'expose_headers' => [],

 /**
  * Standard CORS requests do not send or set any cookies by default. For this to work,
  * the client must set the XMLHttpRequest's "withCredentials" property to "true". For
  * this to work, you must set this option to true so that the server can serve
  * the proper response header.
  */
'allowed_credentials' => false,
```

### Laminas / Mezzio

If you are using [mezzio-skeleton](https://github.com/mezzio/mezzio-skeleton), you can copy `config/los-cors.global.php.dist` to `config/autoload/los-cors.global.php` and modify the configuration as needed.
