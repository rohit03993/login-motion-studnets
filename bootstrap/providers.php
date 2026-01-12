<?php

return [
    // Register FilesystemServiceProvider early to fix "Target class [files] does not exist" error
    Illuminate\Filesystem\FilesystemServiceProvider::class,
    App\Providers\AppServiceProvider::class,
];
