<?php

namespace App\Exceptions;

use Exception;

/**
 * Thrown when the message is safe to show to an end user verbatim.
 * Every other exception is logged and replaced with a generic message.
 */
class UserMessageException extends Exception {}
