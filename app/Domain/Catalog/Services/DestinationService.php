<?php

namespace App\Domain\Catalog\Services;

use App\Domain\Audit\AuditLogger;
use App\Enums\DestinationStatus;
use App\Models\Addon;
use App\Models\Destination;
use App\Models\DestinationContact;
use App\Models\DestinationMedia;
use App\Models\DestinationModeration;
use App\Models\Package;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class DestinationService
{
    public function __construct(
        protected MediaService $mediaService
    ) {}

    /**
     * Create new destination draft.
     */
    public function createDestination(array $data, User $user): Destination
    {
        return DB::transaction(function () use ($data, $user) {
            $partnerId = $user->isInternal() && isset($data['partner_id'])
                ? $data['partner_id']
                : $user->id;

            $slug = Str::slug($data['name']);
            $count = Destination::where('slug', 'like', "{$slug}%")->count();
            if ($count > 0) {
                $slug .= '-'.($count + 1);
            }

            $coverPath = null;
            if (isset($data['cover_image']) && $data['cover_image'] instanceof UploadedFile) {
                $coverPath = $this->mediaService->storeOptimizedImage($data['cover_image'], 'destinations/covers');
            }

            $destination = Destination::create([
                'partner_id' => $partnerId,
                'category_id' => $data['category_id'],
                'name' => $data['name'],
                'slug' => $slug,
                'province_id' => $data['province_id'],
                'city' => $data['city'],
                'address' => $data['address'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'short_description' => $data['short_description'] ?? null,
                'description' => $data['description'] ?? null,
                'educational_highlights' => $data['educational_highlights'] ?? null,
                'suitable_for' => $data['suitable_for'] ?? ['study_tour'],
                'has_permit_document' => (bool) ($data['has_permit_document'] ?? false),
                'cancellation_policy_id' => $data['cancellation_policy_id'] ?? null,
                'default_daily_capacity' => $data['default_daily_capacity'] ?? 50,
                'cover_image' => $coverPath,
                'status' => $user->isInternal() ? DestinationStatus::PUBLISHED : DestinationStatus::DRAFT,
                'published_at' => $user->isInternal() ? now() : null,
            ]);

            if (! empty($data['facilities'])) {
                $destination->facilities()->sync($data['facilities']);
            }

            AuditLogger::log('create_destination', $destination, "Destinasi '{$destination->name}' dibuat");

            return $destination;
        });
    }

    /**
     * Update existing destination.
     */
    public function updateDestination(Destination $destination, array $data, User $user): Destination
    {
        return DB::transaction(function () use ($destination, $data) {
            if (isset($data['cover_image']) && $data['cover_image'] instanceof UploadedFile) {
                $this->mediaService->deleteFile($destination->cover_image);
                $data['cover_image'] = $this->mediaService->storeOptimizedImage($data['cover_image'], 'destinations/covers');
            } else {
                unset($data['cover_image']);
            }

            if (isset($data['name']) && $data['name'] !== $destination->name) {
                $slug = Str::slug($data['name']);
                $count = Destination::where('slug', 'like', "{$slug}%")
                    ->where('id', '!=', $destination->id)
                    ->count();
                if ($count > 0) {
                    $slug .= '-'.($count + 1);
                }
                $data['slug'] = $slug;
            }

            $destination->update($data);

            if (isset($data['facilities'])) {
                $destination->facilities()->sync($data['facilities']);
            }

            AuditLogger::log('update_destination', $destination, "Destinasi '{$destination->name}' diperbarui");

            return $destination;
        });
    }

    /**
     * Submit destination for admin curation.
     */
    public function submitForReview(Destination $destination, User $user): Destination
    {
        if ($destination->status !== DestinationStatus::DRAFT && $destination->status !== DestinationStatus::REVISION_REQUIRED) {
            throw new InvalidArgumentException('Hanya destinasi draf atau perlu revisi yang dapat diajukan kurasi.');
        }

        $destination->update([
            'status' => DestinationStatus::PENDING_REVIEW,
        ]);

        AuditLogger::log('submit_destination', $destination, "Destinasi '{$destination->name}' diajukan untuk kurasi");

        return $destination;
    }

    /**
     * Admin curation moderation (approve, revision, reject).
     */
    public function moderate(Destination $destination, string $action, ?string $note, User $admin): Destination
    {
        return DB::transaction(function () use ($destination, $action, $note, $admin) {
            $newStatus = match ($action) {
                'approve' => DestinationStatus::PUBLISHED,
                'revision' => DestinationStatus::REVISION_REQUIRED,
                'reject' => DestinationStatus::ARCHIVED,
                default => throw new InvalidArgumentException("Aksi kurasi tidak valid: {$action}"),
            };

            $destination->status = $newStatus;
            if ($action === 'approve') {
                $destination->published_at = now();
            }
            $destination->save();

            DestinationModeration::create([
                'destination_id' => $destination->id,
                'admin_id' => $admin->id,
                'action' => $action,
                'note' => $note,
            ]);

            AuditLogger::log('moderate_destination', $destination, "Kurasi destinasi: {$action}. Catatan: {$note}");

            return $destination;
        });
    }

    /**
     * Toggle active/inactive for published destination.
     */
    public function toggleActive(Destination $destination): Destination
    {
        if ($destination->status === DestinationStatus::PUBLISHED) {
            $destination->status = DestinationStatus::INACTIVE;
        } elseif ($destination->status === DestinationStatus::INACTIVE) {
            $destination->status = DestinationStatus::PUBLISHED;
        } else {
            throw new InvalidArgumentException('Hanya destinasi yang sudah disetujui yang dapat diaktifkan / dinonaktifkan.');
        }

        $destination->save();
        AuditLogger::log('toggle_destination_active', $destination, "Status aktif destinasi diubah menjadi {$destination->status->value}");

        return $destination;
    }

    /**
     * Add gallery media image.
     */
    public function addMedia(Destination $destination, UploadedFile $file, ?string $caption = null, int $sortOrder = 0): DestinationMedia
    {
        if ($destination->media()->count() >= 10) {
            throw new InvalidArgumentException('Maksimal galeri foto destinasi adalah 10 foto.');
        }

        $path = $this->mediaService->storeOptimizedImage($file, 'destinations/gallery');

        return DestinationMedia::create([
            'destination_id' => $destination->id,
            'path' => $path,
            'caption' => $caption,
            'sort_order' => $sortOrder,
        ]);
    }

    /**
     * Delete gallery media.
     */
    public function deleteMedia(DestinationMedia $media): void
    {
        $this->mediaService->deleteFile($media->path);
        $media->delete();
    }

    /**
     * Save or update destination contact.
     */
    public function saveContact(Destination $destination, array $data, ?int $contactId = null): DestinationContact
    {
        if ($contactId) {
            $contact = DestinationContact::where('destination_id', $destination->id)->findOrFail($contactId);
            $contact->update($data);

            return $contact;
        }

        $data['destination_id'] = $destination->id;

        return DestinationContact::create($data);
    }

    /**
     * Save or update package.
     */
    public function savePackage(Destination $destination, array $data, ?int $packageId = null): Package
    {
        if ($packageId) {
            $pkg = Package::where('destination_id', $destination->id)->findOrFail($packageId);
            $pkg->update($data);

            return $pkg;
        }

        $data['destination_id'] = $destination->id;
        $data['slug'] = Str::slug($data['name']);

        return Package::create($data);
    }

    /**
     * Save or update addon.
     */
    public function saveAddon(Destination $destination, array $data, ?int $addonId = null): Addon
    {
        if ($addonId) {
            $addon = Addon::where('destination_id', $destination->id)->findOrFail($addonId);
            $addon->update($data);

            return $addon;
        }

        $data['destination_id'] = $destination->id;

        return Addon::create($data);
    }

    /**
     * Toggle wishlist for a user.
     */
    public function toggleWishlist(User $user, Destination $destination): bool
    {
        $existing = Wishlist::where('user_id', $user->id)
            ->where('destination_id', $destination->id)
            ->first();

        if ($existing) {
            $existing->delete();

            return false; // Removed
        }

        Wishlist::create([
            'user_id' => $user->id,
            'destination_id' => $destination->id,
        ]);

        return true; // Added
    }
}
