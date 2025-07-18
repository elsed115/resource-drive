<?php

namespace Elsed115\ResourceDrive;

use Laravel\Nova\ResourceTool;
use Closure;
use Illuminate\Support\Facades\Storage;

class ResourceDrive extends ResourceTool
{
    protected ?Closure $filesystemCallback = null;

    public function name(): string
    {
        return 'Resource Drive';
    }

    public function component(): string
    {
        // deve corrispondere al nome registrato in JS
        return 'resource-file-manager';
    }

    /**
     * Imposta un filesystem custom.
     */
    public function filesystem(Closure $callback): self
    {
        $this->filesystemCallback = $callback;
        return $this;
    }

    public function resolveFilesystem($request)
    {
        if ($this->filesystemCallback) {
            return call_user_func($this->filesystemCallback, $request);
        }
        return Storage::disk('minio');
    }

    public function hasCustomFilesystem(): bool
    {
        return (bool) $this->filesystemCallback;
    }
}
