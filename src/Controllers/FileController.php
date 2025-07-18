<?php

namespace Elsed115\ResourceDrive\Http\Controllers;

use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Laravel\Nova\Http\Requests\NovaRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToRetrieveMetadata;
use Illuminate\Support\Str;
use App\Models\Work;
use Laravel\Nova\Nova;
use Aws\S3\S3Client;

class FileController extends Controller
{
    /**
     * Get the resource model instance from the request.
     *
     * @param NovaRequest $request
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function getResourceModel(NovaRequest $request)
    {
        $resourceName = $request->input('resourceName');
        $resourceId = $request->input('resourceId');

        // Automatizza la risoluzione del model
        $modelClass = '\\App\\Models\\' . Str::studly(Str::singular($resourceName));

        if (!class_exists($modelClass)) {
            Log::error('[FileManager] Resource model not found for key: ' . $resourceName);
            abort(404, 'Resource model not found.');
        }

        $model = (new $modelClass)->findOrFail($resourceId);

        if (!method_exists($model, 'resourceFileManagerDisk') || !method_exists($model, 'resourceFileManagerRootPath')) {
            Log::error('[FileManager] Model ' . get_class($model) . ' does not implement required methods.');
            abort(500, 'The resource model must implement resourceFileManagerDisk() and resourceFileManagerRootPath() methods.');
        }

        return $model;
    }

    /**
     * Elenca file e cartelle in un dato percorso, garantendo la sicurezza.
     */
    public function list(NovaRequest $request)
    {
        Log::info('[FileManager] List method called.', $request->all());

        try {
            $model = $this->getResourceModel($request);
            $diskName = $model->resourceFileManagerDisk();
            Log::info('[FileManager] Using disk: ' . $diskName);
            $rootPath = $model->resourceFileManagerRootPath();
            $requestedPath = $request->input('path', '');
            $page = $request->input('page', 1);
            $perPage = $request->input('perPage', 15);
            Log::info('[FileManager] Requested path: ' . $requestedPath);

            // Security check: Prevent directory traversal
            if (Str::contains($requestedPath, '..')) {
                abort(403, 'Invalid path.');
            }

            // Always construct path from the root to prevent escaping
            if (Str::startsWith($requestedPath, $rootPath)) {
                $fullPath = rtrim($requestedPath, '/');
            } else {
                $fullPath = rtrim($rootPath . '/' . $requestedPath, '/');
            }           
             Log::info('[FileManager] Full path for listing: ' . $fullPath);

            $files = collect(Storage::disk($diskName)->files($fullPath))
                ->map(function ($file) use ($diskName) {
                    try {
                        $url = Storage::disk($diskName)->temporaryUrl($file, now()->addMinutes(5));
                        Log::info('[FileManager] Generated temporary URL for ' . $file . ': ' . $url);
                        return [
                            'name' => basename($file),
                            'path' => $file,
                            'type' => 'file',
                            'size' => Storage::disk($diskName)->size($file),
                            'last_modified' => Storage::disk($diskName)->lastModified($file),
                            'url' => $url,
                        ];
                    } catch (UnableToRetrieveMetadata $e) {
                        Log::warning("[FileManager] Could not retrieve metadata for file: {$file}. Error: " . $e->getMessage());
                        return null;
                    } catch (\Exception $e) {
                        Log::error("[FileManager] Error generating temporary URL for file: {$file}. Error: " . $e->getMessage());
                        return null;
                    }
                })->filter();

            $directories = collect(Storage::disk($diskName)->directories($fullPath))
                ->map(function ($dir) {
                    return [
                        'name' => basename($dir),
                        'path' => $dir,
                        'type' => 'folder',
                    ];
                });

            $allItems = $files->merge($directories)->sortBy('name')->values();
            $total = $allItems->count();
            $paginatedItems = $allItems->forPage($page, $perPage)->values();

            $responsePayload = [
                'files' => $paginatedItems,
                'breadcrumbs' => $this->generateBreadcrumbs($rootPath, $requestedPath),
                'pagination' => [
                    'currentPage' => (int)$page,
                    'perPage' => (int)$perPage,
                    'total' => $total,
                    'lastPage' => ceil($total / $perPage),
                ],
            ];

            Log::info('[FileManager] Sending response payload.', $responsePayload);

            return response()->json($responsePayload);

        } catch (ModelNotFoundException $e) {
            Log::error('[FileManager] Resource not found: ' . $e->getMessage());
            return response()->json(['error' => 'Resource not found.'], 404);
        } catch (\Exception $e) {
            Log::error('[FileManager] An unexpected error occurred: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'An unexpected error occurred on the server.'], 500);
        }
    }

    public function generateTempLink(NovaRequest $request)
    {
        $path = $request->get('path');
        $resourceName = $request->get('resourceName');
        $resourceId = $request->get('resourceId');
        $expires = now()->addMinutes(5); // valido 10 minuti

        $token = bin2hex(random_bytes(16));
        cache()->put("file_token:$token", [
            'path' => $path,
            'resourceName' => $resourceName,
            'resourceId' => $resourceId,
            'expires' => $expires
        ], $expires);

        return response()->json([
            'url' => url("/api/rfm/proxy-download?token=$token")
        ]);
    }

    public function proxyDownload(NovaRequest $request)
    {
        $token = $request->get('token');
        $data = cache()->get("file_token:$token");

        if (!$data || now()->greaterThan($data['expires'])) {
            abort(403, 'Link scaduto o non valido');
        }

        // Crea una NovaRequest fittizia con i parametri necessari
        $fakeRequest = NovaRequest::create('', 'GET', [
            'resourceName' => $data['resourceName'],
            'resourceId' => $data['resourceId'],
        ]);
        $model = $this->getResourceModel($fakeRequest);

        $diskName = $model->resourceFileManagerDisk();
        $path = $data['path'];
        $filename = basename($path);

        if (!Storage::disk($diskName)->exists($path)) {
            abort(404, 'File not found');
        }

        $stream = Storage::disk($diskName)->readStream($path);
        return response()->stream(function () use ($stream) {
            fpassthru($stream);
        }, 200, [
            'Content-Type' => Storage::disk($diskName)->mimeType($path),
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }
    /**
     * Carica un file nel percorso specificato.
     */
    public function upload(NovaRequest $request)
    {
        Log::info('[FileManager] Upload method called.', $request->all());
        try {
            $request->validate([
                'file' => 'required|file',
                'path' => 'nullable|string',
                'resourceName' => 'required|string',
                'resourceId' => 'required',
            ]);

            $model = $this->getResourceModel($request);
            $diskName = $model->resourceFileManagerDisk();
            $rootPath = $model->resourceFileManagerRootPath();
            $uploadPath = $request->input('path', '');

            if (Str::contains($uploadPath, '..')) {
                abort(403, 'Invalid path.');
            }

            if (Str::startsWith($uploadPath, $rootPath)) {
                $realUploadPath = rtrim($uploadPath, '/');
            } else {
                $realUploadPath = rtrim($rootPath . '/' . $uploadPath, '/');
            }
            $file = $request->file('file');
            $fileName = $file->getClientOriginalName();

            Storage::disk($diskName)->putFileAs($realUploadPath, $file, $fileName);

            return response()->json(['success' => true, 'path' => $uploadPath]);
        } catch (ModelNotFoundException $e) {
            Log::error('[FileManager] Resource not found during upload: ' . $e->getMessage());
            return response()->json(['error' => 'Resource not found.'], 404);
        } catch (\Exception $e) {
            Log::error('[FileManager] An unexpected error occurred during upload: ' . $e->getMessage());
            return response()->json(['error' => 'An unexpected error occurred on the server.'], 500);
        }
    }


    /**
     * Crea una nuova cartella.
     */
    public function createFolder(NovaRequest $request)
    {
        Log::info('[FileManager] CreateFolder method called.', $request->all());
        try {
            $request->validate(['folderName' => 'required|string|max:255']);

            $model = $this->getResourceModel($request);
            $diskName = $model->resourceFileManagerDisk();
            $rootPath = $model->resourceFileManagerRootPath();
            $currentPath = $request->input('path', '');
            $newFolderName = $request->input('folderName');

            if (Str::contains($currentPath, '..') || Str::contains($newFolderName, ['/', '\\', '..'])) {
                abort(403, 'Invalid path or folder name.');
            }

            if (Str::startsWith($currentPath, $rootPath)) {
                $realFolderPath = rtrim($currentPath, '/') . '/' . $newFolderName;
            } else {
                $realFolderPath = rtrim($rootPath . '/' . $currentPath, '/') . '/' . $newFolderName;
            }

            Storage::disk($diskName)->makeDirectory($realFolderPath);

            return response()->json(['success' => true]);
        } catch (ModelNotFoundException $e) {
            Log::error('[FileManager] Resource not found during folder creation: ' . $e->getMessage());
            return response()->json(['error' => 'Resource not found.'], 404);
        } catch (\Exception $e) {
            Log::error('[FileManager] An unexpected error occurred during folder creation: ' . $e->getMessage());
            return response()->json(['error' => 'An unexpected error occurred on the server.'], 500);
        }
    }

    private function isFolder(string $key, string $disk): bool
{
    $d = Storage::disk($disk);

    // se il prefisso o il marker esistono, è cartella
    return $d->directoryExists($key) || $d->directoryExists($key . '/');
}

private function s3Client(string $disk): array
{
    $cfg = config("filesystems.disks.$disk");

    $client = new S3Client([
        'version'                 => 'latest',
        'region'                  => $cfg['region']   ?? 'us-east-1',
        'endpoint'                => $cfg['endpoint'] ?? null,
        'use_path_style_endpoint' => $cfg['use_path_style_endpoint'] ?? false,
        'credentials'             => [
            'key'    => $cfg['key'],
            'secret' => $cfg['secret'],
        ],
    ]);

    return [$client, $cfg['bucket']];
}

public function delete(NovaRequest $request)
{
    $request->validate(['path' => 'required|string']);

    $model    = $this->getResourceModel($request);
    $diskName = $model->resourceFileManagerDisk();
    $disk     = Storage::disk($diskName);
    [$s3, $bucket] = $this->s3Client($diskName);

    $key     = trim($request->input('path'), '/');   // es. user_files/1/aa
    $prefix  = Str::finish($key, '/');

    /* -------- FILE -------- */
    if ($disk->fileExists($key)) {
        $s3->deleteObject(['Bucket' => $bucket, 'Key' => $key]);
        return response()->json(['message' => 'Deleted file'], 200);
    }

    /* -------- CARTELLA -------- */

    // 1. elimina subito i possibili marker
    foreach ([$key, $key . '/'] as $marker) {
        $s3->deleteObject(['Bucket' => $bucket, 'Key' => $marker]);
    }

    // 2. enumera e cancella ogni oggetto sotto il prefisso (uno per volta → no MD5)
    $paginator = $s3->getPaginator('ListObjectsV2', [
        'Bucket' => $bucket,
        'Prefix' => $prefix,
    ]);

    foreach ($paginator as $page) {
        if (!empty($page['Contents'])) {
            foreach ($page['Contents'] as $obj) {
                $s3->deleteObject(['Bucket' => $bucket, 'Key' => $obj['Key']]);
            }
        }
    }

    return response()->json(['message' => 'Deleted folder'], 200);
}

public function rename(NovaRequest $request)
{
    $request->validate([
        'path'    => 'required|string',
        'newName' => 'required|string',
    ]);

    $model    = $this->getResourceModel($request);
    $diskName = $model->resourceFileManagerDisk();
    $disk     = Storage::disk($diskName);
    [$s3, $bucket] = $this->s3Client($diskName);

    $oldKey = trim($request->input('path'), '/');                // user_files/1/aa
    $newKey = Str::finish(trim(dirname($oldKey), '/'), '/') . trim($request->input('newName'));
    $src    = Str::finish($oldKey, '/');
    $dst    = Str::finish($newKey, '/');

    /* -------- FILE -------- */
    if ($disk->fileExists($oldKey)) {
        // copia e poi cancella l’originale
        $s3->copyObject([
            'Bucket'     => $bucket,
            'Key'        => $newKey,
            'CopySource' => "$bucket/$oldKey",
        ]);
        $s3->deleteObject(['Bucket' => $bucket, 'Key' => $oldKey]);

        return response()->json(['message' => 'Renamed file'], 200);
    }

    /* -------- CARTELLA -------- */

    // crea subito il marker della nuova cartella
    $disk->makeDirectory($dst);

    // copia ogni oggetto
    $paginator = $s3->getPaginator('ListObjectsV2', [
        'Bucket' => $bucket,
        'Prefix' => $src,
    ]);

    foreach ($paginator as $page) {
        if (!empty($page['Contents'])) {
            foreach ($page['Contents'] as $obj) {
                $from = $obj['Key'];
                $to   = $dst . Str::after($from, $src);

                $s3->copyObject([
                    'Bucket'     => $bucket,
                    'Key'        => $to,
                    'CopySource' => "$bucket/$from",
                ]);
            }
        }
    }

    // ora elimina il vecchio prefisso usando la delete qui sopra
    $fake = NovaRequest::create('', 'POST', [
        'resourceName' => $request->input('resourceName'),
        'resourceId'   => $request->input('resourceId'),
        'path'         => $oldKey,
    ]);
    return $this->delete($fake);   // risponde già in JSON
}


    /**
     * Scarica un file.
     */
    public function download(NovaRequest $request)
    {
        Log::info('[FileManager] Download method called.', $request->all());
        try {
            $validated = $request->validate(['path' => 'required|string']);

            $model = $this->getResourceModel($request);
            $diskName = $model->resourceFileManagerDisk();

            $path = $validated['path'];
            if (Str::contains($path, '..')) {
                abort(403, 'Invalid path.');
            }

            $realPath = $path;

            if (!Storage::disk($diskName)->exists($realPath)) {
                Log::warning('[FileManager] Download failed: File not found at ' . $realPath);
                abort(404, 'File not found.');
            }

            return Storage::disk($diskName)->download($realPath);
        } catch (\Exception $e) {
            Log::error('[FileManager] An unexpected error occurred during download: ' . $e->getMessage());
            return response()->json(['error' => 'An unexpected error occurred on the server.'], 500);
        }
    }

    /**
     * Genera breadcrumb per la navigazione.
     */
    private function generateBreadcrumbs($rootPath, $path)
    {
        // Remove the root path from the start of the current path to get the relative path
        $relativePath = Str::startsWith($path, $rootPath)
            ? Str::after($path, $rootPath)
            : $path;

        $relativePath = trim($relativePath, '/');

        if (empty($relativePath)) {
            return [];
        }

        $crumbs = collect(explode('/', $relativePath))->filter();
        $breadcrumbs = [];
        $currentPath = '';

        foreach ($crumbs as $crumb) {
            $currentPath = empty($currentPath) ? $crumb : $currentPath . '/' . $crumb;
            $breadcrumbs[] = ['name' => $crumb, 'path' => $rootPath . '/' . $currentPath];
        }

        return $breadcrumbs;
    }
}
