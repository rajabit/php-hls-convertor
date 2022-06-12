<?php

use App\Helpers\Mix;


if (!function_exists('mix')) {
      /**
       * Get the path to a versioned Mix file.
       *
       * @param  string  $path
       * @param  string  $manifestDirectory
       * @return \Illuminate\Support\HtmlString|string
       *
       * @throws \Exception
       */
      function mix($path, $manifestDirectory = '')
      {
            return app(Mix::class)(...func_get_args());
      }
}

if (!function_exists('public_path')) {
      function public_path($path = null)
      {
            return rtrim(app()->basePath('public/' . $path), '/');
      }
}
