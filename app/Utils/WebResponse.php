<?php

namespace App\Utils;

use App\Exceptions\UserMessageException;
use Inertia\Inertia;
use Throwable;

class WebResponse
{
    public const GENERIC_ERROR = 'Something went wrong. Please try again.';

    public static function response($result, $redirect = null)
    {
        if ($result instanceof Throwable) {
            return back()->withErrors(['errors' => self::messageFor($result)]);
        }

        if (is_null($redirect)) {
            return redirect()->back();
        }

        if (is_array($redirect)) {
            [$routeName, $params] = $redirect;

            return Inertia::location(route($routeName, $params));
        }

        return Inertia::location(route($redirect));
    }

    public static function inertia($result, $redirectRoute, $param = null)
    {
        if ($result instanceof Throwable) {
            return back()->withErrors(['errors' => self::messageFor($result)]);
        }

        return Inertia::location(route($redirectRoute, $param));
    }

    public static function inertiaRender($result, $render, $param = [])
    {
        if ($result instanceof Throwable) {
            return back()->withErrors(['errors' => self::messageFor($result)]);
        }

        return Inertia::render($render, $param ?? $result);
    }

    public static function json($result, $message = 'Success', $status = 200)
    {
        if ($result instanceof Throwable) {
            return response()->json(['message' => self::messageFor($result)], 400);
        }

        return response()->json([
            'message' => $message,
            'data' => $result,
        ], $status);
    }

    /**
     * Only exceptions we raised deliberately are shown to the user;
     * anything else is logged and hidden behind a generic message.
     */
    protected static function messageFor(Throwable $e): string
    {
        if ($e instanceof UserMessageException) {
            return $e->getMessage();
        }

        report($e);

        return self::GENERIC_ERROR;
    }
}
