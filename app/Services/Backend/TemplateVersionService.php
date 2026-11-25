<?php

declare(strict_types=1);

namespace App\Services\Backend;

use App\Models\Backend\Template;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Versions of DCT templates. Each version is its own row linked to the one it replaced
 * (previous_template_id). One version of a template is active unless an admin keeps more of them active.
 * Rows and files are never deleted.
 */
class TemplateVersionService
{
    /**
     * Not reachable by URL. New files go here; files uploaded earlier are on PUBLIC_DISK until archived.
     */
    private const PRIVATE_DISK = 'local';

    private const PUBLIC_DISK = 'public';

    /**
     * All templates, each as its versions from the newest to the oldest.
     *
     * @return Collection<int, Collection<int, Template>>
     */
    public function groups(): Collection
    {
        $templates = Template::query()
            ->with(['databaseEntity:id,code,name', 'creator:id,first_name,last_name'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $byId = $templates->keyBy('id');
        $replacedIds = $templates->pluck('previous_template_id')->filter()->flip();

        return $templates
            ->reject(fn (Template $template): bool => $replacedIds->has($template->id))
            ->map(function (Template $newest) use ($byId): Collection {
                $versions = collect();
                for ($version = $newest; $version !== null; $version = $byId->get($version->previous_template_id)) {
                    $versions->push($version);
                }

                return $versions;
            })
            ->values();
    }

    /**
     * All versions of one template, from the newest to the oldest.
     *
     * @return Collection<int, Template>
     */
    public function history(Template $template): Collection
    {
        return $this->groups()->first(fn (Collection $versions): bool => $versions->contains('id', $template->id), collect());
    }

    /**
     * The newest active version, or the newest one when the template is archived.
     *
     * @param  Collection<int, Template>  $versions
     */
    public function current(Collection $versions): Template
    {
        return $versions->firstWhere('is_active', true) ?? $versions->first();
    }

    /**
     * Version number offered for the next upload; null when the latest one is not a number.
     *
     * @param  Collection<int, Template>  $versions
     */
    public function suggestNextVersion(Collection $versions): ?string
    {
        $latest = (string) $versions->first()->version;

        return ctype_digit($latest) ? (string) ((int) $latest + 1) : null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, UploadedFile $file, ?int $userId): Template
    {
        return Template::create([
            ...$data,
            ...$this->storeFile($file),
            'is_active' => true,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
    }

    /**
     * Adds a new active version on top of the template and archives the others unless they are kept active.
     *
     * @param  array<string, mixed>  $data
     */
    public function createVersion(Template $template, array $data, UploadedFile $file, ?int $userId, bool $keepActive = false): Template
    {
        return DB::transaction(function () use ($template, $data, $file, $userId, $keepActive): Template {
            $versions = $this->lockedHistory($template);
            $current = $this->current($versions);
            if (! $keepActive) {
                $this->archiveVersions($versions, $userId);
            }

            return $this->create([
                ...$data,
                'database_entity_id' => $current->database_entity_id,
                'previous_template_id' => $versions->first()->id,
            ], $file, $userId);
        });
    }

    /**
     * Hides the version from the public and moves its file to private storage.
     */
    public function archive(Template $template, ?int $userId): void
    {
        DB::transaction(function () use ($template, $userId): void {
            $template->update(['is_active' => false, 'updated_by' => $userId]);
            $this->moveFileToPrivateDisk($template);
        });
    }

    /**
     * Name of the disk that holds the file of the version; null when the file is missing.
     */
    public function disk(Template $template): ?string
    {
        if (! $template->file_path) {
            return null;
        }

        return collect([self::PRIVATE_DISK, self::PUBLIC_DISK])
            ->first(fn (string $disk): bool => Storage::disk($disk)->exists($template->file_path));
    }

    /**
     * Makes an archived version active again and archives the others unless they are kept active.
     */
    public function restore(Template $template, ?int $userId, bool $keepActive = false): void
    {
        DB::transaction(function () use ($template, $userId, $keepActive): void {
            $others = $this->lockedHistory($template)->where('id', '!=', $template->id);
            if (! $keepActive) {
                $this->archiveVersions($others, $userId);
            }
            $template->update(['is_active' => true, 'updated_by' => $userId]);
        });
    }

    /**
     * History read after its oldest version is locked, so two changes of one template cannot run at once.
     *
     * @return Collection<int, Template>
     */
    private function lockedHistory(Template $template): Collection
    {
        Template::query()->whereKey($this->history($template)->last()->id)->lockForUpdate()->first(['id']);

        return $this->history($template);
    }

    /**
     * @param  Collection<int, Template>  $versions
     */
    private function archiveVersions(Collection $versions, ?int $userId): void
    {
        $versions->where('is_active', true)->each(fn (Template $version) => $this->archive($version, $userId));
    }

    /**
     * The public copy goes only after the private one is complete.
     */
    private function moveFileToPrivateDisk(Template $template): void
    {
        $path = $template->file_path;
        $public = Storage::disk(self::PUBLIC_DISK);
        $private = Storage::disk(self::PRIVATE_DISK);

        if (! $path || ! $public->exists($path)) {
            return;
        }

        $private->put($path, $public->readStream($path));

        if (! $private->exists($path) || hash_file('sha256', $private->path($path)) !== hash_file('sha256', $public->path($path))) {
            throw new RuntimeException("The file of template {$template->id} could not be moved to private storage.");
        }

        $public->delete($path);
    }

    /**
     * Stores the file under a name of its own, so it never overwrites an earlier one.
     *
     * @return array{file_path: string, filesize: int}
     */
    private function storeFile(UploadedFile $file): array
    {
        $fileName = time().'_'.Str::lower(Str::random(6)).'_'.$file->getClientOriginalName();

        return [
            'file_path' => $this->storeOnPrivateDisk($file, $fileName),
            'filesize' => $file->getSize(),
        ];
    }

    private function storeOnPrivateDisk(UploadedFile $file, string $fileName): string
    {
        $path = $file->storeAs('templates', $fileName, self::PRIVATE_DISK);

        if ($path === false) {
            throw new RuntimeException('The template file could not be stored.');
        }

        return $path;
    }
}
