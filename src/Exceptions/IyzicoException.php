<?php

namespace ArtisanXL\CashierIyzico\Exceptions;

use RuntimeException;

/**
 * Base class for every exception this package throws deliberately, so host
 * apps can catch \ArtisanXL\CashierIyzico\Exceptions\IyzicoException to
 * handle any package-specific failure without enumerating each subclass.
 */
class IyzicoException extends RuntimeException {}
