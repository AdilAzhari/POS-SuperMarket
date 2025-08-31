<?php

declare(strict_types=1);

namespace App\Traits;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

trait HandlesFileUploads
{
    public function uploadFile(UploadedFile $file, string $directory = 'uploads', string $disk = 'public'): string
    {
        $filename = $this->generateUniqueFilename($file);

        return $file->storeAs($directory, $filename, $disk);
    }

    public function uploadImage(
        UploadedFile $file,
        string $directory = 'images',
        string $disk = 'public',
        ?int $width = null,
        ?int $height = null,
        int $quality = 90
    ): string {
        $filename = $this->generateUniqueFilename($file);
        $fullPath = $directory.'/'.$filename;

        $image = Image::read($file->getPathname());

        if ($width || $height) {
            $image->resize($width, $height, function ($constraint): void {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
        }

        $processedImage = $image->encode(quality: $quality);

        Storage::disk($disk)->put($fullPath, $processedImage);

        return $fullPath;
    }

    public function uploadImageWithThumbnail(
        UploadedFile $file,
        string $directory = 'images',
        string $disk = 'public',
        int $thumbnailWidth = 300,
        int $thumbnailHeight = 300,
        int $quality = 90
    ): array {
        $filename = $this->generateUniqueFilename($file);
        $originalPath = $directory.'/'.$filename;
        $thumbnailPath = $directory.'/thumbnails/'.$filename;

        $image = Image::read($file->getPathname());

        // Save original (optionally resized for web optimization)
        $originalImage = clone $image;
        $originalImage->resize(1200, 1200, function ($constraint): void {
            $constraint->aspectRatio();
            $constraint->upsize();
        });
        Storage::disk($disk)->put($originalPath, $originalImage->encode(quality: $quality));

        // Create and save thumbnail
        $thumbnail = $image->resize($thumbnailWidth, $thumbnailHeight, function ($constraint): void {
            $constraint->aspectRatio();
            $constraint->upsize();
        });
        Storage::disk($disk)->put($thumbnailPath, $thumbnail->encode(quality: $quality));

        return [
            'original' => $originalPath,
            'thumbnail' => $thumbnailPath,
        ];
    }

    /**
     * @throws Exception
     */
    public function resizeImage(
        string $path,
        int $width,
        int $height,
        string $disk = 'public',
        bool $maintainAspectRatio = true
    ): string {
        $storage = Storage::disk($disk);

        if (! $storage->exists($path)) {
            throw new Exception("Image not found: $path");
        }

        $image = Image::read($storage->get($path));

        if ($maintainAspectRatio) {
            $image->resize($width, $height, function ($constraint): void {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
        } else {
            $image->resize($width, $height);
        }

        $storage->put($path, $image->encode());

        return $path;
    }

    public function uploadMultipleFiles(array $files, string $directory = 'uploads', string $disk = 'public'): array
    {
        $uploadedFiles = [];

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $uploadedFiles[] = $this->uploadFile($file, $directory, $disk);
            }
        }

        return $uploadedFiles;
    }

    public function deleteFile(string $path, string $disk = 'public'): bool
    {
        return Storage::disk($disk)->delete($path);
    }

    public function deleteMultipleFiles(array $paths, string $disk = 'public'): bool
    {
        return Storage::disk($disk)->delete($paths);
    }

    public function fileExists(string $path, string $disk = 'public'): bool
    {
        return Storage::disk($disk)->exists($path);
    }

    public function getFileUrl(string $path, string $disk = 'public'): string
    {
        return Storage::disk($disk)->url($path);
    }

    /**
     * @throws Exception
     */
    public function optimizeImage(
        string $path,
        string $disk = 'public',
        int $quality = 85,
        ?int $maxWidth = 1920,
        ?int $maxHeight = 1080
    ): string {
        $storage = Storage::disk($disk);

        if (! $storage->exists($path)) {
            throw new Exception("Image not found: $path");
        }

        $image = Image::read($storage->get($path));

        if ($maxWidth || $maxHeight) {
            $image->resize($maxWidth, $maxHeight, function ($constraint): void {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
        }

        $storage->put($path, $image->encode(quality: $quality));

        return $path;
    }

    /**
     * @throws Exception
     */
    public function cropImage(
        string $path,
        int $width,
        int $height,
        int $x = 0,
        int $y = 0,
        string $disk = 'public'
    ): string {
        $storage = Storage::disk($disk);

        if (! $storage->exists($path)) {
            throw new Exception("Image not found: $path");
        }

        $image = Image::read($storage->get($path));
        $image->crop($width, $height, $x, $y);

        $storage->put($path, $image->encode());

        return $path;
    }

    protected function generateUniqueFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $name = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));

        return $name.'_'.time().'_'.Str::random(8).'.'.$extension;
    }

    protected function validateFileType(UploadedFile $file, array $allowedTypes): bool
    {
        return in_array($file->getClientOriginalExtension(), $allowedTypes);
    }

    protected function validateFileSize(UploadedFile $file, int $maxSizeInKb): bool
    {
        return $file->getSize() <= ($maxSizeInKb * 1024);
    }

    protected function isImageFile(UploadedFile $file): bool
    {
        $imageTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];

        return $this->validateFileType($file, $imageTypes);
    }
}
