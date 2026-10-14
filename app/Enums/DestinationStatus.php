<?php

namespace App\Enums;

enum DestinationStatus: string
{
    case DRAFT = 'draft';
    case PENDING_REVIEW = 'pending_review';
    case REVISION_REQUIRED = 'revision_required';
    case PUBLISHED = 'published';
    case INACTIVE = 'inactive';
    case ARCHIVED = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draf',
            self::PENDING_REVIEW => 'Menunggu Kurasi',
            self::REVISION_REQUIRED => 'Perlu Revisi',
            self::PUBLISHED => 'Tayang',
            self::INACTIVE => 'Nonaktif',
            self::ARCHIVED => 'Diarsipkan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::PENDING_REVIEW => 'amber',
            self::REVISION_REQUIRED => 'rose',
            self::PUBLISHED => 'emerald',
            self::INACTIVE => 'zinc',
            self::ARCHIVED => 'slate',
        };
    }
}
