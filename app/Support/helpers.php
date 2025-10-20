<?php

if (! function_exists('aytispec_version')) {
    function aytispec_version(): string
    {
        return (string) config('aytispec.ver');
    }
}
